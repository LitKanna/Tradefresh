<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupBooking;
use App\Models\DeliveryRoute;
use App\Models\DeliveryStop;
use App\Models\PackingSlip;
use App\Models\ShippingLabel;
use App\Services\Pickup\PickupBookingService;
use App\Services\Delivery\RouteOptimizationService;
use App\Events\OrderReadyForPickup;
use App\Events\OrderPickedUp;
use App\Events\OrderInTransit;
use App\Events\OrderDelivered;
use App\Jobs\GeneratePickingList;
use App\Jobs\GeneratePackingSlip;
use App\Jobs\GenerateShippingLabel;
use App\Jobs\NotifyDriverOfNewDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class FulfillmentService
{
    protected PickupBookingService $pickupService;
    protected RouteOptimizationService $routeService;

    public function __construct(
        PickupBookingService $pickupService,
        RouteOptimizationService $routeService
    ) {
        $this->pickupService = $pickupService;
        $this->routeService = $routeService;
    }

    /**
     * Process order for fulfillment
     */
    public function processOrderFulfillment(Order $order, array $options = []): array
    {
        return DB::transaction(function () use ($order, $options) {
            $fulfillmentType = $options['fulfillment_type'] ?? $order->fulfillment_type;
            
            if ($fulfillmentType === Order::FULFILLMENT_TYPE_PICKUP) {
                return $this->processPickupFulfillment($order, $options);
            } else {
                return $this->processDeliveryFulfillment($order, $options);
            }
        });
    }

    /**
     * Process pickup fulfillment
     */
    protected function processPickupFulfillment(Order $order, array $options): array
    {
        // Create or update pickup booking
        if (!$order->pickup_booking_id) {
            $booking = $this->pickupService->createBooking([
                'user_id' => $order->buyerBusiness->primary_contact_id,
                'order_id' => $order->id,
                'bay_id' => $options['bay_id'] ?? null,
                'pickup_date' => $options['pickup_date'] ?? Carbon::tomorrow()->toDateString(),
                'pickup_time' => $options['pickup_time'] ?? '10:00',
                'duration_minutes' => $this->estimatePickupDuration($order),
                'vehicle_type' => $options['vehicle_type'] ?? null,
                'vehicle_registration' => $options['vehicle_registration'] ?? null,
                'driver_name' => $options['driver_name'] ?? null,
                'driver_phone' => $options['driver_phone'] ?? null,
                'items_to_pickup' => $this->generatePickupItemsList($order),
                'auto_confirm' => $options['auto_confirm'] ?? false,
            ]);
            
            $order->update(['pickup_booking_id' => $booking->id]);
        } else {
            $booking = $order->pickupBooking;
            
            if (isset($options['update_booking']) && $options['update_booking']) {
                $booking = $this->pickupService->updateBooking($booking, $options);
            }
        }
        
        // Generate documents
        $documents = $this->generatePickupDocuments($order);
        
        return [
            'success' => true,
            'booking' => $booking,
            'documents' => $documents,
            'message' => 'Pickup booking created successfully'
        ];
    }

    /**
     * Process delivery fulfillment
     */
    protected function processDeliveryFulfillment(Order $order, array $options): array
    {
        // Validate delivery address
        if (!$order->delivery_address_id && !isset($options['delivery_address'])) {
            throw new \Exception('Delivery address is required');
        }
        
        // Create delivery stop
        $stop = $this->createDeliveryStop($order, $options);
        
        // Find or create optimal route
        $route = $this->assignToOptimalRoute($stop, $options);
        
        // Update order with route
        $order->update([
            'delivery_route_id' => $route->id,
            'expected_delivery_date' => $route->route_date . ' ' . $stop->estimated_arrival_time
        ]);
        
        // Generate documents
        $documents = $this->generateDeliveryDocuments($order);
        
        // Notify driver if route is already assigned
        if ($route->driver_id && $route->status === 'assigned') {
            NotifyDriverOfNewDelivery::dispatch($route->driver, $stop);
        }
        
        return [
            'success' => true,
            'route' => $route,
            'stop' => $stop,
            'documents' => $documents,
            'message' => 'Order added to delivery route successfully'
        ];
    }

    /**
     * Create delivery stop for order
     */
    protected function createDeliveryStop(Order $order, array $options): DeliveryStop
    {
        $address = $order->deliveryAddress ?? $options['delivery_address'];
        
        return DeliveryStop::create([
            'order_id' => $order->id,
            'customer_name' => $order->buyerBusiness->business_name,
            'customer_phone' => $order->buyerBusiness->phone,
            'delivery_address' => $address->full_address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'priority' => $order->is_urgent ? 'urgent' : 'normal',
            'time_window_start' => $options['time_window_start'] ?? '09:00',
            'time_window_end' => $options['time_window_end'] ?? '17:00',
            'service_time_minutes' => $this->estimateServiceTime($order),
            'total_weight_kg' => $this->calculateOrderWeight($order),
            'total_volume_m3' => $this->calculateOrderVolume($order),
            'number_of_packages' => $order->items->count(),
            'requires_signature' => $options['requires_signature'] ?? true,
            'requires_refrigeration' => $this->requiresRefrigeration($order),
            'special_instructions' => $options['delivery_instructions'] ?? $order->delivery_notes,
            'status' => 'pending'
        ]);
    }

    /**
     * Assign stop to optimal route
     */
    protected function assignToOptimalRoute(DeliveryStop $stop, array $options): DeliveryRoute
    {
        $deliveryDate = Carbon::parse($options['delivery_date'] ?? Carbon::tomorrow());
        
        // Find existing routes for the date
        $existingRoutes = DeliveryRoute::where('route_date', $deliveryDate->toDateString())
            ->where('status', '!=', 'completed')
            ->whereRaw('pending_stops < max_stops')
            ->get();
        
        // Find best route based on location and capacity
        $bestRoute = null;
        $minDetour = PHP_INT_MAX;
        
        foreach ($existingRoutes as $route) {
            // Check capacity
            if (!$this->canAccommodateStop($route, $stop)) {
                continue;
            }
            
            // Calculate detour distance
            $detour = $this->calculateDetourDistance($route, $stop);
            
            if ($detour < $minDetour) {
                $minDetour = $detour;
                $bestRoute = $route;
            }
        }
        
        // Create new route if no suitable route found
        if (!$bestRoute) {
            $bestRoute = $this->createNewDeliveryRoute($deliveryDate, $stop);
        } else {
            // Add stop to existing route
            $stop->update([
                'route_id' => $bestRoute->id,
                'stop_sequence' => $bestRoute->stops()->count() + 1
            ]);
            
            // Update route totals
            $bestRoute->increment('total_stops');
            $bestRoute->increment('pending_stops');
            $bestRoute->increment('total_weight_kg', $stop->total_weight_kg);
            $bestRoute->increment('total_volume_m3', $stop->total_volume_m3);
            
            // Re-optimize route
            $this->routeService->optimizeRoute($bestRoute);
        }
        
        return $bestRoute;
    }

    /**
     * Generate picking list for warehouse
     */
    public function generatePickingList(Order $order): string
    {
        $items = $order->items()->with(['product', 'product.location'])->get();
        
        // Group by warehouse location for efficient picking
        $itemsByLocation = $items->groupBy(function ($item) {
            return $item->product->location->zone ?? 'GENERAL';
        })->sortKeys();
        
        $pickingList = [
            'order_number' => $order->order_number,
            'vendor' => $order->vendor->business_name,
            'created_at' => now(),
            'picker_name' => '________________',
            'start_time' => '________________',
            'end_time' => '________________',
            'zones' => []
        ];
        
        foreach ($itemsByLocation as $zone => $zoneItems) {
            $pickingList['zones'][$zone] = [
                'name' => $zone,
                'items' => $zoneItems->map(function ($item) {
                    return [
                        'location' => $item->product->location->code ?? 'N/A',
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit_of_measure,
                        'picked' => false,
                        'picked_quantity' => null,
                        'notes' => $item->notes
                    ];
                })->toArray()
            ];
        }
        
        // Generate PDF
        $pdf = Pdf::loadView('documents.picking-list', compact('pickingList'));
        $filename = "picking-lists/PL-{$order->order_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());
        
        // Dispatch async job for complex orders
        if ($items->count() > 20) {
            GeneratePickingList::dispatch($order);
        }
        
        return $filename;
    }

    /**
     * Generate packing slip
     */
    public function generatePackingSlip(Order $order): string
    {
        $packingSlip = PackingSlip::create([
            'order_id' => $order->id,
            'slip_number' => 'PS-' . $order->order_number,
            'packed_by' => auth()->user()->name ?? 'System',
            'packed_at' => now(),
            'total_packages' => $this->calculatePackageCount($order),
            'total_weight' => $this->calculateOrderWeight($order),
            'notes' => $order->packing_notes
        ]);
        
        // Generate barcode for tracking
        $barcode = $this->generateTrackingBarcode($packingSlip);
        
        $data = [
            'order' => $order,
            'packingSlip' => $packingSlip,
            'items' => $order->items,
            'barcode' => $barcode,
            'buyer' => $order->buyerBusiness,
            'vendor' => $order->vendor
        ];
        
        $pdf = Pdf::loadView('documents.packing-slip', $data);
        $filename = "packing-slips/{$packingSlip->slip_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());
        
        $packingSlip->update(['document_path' => $filename]);
        
        return $filename;
    }

    /**
     * Generate shipping label
     */
    public function generateShippingLabel(Order $order): array
    {
        $labels = [];
        $packageCount = $this->calculatePackageCount($order);
        
        for ($i = 1; $i <= $packageCount; $i++) {
            $label = ShippingLabel::create([
                'order_id' => $order->id,
                'package_number' => $i,
                'total_packages' => $packageCount,
                'tracking_number' => $this->generateTrackingNumber($order, $i),
                'carrier' => 'Sydney Markets Delivery',
                'service_type' => $order->is_urgent ? 'Express' : 'Standard',
                'weight_kg' => $this->calculateOrderWeight($order) / $packageCount,
                'dimensions' => $this->estimatePackageDimensions($order, $i),
                'from_address' => $this->getVendorAddress($order->vendor),
                'to_address' => $order->deliveryAddress->full_address ?? 'Pickup at Sydney Markets',
                'created_by' => auth()->id()
            ]);
            
            // Generate label with QR code
            $qrCode = $this->generateLabelQRCode($label);
            
            $data = [
                'label' => $label,
                'order' => $order,
                'qrCode' => $qrCode,
                'packageInfo' => "Package {$i} of {$packageCount}"
            ];
            
            $pdf = Pdf::loadView('documents.shipping-label', $data)
                ->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inch label
            
            $filename = "shipping-labels/SL-{$label->tracking_number}.pdf";
            Storage::disk('public')->put($filename, $pdf->output());
            
            $label->update(['label_path' => $filename]);
            $labels[] = $label;
        }
        
        return $labels;
    }

    /**
     * Handle order modifications
     */
    public function handleOrderModification(Order $order, array $changes): array
    {
        if (!$order->canBeModified()) {
            throw new \Exception('Order cannot be modified in current status');
        }
        
        return DB::transaction(function () use ($order, $changes) {
            $results = [];
            
            // Handle item additions
            if (isset($changes['add_items'])) {
                foreach ($changes['add_items'] as $item) {
                    $orderItem = $order->items()->create($item);
                    $results['added_items'][] = $orderItem;
                }
            }
            
            // Handle item removals
            if (isset($changes['remove_items'])) {
                foreach ($changes['remove_items'] as $itemId) {
                    $item = $order->items()->find($itemId);
                    if ($item) {
                        $item->delete();
                        $results['removed_items'][] = $itemId;
                    }
                }
            }
            
            // Handle quantity changes
            if (isset($changes['update_quantities'])) {
                foreach ($changes['update_quantities'] as $itemId => $quantity) {
                    $item = $order->items()->find($itemId);
                    if ($item) {
                        $item->update(['quantity' => $quantity]);
                        $results['updated_items'][] = $item;
                    }
                }
            }
            
            // Recalculate totals
            $order->calculateTotals();
            
            // Update fulfillment if needed
            if ($order->pickup_booking_id) {
                $this->updatePickupBooking($order);
            }
            
            if ($order->delivery_route_id) {
                $this->updateDeliveryStop($order);
            }
            
            // Log modification
            activity()
                ->performedOn($order)
                ->withProperties($changes)
                ->log('Order modified');
            
            return $results;
        });
    }

    /**
     * Handle order cancellation
     */
    public function handleOrderCancellation(Order $order, string $reason, int $cancelledBy): bool
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status');
        }
        
        return DB::transaction(function () use ($order, $reason, $cancelledBy) {
            // Update order status
            $order->updateStatus(Order::STATUS_CANCELLED, $reason, $cancelledBy);
            
            // Release reserved stock
            $order->releaseStock();
            
            // Cancel pickup booking if exists
            if ($order->pickup_booking_id) {
                $this->pickupService->cancelBooking(
                    $order->pickupBooking,
                    $reason,
                    $cancelledBy
                );
            }
            
            // Remove from delivery route if exists
            if ($order->delivery_route_id) {
                $stop = DeliveryStop::where('order_id', $order->id)->first();
                if ($stop) {
                    $stop->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $reason
                    ]);
                    
                    // Update route totals
                    $route = $order->deliveryRoute;
                    $route->decrement('total_stops');
                    $route->decrement('pending_stops');
                    $route->decrement('total_weight_kg', $stop->total_weight_kg);
                    $route->decrement('total_volume_m3', $stop->total_volume_m3);
                    
                    // Re-optimize route
                    $this->routeService->optimizeRoute($route);
                }
            }
            
            // Process refund if payment was made
            if ($order->paid_amount > 0) {
                // Refund logic would go here
            }
            
            // Send cancellation notifications
            event(new \App\Events\OrderCancelled($order, $reason));
            
            return true;
        });
    }

    /**
     * Mark order as ready for pickup
     */
    public function markReadyForPickup(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->updateStatus(Order::STATUS_READY_FOR_PICKUP);
            
            // Generate final documents
            $this->generatePickupDocuments($order);
            
            // Send notification
            event(new OrderReadyForPickup($order));
            
            // Update pickup booking status
            if ($order->pickup_booking_id) {
                $order->pickupBooking->update(['order_ready' => true]);
            }
        });
    }

    /**
     * Process order pickup completion
     */
    public function processPickupCompletion(Order $order, array $data = []): void
    {
        DB::transaction(function () use ($order, $data) {
            // Update order status
            $order->updateStatus(Order::STATUS_IN_TRANSIT);
            
            // Complete pickup booking
            if ($order->pickup_booking_id) {
                $this->pickupService->completePickup($order->pickupBooking);
            }
            
            // Update items as picked
            foreach ($order->items as $item) {
                $pickedQuantity = $data['picked_quantities'][$item->id] ?? $item->quantity;
                $item->markAsPicked($pickedQuantity);
            }
            
            // Send notification
            event(new OrderPickedUp($order));
        });
    }

    /**
     * Process delivery completion
     */
    public function processDeliveryCompletion(Order $order, array $data): void
    {
        DB::transaction(function () use ($order, $data) {
            // Update order status
            $order->updateStatus(Order::STATUS_DELIVERED);
            
            // Update delivery stop
            $stop = DeliveryStop::where('order_id', $order->id)->first();
            if ($stop) {
                $stop->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'signature' => $data['signature'] ?? null,
                    'photo_proof' => $data['photo_proof'] ?? null,
                    'delivery_notes' => $data['notes'] ?? null,
                    'recipient_name' => $data['recipient_name'] ?? null
                ]);
            }
            
            // Update items as delivered
            foreach ($order->items as $item) {
                $deliveredQuantity = $data['delivered_quantities'][$item->id] ?? $item->quantity;
                $item->markAsDelivered($deliveredQuantity);
            }
            
            // Send notification
            event(new OrderDelivered($order));
            
            // Auto-complete order after 24 hours
            \App\Jobs\AutoCompleteOrder::dispatch($order)->delay(now()->addDay());
        });
    }

    // Helper methods
    
    protected function estimatePickupDuration(Order $order): int
    {
        $baseTime = 15; // Base 15 minutes
        $itemCount = $order->items->count();
        $additionalTime = min($itemCount * 2, 30); // 2 min per item, max 30 min
        return $baseTime + $additionalTime;
    }

    protected function estimateServiceTime(Order $order): int
    {
        $baseTime = 10; // Base 10 minutes
        $packageCount = $this->calculatePackageCount($order);
        return $baseTime + ($packageCount * 2);
    }

    protected function calculateOrderWeight(Order $order): float
    {
        return $order->items->sum(function ($item) {
            return ($item->weight ?? 1) * $item->quantity;
        });
    }

    protected function calculateOrderVolume(Order $order): float
    {
        return $order->items->sum(function ($item) {
            if ($item->dimensions) {
                $dims = $item->dimensions;
                return ($dims['length'] * $dims['width'] * $dims['height'] / 1000000) * $item->quantity;
            }
            return 0.01 * $item->quantity; // Default 0.01 m³ per item
        });
    }

    protected function calculatePackageCount(Order $order): int
    {
        // Logic to determine number of packages based on items
        $totalItems = $order->items->sum('quantity');
        return max(1, ceil($totalItems / 10)); // Assume 10 items per package
    }

    protected function requiresRefrigeration(Order $order): bool
    {
        return $order->items->contains(function ($item) {
            return $item->product->requires_refrigeration ?? false;
        });
    }

    protected function generatePickupItemsList(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit_of_measure
            ];
        })->toArray();
    }

    protected function generatePickupDocuments(Order $order): array
    {
        return [
            'picking_list' => $this->generatePickingList($order),
            'packing_slip' => $this->generatePackingSlip($order),
            'invoice' => $this->generateInvoice($order)
        ];
    }

    protected function generateDeliveryDocuments(Order $order): array
    {
        $documents = [
            'packing_slip' => $this->generatePackingSlip($order),
            'shipping_labels' => $this->generateShippingLabel($order),
            'invoice' => $this->generateInvoice($order)
        ];
        
        if ($order->requires_pod) {
            $documents['pod_form'] = $this->generatePODForm($order);
        }
        
        return $documents;
    }

    protected function generateInvoice(Order $order): string
    {
        // Invoice generation logic
        return "invoices/INV-{$order->order_number}.pdf";
    }

    protected function generatePODForm(Order $order): string
    {
        // POD form generation logic
        return "pod-forms/POD-{$order->order_number}.pdf";
    }

    protected function generateTrackingNumber(Order $order, int $packageNumber): string
    {
        return 'SM' . $order->id . str_pad($packageNumber, 3, '0', STR_PAD_LEFT) . strtoupper(str()->random(6));
    }

    protected function generateTrackingBarcode(PackingSlip $slip): string
    {
        // Generate barcode image
        return "barcodes/{$slip->slip_number}.png";
    }

    protected function generateLabelQRCode(ShippingLabel $label): string
    {
        $qrData = [
            'tracking' => $label->tracking_number,
            'order' => $label->order->order_number,
            'package' => "{$label->package_number}/{$label->total_packages}"
        ];
        
        return base64_encode(QrCode::format('png')->size(200)->generate(json_encode($qrData)));
    }

    protected function getVendorAddress($vendor): string
    {
        return $vendor->vendorProfile->pickup_address ?? 'Sydney Markets, Building ' . ($vendor->id % 10 + 1);
    }

    protected function estimatePackageDimensions(Order $order, int $packageNumber): array
    {
        // Estimate based on items
        return [
            'length' => 40,
            'width' => 30,
            'height' => 20,
            'unit' => 'cm'
        ];
    }

    protected function canAccommodateStop(DeliveryRoute $route, DeliveryStop $stop): bool
    {
        // Check weight capacity
        if ($route->vehicle && $route->vehicle->max_weight_kg) {
            if (($route->total_weight_kg + $stop->total_weight_kg) > $route->vehicle->max_weight_kg) {
                return false;
            }
        }
        
        // Check volume capacity
        if ($route->vehicle && $route->vehicle->max_volume_m3) {
            if (($route->total_volume_m3 + $stop->total_volume_m3) > $route->vehicle->max_volume_m3) {
                return false;
            }
        }
        
        // Check refrigeration requirement
        if ($stop->requires_refrigeration && !($route->vehicle->has_refrigeration ?? false)) {
            return false;
        }
        
        return true;
    }

    protected function calculateDetourDistance(DeliveryRoute $route, DeliveryStop $newStop): float
    {
        // Simplified calculation - would use actual routing API in production
        $lastStop = $route->stops()->orderBy('stop_sequence', 'desc')->first();
        
        if (!$lastStop) {
            return 0;
        }
        
        return $this->calculateDistance(
            $lastStop->latitude,
            $lastStop->longitude,
            $newStop->latitude,
            $newStop->longitude
        );
    }

    protected function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371;
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    protected function createNewDeliveryRoute(Carbon $date, DeliveryStop $stop): DeliveryRoute
    {
        $route = DeliveryRoute::create([
            'route_date' => $date->toDateString(),
            'route_type' => $stop->priority === 'urgent' ? 'express' : 'standard',
            'status' => 'planned',
            'planned_start_time' => '08:00',
            'planned_end_time' => '17:00',
            'total_stops' => 1,
            'pending_stops' => 1,
            'total_weight_kg' => $stop->total_weight_kg,
            'total_volume_m3' => $stop->total_volume_m3,
            'zones_covered' => [$this->determineZone($stop->latitude, $stop->longitude)],
            'max_stops' => 20
        ]);
        
        $stop->update([
            'route_id' => $route->id,
            'stop_sequence' => 1
        ]);
        
        return $route;
    }

    protected function determineZone($latitude, $longitude): string
    {
        // Simplified zone determination
        if ($latitude > -33.85) return 'NORTH';
        if ($latitude < -33.95) return 'SOUTH';
        if ($longitude > 151.22) return 'EAST';
        if ($longitude < 151.18) return 'WEST';
        return 'CENTRAL';
    }

    protected function updatePickupBooking(Order $order): void
    {
        if ($order->pickupBooking) {
            $order->pickupBooking->update([
                'items_to_pickup' => $this->generatePickupItemsList($order),
                'duration_minutes' => $this->estimatePickupDuration($order)
            ]);
        }
    }

    protected function updateDeliveryStop(Order $order): void
    {
        $stop = DeliveryStop::where('order_id', $order->id)->first();
        if ($stop) {
            $stop->update([
                'total_weight_kg' => $this->calculateOrderWeight($order),
                'total_volume_m3' => $this->calculateOrderVolume($order),
                'number_of_packages' => $order->items->count(),
                'service_time_minutes' => $this->estimateServiceTime($order)
            ]);
            
            // Re-optimize route if needed
            if ($stop->route) {
                $this->routeService->optimizeRoute($stop->route);
            }
        }
    }
}