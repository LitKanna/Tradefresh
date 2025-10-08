<?php

namespace App\Services\Delivery;

use App\Models\DeliveryRoute;
use App\Models\DeliveryStop;
use App\Models\DeliveryDriver;
use App\Events\DriverLocationUpdated;
use App\Events\DeliveryStatusChanged;
use App\Jobs\SendDeliveryNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeliveryTrackingService
{
    protected $notificationChannels = ['sms', 'email', 'push'];
    protected $trackingUrlBase;
    
    public function __construct()
    {
        $this->trackingUrlBase = config('app.url') . '/track';
    }
    
    /**
     * Update driver location
     */
    public function updateDriverLocation(
        DeliveryDriver $driver,
        float $latitude,
        float $longitude,
        array $metadata = []
    ): void {
        // Update driver's current location
        $driver->updateLocation($latitude, $longitude, $metadata['zone'] ?? null);
        
        // Update active route if exists
        $activeRoute = $driver->activeRoute;
        if ($activeRoute) {
            $this->updateRouteProgress($activeRoute, $latitude, $longitude);
        }
        
        // Broadcast location update for real-time tracking
        broadcast(new DriverLocationUpdated($driver, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => now(),
            'speed' => $metadata['speed'] ?? null,
            'heading' => $metadata['heading'] ?? null,
        ]));
        
        // Check for geofence events
        $this->checkGeofenceEvents($driver, $latitude, $longitude);
    }
    
    /**
     * Update route progress based on driver location
     */
    protected function updateRouteProgress(
        DeliveryRoute $route,
        float $latitude,
        float $longitude
    ): void {
        $currentStop = $route->getCurrentStop();
        $nextStop = $route->getNextStop();
        
        if ($nextStop) {
            // Calculate distance to next stop
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                $nextStop->latitude, $nextStop->longitude
            );
            
            // Auto-arrive if within 100 meters
            if ($distance < 0.1 && $nextStop->status === 'en_route') {
                $this->arriveAtStop($nextStop);
            }
            
            // Update ETA for upcoming stops
            $this->updateUpcomingETAs($route, $latitude, $longitude);
        }
    }
    
    /**
     * Mark arrival at stop
     */
    public function arriveAtStop(DeliveryStop $stop): void
    {
        try {
            $stop->markArrived();
            
            // Send arrival notification
            $this->sendArrivalNotification($stop);
            
            // Broadcast status update
            broadcast(new DeliveryStatusChanged($stop, 'arrived'));
            
            // Log arrival
            Log::info('Driver arrived at stop', [
                'stop_id' => $stop->id,
                'reference' => $stop->stop_reference,
                'time' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark arrival', [
                'stop_id' => $stop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Complete delivery at stop
     */
    public function completeDelivery(
        DeliveryStop $stop,
        array $completionData
    ): void {
        try {
            // Process proof of delivery
            if (isset($completionData['signature_data'])) {
                $completionData['signature_url'] = $this->saveSignature(
                    $completionData['signature_data']
                );
            }
            
            if (isset($completionData['photo_data'])) {
                $completionData['photo_url'] = $this->savePhoto(
                    $completionData['photo_data']
                );
            }
            
            // Complete the stop
            $stop->complete($completionData);
            
            // Send completion notification
            $this->sendCompletionNotification($stop);
            
            // Broadcast status update
            broadcast(new DeliveryStatusChanged($stop, 'completed'));
            
            // Update route progress
            $stop->route->updateProgress();
            
            // Process COD if applicable
            if ($stop->cod_amount > 0 && $completionData['cod_collected'] ?? false) {
                $this->processCODCollection($stop, $completionData['cod_amount'] ?? 0);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to complete delivery', [
                'stop_id' => $stop->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Fail delivery at stop
     */
    public function failDelivery(
        DeliveryStop $stop,
        string $reason,
        string $notes = null
    ): void {
        try {
            $stop->fail($reason, $notes);
            
            // Send failure notification
            $this->sendFailureNotification($stop);
            
            // Broadcast status update
            broadcast(new DeliveryStatusChanged($stop, 'failed'));
            
            // Update route progress
            $stop->route->updateProgress();
            
            // Create incident report if critical
            if ($this->isCriticalFailure($reason)) {
                $this->createIncidentReport($stop, $reason, $notes);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to mark delivery as failed', [
                'stop_id' => $stop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Generate tracking URL for stop
     */
    public function generateTrackingUrl(DeliveryStop $stop): string
    {
        $token = $this->generateTrackingToken($stop);
        
        return "{$this->trackingUrlBase}/delivery/{$stop->stop_reference}?token={$token}";
    }
    
    /**
     * Generate secure tracking token
     */
    protected function generateTrackingToken(DeliveryStop $stop): string
    {
        $data = [
            'stop_id' => $stop->id,
            'reference' => $stop->stop_reference,
            'expires' => now()->addDays(30)->timestamp,
        ];
        
        $token = base64_encode(json_encode($data));
        
        // Cache token for validation
        Cache::put(
            "tracking_token_{$stop->stop_reference}",
            $token,
            now()->addDays(30)
        );
        
        return $token;
    }
    
    /**
     * Get tracking information for public display
     */
    public function getTrackingInfo(string $reference, string $token = null): ?array
    {
        $stop = DeliveryStop::where('stop_reference', $reference)->first();
        
        if (!$stop) {
            return null;
        }
        
        // Validate token if provided
        if ($token) {
            $cachedToken = Cache::get("tracking_token_{$reference}");
            if ($token !== $cachedToken) {
                return null;
            }
        }
        
        $route = $stop->route;
        $driver = $route ? $route->driver : null;
        
        $info = [
            'reference' => $stop->stop_reference,
            'status' => $this->getPublicStatus($stop->status),
            'status_description' => $this->getStatusDescription($stop->status),
            'recipient' => $this->maskName($stop->recipient_name),
            'address' => $this->maskAddress($stop->delivery_address),
            'estimated_delivery' => $stop->estimated_arrival_time,
            'time_window' => [
                'start' => $stop->time_window_start,
                'end' => $stop->time_window_end,
            ],
        ];
        
        // Add completion details if delivered
        if ($stop->status === 'completed') {
            $info['delivered'] = [
                'at' => $stop->completed_at,
                'to' => $stop->delivered_to,
                'location' => $stop->delivery_location,
                'proof' => [
                    'signature' => !empty($stop->signature_url),
                    'photo' => !empty($stop->photo_url),
                ],
            ];
        }
        
        // Add failure details if failed
        if ($stop->status === 'failed') {
            $info['failed'] = [
                'reason' => $stop->failure_reason,
                'next_attempt' => $stop->next_attempt_date,
                'can_reschedule' => $stop->can_reschedule,
            ];
        }
        
        // Add driver location if in progress
        if ($driver && $route->isInProgress()) {
            $info['driver'] = [
                'location' => [
                    'updated_at' => $driver->last_location_update,
                    'distance_km' => $this->calculateDistance(
                        $driver->current_latitude,
                        $driver->current_longitude,
                        $stop->latitude,
                        $stop->longitude
                    ),
                ],
                'stops_remaining' => $route->pending_stops,
            ];
        }
        
        // Add timeline events
        $info['timeline'] = $this->getDeliveryTimeline($stop);
        
        return $info;
    }
    
    /**
     * Send arrival notification
     */
    protected function sendArrivalNotification(DeliveryStop $stop): void
    {
        if (!$stop->recipient_phone && !$stop->recipient_email) {
            return;
        }
        
        $message = $this->buildArrivalMessage($stop);
        
        if ($stop->recipient_phone && !$stop->sms_sent) {
            SendDeliveryNotification::dispatch($stop, 'sms', $message)
                ->onQueue('notifications');
            
            $stop->update([
                'sms_sent' => true,
                'sms_sent_at' => now(),
            ]);
        }
        
        if ($stop->recipient_email) {
            SendDeliveryNotification::dispatch($stop, 'email', [
                'subject' => 'Your delivery is arriving soon',
                'message' => $message,
                'tracking_url' => $stop->tracking_url,
            ])->onQueue('notifications');
        }
    }
    
    /**
     * Send completion notification
     */
    protected function sendCompletionNotification(DeliveryStop $stop): void
    {
        if (!$stop->recipient_email) {
            return;
        }
        
        $message = $this->buildCompletionMessage($stop);
        
        SendDeliveryNotification::dispatch($stop, 'email', [
            'subject' => 'Your delivery has been completed',
            'message' => $message,
            'tracking_url' => $stop->tracking_url,
            'proof_of_delivery' => [
                'delivered_to' => $stop->delivered_to,
                'location' => $stop->delivery_location,
                'time' => $stop->completed_at,
                'signature' => $stop->signature_url,
                'photo' => $stop->photo_url,
            ],
        ])->onQueue('notifications');
        
        $stop->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }
    
    /**
     * Send failure notification
     */
    protected function sendFailureNotification(DeliveryStop $stop): void
    {
        if (!$stop->recipient_phone && !$stop->recipient_email) {
            return;
        }
        
        $message = $this->buildFailureMessage($stop);
        
        if ($stop->recipient_phone) {
            SendDeliveryNotification::dispatch($stop, 'sms', $message)
                ->onQueue('notifications');
        }
        
        if ($stop->recipient_email) {
            SendDeliveryNotification::dispatch($stop, 'email', [
                'subject' => 'Delivery attempt unsuccessful',
                'message' => $message,
                'tracking_url' => $stop->tracking_url,
                'reschedule_url' => $this->getRescheduleUrl($stop),
            ])->onQueue('notifications');
        }
    }
    
    /**
     * Update ETAs for upcoming stops
     */
    protected function updateUpcomingETAs(
        DeliveryRoute $route,
        float $currentLat,
        float $currentLng
    ): void {
        $currentTime = now();
        $currentLocation = ['lat' => $currentLat, 'lng' => $currentLng];
        
        $upcomingStops = $route->stops()
            ->whereIn('status', ['pending', 'en_route'])
            ->orderBy('stop_sequence')
            ->get();
        
        foreach ($upcomingStops as $stop) {
            // Calculate travel time from current location
            $distance = $this->calculateDistance(
                $currentLocation['lat'],
                $currentLocation['lng'],
                $stop->latitude,
                $stop->longitude
            );
            
            $travelTime = $this->estimateTravelTime($distance);
            $estimatedArrival = $currentTime->copy()->addMinutes($travelTime);
            
            // Update if significantly different (> 5 minutes)
            if (!$stop->estimated_arrival_time ||
                abs($stop->estimated_arrival_time->diffInMinutes($estimatedArrival)) > 5) {
                
                $stop->update(['estimated_arrival_time' => $estimatedArrival]);
                
                // Notify if running late
                if ($stop->time_window_end &&
                    $estimatedArrival->gt(Carbon::parse($stop->time_window_end))) {
                    $this->sendLateNotification($stop, $estimatedArrival);
                }
            }
            
            // Update current location for next iteration
            $currentLocation = ['lat' => $stop->latitude, 'lng' => $stop->longitude];
            $currentTime = $estimatedArrival->copy()->addMinutes($stop->service_time_minutes);
        }
    }
    
    /**
     * Check geofence events
     */
    protected function checkGeofenceEvents(
        DeliveryDriver $driver,
        float $latitude,
        float $longitude
    ): void {
        // Check depot geofence
        $depotDistance = $this->calculateDistance(
            $latitude, $longitude,
            $this->getDepotLocation()['lat'],
            $this->getDepotLocation()['lng']
        );
        
        if ($depotDistance < 0.5) { // Within 500m of depot
            $this->handleDepotEntry($driver);
        }
        
        // Check delivery zone geofences
        $zone = $this->determineZone($latitude, $longitude);
        if ($zone !== $driver->current_zone) {
            $this->handleZoneChange($driver, $zone);
        }
    }
    
    /**
     * Save signature image
     */
    protected function saveSignature(string $signatureData): string
    {
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
        $filename = 'signatures/' . Str::uuid() . '.png';
        
        \Storage::disk('public')->put($filename, $data);
        
        return $filename;
    }
    
    /**
     * Save photo
     */
    protected function savePhoto(string $photoData): string
    {
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photoData));
        $filename = 'delivery-photos/' . Str::uuid() . '.jpg';
        
        \Storage::disk('public')->put($filename, $data);
        
        return $filename;
    }
    
    /**
     * Process COD collection
     */
    protected function processCODCollection(DeliveryStop $stop, float $amount): void
    {
        // Record COD collection
        $stop->update([
            'cod_collected' => true,
        ]);
        
        // Log transaction
        Log::info('COD collected', [
            'stop_id' => $stop->id,
            'amount' => $amount,
            'driver_id' => $stop->route->driver_id,
        ]);
        
        // Create accounting entry
        // Implementation depends on accounting system
    }
    
    /**
     * Check if failure reason is critical
     */
    protected function isCriticalFailure(string $reason): bool
    {
        $criticalReasons = [
            'damaged_package',
            'lost_package',
            'wrong_address',
            'refused_dangerous',
            'accident',
            'theft',
        ];
        
        return in_array($reason, $criticalReasons);
    }
    
    /**
     * Create incident report
     */
    protected function createIncidentReport(
        DeliveryStop $stop,
        string $reason,
        ?string $notes
    ): void {
        // Implementation for incident reporting system
        Log::error('Critical delivery failure', [
            'stop_id' => $stop->id,
            'reason' => $reason,
            'notes' => $notes,
            'route_id' => $stop->route_id,
            'driver_id' => $stop->route->driver_id,
        ]);
    }
    
    /**
     * Build arrival message
     */
    protected function buildArrivalMessage(DeliveryStop $stop): string
    {
        $driver = $stop->route->driver;
        
        return sprintf(
            "Your delivery from Sydney Markets is arriving soon! Driver %s is on the way. Track: %s",
            $driver->full_name,
            $stop->tracking_url
        );
    }
    
    /**
     * Build completion message
     */
    protected function buildCompletionMessage(DeliveryStop $stop): string
    {
        return sprintf(
            "Your delivery has been completed at %s. Delivered to: %s at %s. Thank you for your order!",
            $stop->completed_at->format('g:i A'),
            $stop->delivered_to,
            $stop->delivery_location
        );
    }
    
    /**
     * Build failure message
     */
    protected function buildFailureMessage(DeliveryStop $stop): string
    {
        $message = sprintf(
            "We were unable to complete your delivery. Reason: %s.",
            $stop->failure_reason
        );
        
        if ($stop->can_reschedule && $stop->next_attempt_date) {
            $message .= sprintf(
                " We will try again on %s.",
                $stop->next_attempt_date->format('M d')
            );
        }
        
        return $message;
    }
    
    /**
     * Get public-friendly status
     */
    protected function getPublicStatus(string $status): string
    {
        return match($status) {
            'pending' => 'scheduled',
            'en_route' => 'on_the_way',
            'arrived' => 'driver_arrived',
            'in_progress' => 'delivering',
            'completed' => 'delivered',
            'failed' => 'attempted',
            'rescheduled' => 'rescheduled',
            'cancelled' => 'cancelled',
            default => 'processing',
        };
    }
    
    /**
     * Get status description
     */
    protected function getStatusDescription(string $status): string
    {
        return match($status) {
            'pending' => 'Your delivery is scheduled and will be dispatched soon.',
            'en_route' => 'Your driver is on the way to your location.',
            'arrived' => 'The driver has arrived at your location.',
            'in_progress' => 'Your delivery is being completed.',
            'completed' => 'Your delivery has been successfully completed.',
            'failed' => 'Delivery could not be completed. We will contact you to reschedule.',
            'rescheduled' => 'Your delivery has been rescheduled for another date.',
            'cancelled' => 'This delivery has been cancelled.',
            default => 'Your delivery is being processed.',
        };
    }
    
    /**
     * Get delivery timeline
     */
    protected function getDeliveryTimeline(DeliveryStop $stop): array
    {
        $timeline = [];
        
        // Order placed
        if ($stop->created_at) {
            $timeline[] = [
                'event' => 'scheduled',
                'description' => 'Delivery scheduled',
                'timestamp' => $stop->created_at,
            ];
        }
        
        // Route started
        if ($stop->route && $stop->route->actual_start_time) {
            $timeline[] = [
                'event' => 'dispatched',
                'description' => 'Driver started route',
                'timestamp' => $stop->route->actual_start_time,
            ];
        }
        
        // En route
        if ($stop->status !== 'pending' && $stop->updated_at) {
            $timeline[] = [
                'event' => 'on_the_way',
                'description' => 'Driver is on the way',
                'timestamp' => $stop->updated_at,
            ];
        }
        
        // Arrived
        if ($stop->actual_arrival_time) {
            $timeline[] = [
                'event' => 'arrived',
                'description' => 'Driver arrived',
                'timestamp' => $stop->actual_arrival_time,
            ];
        }
        
        // Completed or failed
        if ($stop->completed_at) {
            $timeline[] = [
                'event' => 'delivered',
                'description' => 'Delivery completed',
                'timestamp' => $stop->completed_at,
            ];
        } elseif ($stop->status === 'failed') {
            $timeline[] = [
                'event' => 'attempted',
                'description' => 'Delivery attempted',
                'timestamp' => $stop->updated_at,
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Mask sensitive information
     */
    protected function maskName(string $name): string
    {
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            $parts[0] = substr($parts[0], 0, 1) . str_repeat('*', strlen($parts[0]) - 1);
        }
        return implode(' ', $parts);
    }
    
    protected function maskAddress(string $address): string
    {
        // Show only suburb and postcode
        if (preg_match('/(.+),\s*([^,]+)\s+(\d{4})/', $address, $matches)) {
            return '*****, ' . $matches[2] . ' ' . $matches[3];
        }
        return $address;
    }
    
    /**
     * Calculate distance between coordinates
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // km
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Estimate travel time based on distance
     */
    protected function estimateTravelTime(float $distance): int
    {
        $avgSpeed = 30; // km/h in city
        return (int) (($distance / $avgSpeed) * 60); // minutes
    }
    
    /**
     * Get depot location
     */
    protected function getDepotLocation(): array
    {
        return [
            'lat' => config('delivery.depot.latitude', -33.8688),
            'lng' => config('delivery.depot.longitude', 151.2093),
        ];
    }
    
    /**
     * Determine zone from coordinates
     */
    protected function determineZone(float $latitude, float $longitude): string
    {
        // Simplified zone determination
        // In production, this would use actual zone boundaries
        return 'ZONE_A';
    }
    
    /**
     * Handle depot entry
     */
    protected function handleDepotEntry(DeliveryDriver $driver): void
    {
        Log::info('Driver entered depot', [
            'driver_id' => $driver->id,
            'time' => now(),
        ]);
    }
    
    /**
     * Handle zone change
     */
    protected function handleZoneChange(DeliveryDriver $driver, string $newZone): void
    {
        $driver->update(['current_zone' => $newZone]);
        
        Log::info('Driver changed zone', [
            'driver_id' => $driver->id,
            'from_zone' => $driver->current_zone,
            'to_zone' => $newZone,
            'time' => now(),
        ]);
    }
    
    /**
     * Get reschedule URL
     */
    protected function getRescheduleUrl(DeliveryStop $stop): string
    {
        return route('delivery.reschedule', ['reference' => $stop->stop_reference]);
    }
    
    /**
     * Send late notification
     */
    protected function sendLateNotification(DeliveryStop $stop, Carbon $newETA): void
    {
        if ($stop->recipient_phone) {
            $message = sprintf(
                "Your delivery is running late. New estimated arrival: %s. We apologize for the delay.",
                $newETA->format('g:i A')
            );
            
            SendDeliveryNotification::dispatch($stop, 'sms', $message)
                ->onQueue('notifications');
        }
    }
}