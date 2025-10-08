<?php

namespace App\Services\Delivery;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Models\DeliveryZone;
use App\Models\ParkingLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryManager
{
    protected RouteOptimizer $routeOptimizer;
    protected LocationTracker $locationTracker;
    protected NotificationService $notificationService;

    public function __construct(
        RouteOptimizer $routeOptimizer,
        LocationTracker $locationTracker,
        NotificationService $notificationService
    ) {
        $this->routeOptimizer = $routeOptimizer;
        $this->locationTracker = $locationTracker;
        $this->notificationService = $notificationService;
    }

    public function createDelivery(Order $order, array $data = []): Delivery
    {
        DB::beginTransaction();
        try {
            // Determine delivery zone based on address
            $zone = $this->determineDeliveryZone($data['delivery_address'] ?? $order->delivery_address);
            
            // Calculate delivery fee
            $deliveryFee = $zone ? $zone->calculateDeliveryFee(
                $data['distance'] ?? 0,
                $data['priority'] ?? 'standard'
            ) : 25.00;

            // Create delivery record
            $delivery = Delivery::create([
                'order_id' => $order->id,
                'delivery_zone_id' => $zone?->id,
                'tracking_code' => Delivery::generateTrackingCode(),
                'priority' => $data['priority'] ?? 'standard',
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'time_slot' => $data['time_slot'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? $order->delivery_address,
                'delivery_instructions' => $data['delivery_instructions'] ?? $order->delivery_notes,
                'contact_name' => $data['contact_name'] ?? $order->customer->name,
                'contact_phone' => $data['contact_phone'] ?? $order->customer->phone,
                'requires_cold_chain' => $data['requires_cold_chain'] ?? $this->requiresColdChain($order),
                'min_temperature' => $data['min_temperature'] ?? null,
                'max_temperature' => $data['max_temperature'] ?? null,
                'delivery_fee' => $deliveryFee,
                'estimated_minutes' => $zone?->estimated_minutes ?? 30,
                'sms_enabled' => $data['sms_enabled'] ?? true,
                'whatsapp_enabled' => $data['whatsapp_enabled'] ?? false,
                'email_enabled' => $data['email_enabled'] ?? true,
                'status' => 'pending'
            ]);

            // Auto-assign driver if priority is urgent or express
            if (in_array($delivery->priority, ['urgent', 'express'])) {
                $this->autoAssignDriver($delivery);
            }

            // Send initial notification
            $this->notificationService->sendDeliveryCreated($delivery);

            DB::commit();
            return $delivery;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create delivery: ' . $e->getMessage());
            throw $e;
        }
    }

    public function assignDriver(Delivery $delivery, Driver $driver, ?ParkingLocation $parking = null): bool
    {
        DB::beginTransaction();
        try {
            // Verify driver availability
            if (!$driver->isAvailable()) {
                throw new \Exception('Driver is not available');
            }

            // Verify driver can handle the delivery
            if ($delivery->requires_cold_chain && !$driver->canHandleColdChain()) {
                throw new \Exception('Driver does not have cold storage capability');
            }

            if ($delivery->deliveryZone && !$driver->canDeliverToZone($delivery->deliveryZone)) {
                throw new \Exception('Driver cannot deliver to this zone');
            }

            // Assign parking if provided
            if ($parking) {
                if (!$parking->isAvailable($delivery->scheduled_date)) {
                    throw new \Exception('Parking location is not available');
                }
                
                if (!$parking->canAccommodateVehicle($driver->vehicle_type)) {
                    throw new \Exception('Parking location cannot accommodate vehicle type');
                }

                $delivery->parking_location_id = $parking->id;
            } elseif ($delivery->scheduled_date) {
                // Auto-assign parking based on requirements
                $parking = $this->findBestParking($delivery, $driver);
                if ($parking) {
                    $delivery->parking_location_id = $parking->id;
                }
            }

            // Update delivery
            $delivery->driver_id = $driver->id;
            $delivery->status = 'assigned';
            $delivery->save();

            // Update driver status
            $driver->update(['status' => 'on_delivery']);

            // Calculate and set route
            $this->optimizeRoute($delivery);

            // Send notifications
            $this->notificationService->sendDriverAssigned($delivery);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to assign driver: ' . $e->getMessage());
            return false;
        }
    }

    public function autoAssignDriver(Delivery $delivery): ?Driver
    {
        // Find the best available driver
        $drivers = Driver::where('status', 'active')
            ->where('is_verified', true)
            ->when($delivery->requires_cold_chain, function ($query) {
                $query->where('has_cold_storage', true);
            })
            ->get()
            ->filter(function ($driver) use ($delivery) {
                return $driver->isAvailable() && 
                    (!$delivery->deliveryZone || $driver->canDeliverToZone($delivery->deliveryZone));
            });

        if ($drivers->isEmpty()) {
            return null;
        }

        // Sort by proximity if delivery has pickup location
        if ($delivery->parkingLocation) {
            $drivers = $drivers->sortBy(function ($driver) use ($delivery) {
                return $driver->getDistanceFromPoint(
                    $delivery->parkingLocation->latitude ?? -33.7169,
                    $delivery->parkingLocation->longitude ?? 150.9050
                );
            });
        } else {
            // Sort by number of active deliveries (load balancing)
            $drivers = $drivers->sortBy(function ($driver) {
                return $driver->activeDeliveries()->count();
            });
        }

        $selectedDriver = $drivers->first();

        if ($selectedDriver && $this->assignDriver($delivery, $selectedDriver)) {
            return $selectedDriver;
        }

        return null;
    }

    public function updateDeliveryStatus(Delivery $delivery, string $status, array $data = []): bool
    {
        DB::beginTransaction();
        try {
            $oldStatus = $delivery->status;
            $delivery->status = $status;

            switch ($status) {
                case 'picked_up':
                    $delivery->pickup_time = now();
                    if (isset($data['parking_location_id'])) {
                        $delivery->parking_location_id = $data['parking_location_id'];
                    }
                    break;

                case 'in_transit':
                    if (!$delivery->pickup_time) {
                        $delivery->pickup_time = now();
                    }
                    break;

                case 'delivered':
                    $delivery->delivered_time = now();
                    if (isset($data['proof_type'])) {
                        $delivery->proof_type = $data['proof_type'];
                        $delivery->proof_signature = $data['proof_signature'] ?? null;
                        $delivery->proof_photo = $data['proof_photo'] ?? null;
                        $delivery->proof_pin = $data['proof_pin'] ?? null;
                        $delivery->received_by = $data['received_by'] ?? null;
                    }

                    // Update driver status
                    if ($delivery->driver) {
                        $activeDeliveries = $delivery->driver->activeDeliveries()
                            ->where('id', '!=', $delivery->id)
                            ->count();
                        
                        if ($activeDeliveries == 0) {
                            $delivery->driver->update(['status' => 'active']);
                        }
                    }
                    break;

                case 'failed':
                case 'cancelled':
                    if (isset($data['reason'])) {
                        $delivery->internal_notes = $data['reason'];
                    }
                    
                    // Release driver if assigned
                    if ($delivery->driver && $delivery->driver->status === 'on_delivery') {
                        $activeDeliveries = $delivery->driver->activeDeliveries()
                            ->where('id', '!=', $delivery->id)
                            ->count();
                        
                        if ($activeDeliveries == 0) {
                            $delivery->driver->update(['status' => 'active']);
                        }
                    }
                    break;
            }

            $delivery->save();

            // Send status notification
            $this->notificationService->sendStatusUpdate($delivery, $oldStatus, $status);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to update delivery status: ' . $e->getMessage());
            return false;
        }
    }

    public function recordProofOfDelivery(Delivery $delivery, array $proof): bool
    {
        return $this->updateDeliveryStatus($delivery, 'delivered', $proof);
    }

    public function reportDelay(Delivery $delivery, int $minutes, string $reason): void
    {
        $delivery->update([
            'delay_minutes' => $delivery->delay_minutes + $minutes,
            'delay_reason' => $reason
        ]);

        $this->notificationService->sendDelayNotification($delivery, $minutes, $reason);
    }

    public function optimizeRoute(Delivery $delivery): void
    {
        if (!$delivery->driver) {
            return;
        }

        // Get driver's other active deliveries
        $deliveries = $delivery->driver->activeDeliveries()
            ->with(['deliveryZone', 'parkingLocation'])
            ->get();

        if ($deliveries->count() <= 1) {
            return;
        }

        // Optimize route for all deliveries
        $optimizedRoute = $this->routeOptimizer->optimizeMultipleDeliveries($deliveries);

        // Update each delivery with its position in the route
        foreach ($optimizedRoute['deliveries'] as $index => $optimizedDelivery) {
            $del = $deliveries->find($optimizedDelivery['id']);
            if ($del) {
                $del->update([
                    'route_waypoints' => $optimizedDelivery['waypoints'] ?? [],
                    'total_distance_km' => $optimizedDelivery['distance'] ?? 0,
                    'estimated_minutes' => $optimizedDelivery['duration'] ?? 30
                ]);
            }
        }
    }

    protected function determineDeliveryZone(?string $address): ?DeliveryZone
    {
        if (!$address) {
            return null;
        }

        // Extract suburb from address (simple implementation)
        $suburbs = DeliveryZone::active()->pluck('suburbs')->flatten()->unique();
        
        foreach ($suburbs as $suburb) {
            if (stripos($address, $suburb) !== false) {
                return DeliveryZone::active()
                    ->whereJsonContains('suburbs', $suburb)
                    ->first();
            }
        }

        // Default to local zone if no match
        return DeliveryZone::where('code', 'SML')->first();
    }

    protected function requiresColdChain(Order $order): bool
    {
        // Check if any order items require cold storage
        return $order->items()->whereHas('product', function ($query) {
            $query->where('requires_cold_storage', true)
                ->orWhere('category', 'fresh_produce')
                ->orWhere('category', 'dairy')
                ->orWhere('category', 'meat')
                ->orWhere('category', 'seafood');
        })->exists();
    }

    protected function findBestParking(Delivery $delivery, Driver $driver): ?ParkingLocation
    {
        $query = ParkingLocation::available()
            ->forVehicleType($driver->vehicle_type);

        if ($delivery->requires_cold_chain) {
            $query->withColdStorage();
        }

        // Prefer loading docks for trucks
        if (in_array($driver->vehicle_type, ['truck', 'semi_trailer'])) {
            $parking = (clone $query)->loadingDocks()->first();
            if ($parking) {
                return $parking;
            }
        }

        return $query->first();
    }

    public function getBatchDeliveries(array $orderIds): Collection
    {
        return Delivery::whereIn('order_id', $orderIds)
            ->with(['order', 'driver', 'deliveryZone', 'parkingLocation'])
            ->get();
    }

    public function scheduleBatchDelivery(Collection $deliveries, Carbon $scheduledDate, ?Driver $driver = null): bool
    {
        DB::beginTransaction();
        try {
            $timeSlot = $scheduledDate->format('H:i') . '-' . $scheduledDate->copy()->addMinutes(30)->format('H:i');

            foreach ($deliveries as $delivery) {
                $delivery->update([
                    'scheduled_date' => $scheduledDate,
                    'time_slot' => $timeSlot,
                    'priority' => 'scheduled'
                ]);

                if ($driver) {
                    $this->assignDriver($delivery, $driver);
                }
            }

            // Optimize route for batch
            if ($driver) {
                $this->routeOptimizer->optimizeMultipleDeliveries($deliveries);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to schedule batch delivery: ' . $e->getMessage());
            return false;
        }
    }

    public function getDeliveryMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $deliveries = Delivery::whereBetween('created_at', [$startDate, $endDate])
            ->with(['driver', 'deliveryZone'])
            ->get();

        return [
            'total_deliveries' => $deliveries->count(),
            'completed' => $deliveries->where('status', 'delivered')->count(),
            'failed' => $deliveries->where('status', 'failed')->count(),
            'on_time' => $deliveries->filter(fn($d) => !$d->isLate())->count(),
            'average_delivery_time' => $deliveries->avg('estimated_minutes'),
            'total_distance' => $deliveries->sum('total_distance_km'),
            'total_revenue' => $deliveries->sum('delivery_fee'),
            'by_zone' => $deliveries->groupBy('delivery_zone_id')->map->count(),
            'by_priority' => $deliveries->groupBy('priority')->map->count(),
            'average_rating' => $deliveries->avg('delivery_rating'),
            'cold_chain_deliveries' => $deliveries->where('requires_cold_chain', true)->count()
        ];
    }
}