<?php

namespace App\Services\Delivery;

use App\Models\Delivery;
use App\Models\Driver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LocationTracker
{
    protected NotificationService $notificationService;
    protected int $staleThreshold = 300; // 5 minutes
    protected int $nearbyThreshold = 500; // 500 meters
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function updateDriverLocation(Driver $driver, float $latitude, float $longitude, array $metadata = []): void
    {
        try {
            // Update driver location
            $driver->updateLocation($latitude, $longitude);

            // Store in cache for real-time tracking
            $this->cacheLocation('driver', $driver->id, $latitude, $longitude, $metadata);

            // Check for nearby deliveries
            $this->checkNearbyDeliveries($driver, $latitude, $longitude);

            // Update active deliveries
            $driver->activeDeliveries->each(function ($delivery) use ($latitude, $longitude, $metadata) {
                $this->updateDeliveryLocation($delivery, $latitude, $longitude, $metadata);
            });

            // Log location history
            $this->logLocationHistory('driver', $driver->id, $latitude, $longitude, $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to update driver location: ' . $e->getMessage());
        }
    }

    public function updateDeliveryLocation(Delivery $delivery, float $latitude, float $longitude, array $metadata = []): void
    {
        try {
            // Update delivery location
            $delivery->updateLocation($latitude, $longitude);

            // Store in cache
            $this->cacheLocation('delivery', $delivery->id, $latitude, $longitude, $metadata);

            // Check proximity to destination
            $this->checkDestinationProximity($delivery, $latitude, $longitude);

            // Update temperature if cold chain
            if ($delivery->requires_cold_chain && isset($metadata['temperature'])) {
                $delivery->addTemperatureReading($metadata['temperature']);
            }

            // Check for geofence events
            $this->checkGeofenceEvents($delivery, $latitude, $longitude);

            // Log location history
            $this->logLocationHistory('delivery', $delivery->id, $latitude, $longitude, $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to update delivery location: ' . $e->getMessage());
        }
    }

    protected function cacheLocation(string $type, int $id, float $latitude, float $longitude, array $metadata = []): void
    {
        $key = "{$type}_location_{$id}";
        $data = [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata
        ];

        // Cache for 10 minutes
        Cache::put($key, $data, 600);

        // Also update real-time channel for live tracking
        $this->broadcastLocationUpdate($type, $id, $data);
    }

    protected function broadcastLocationUpdate(string $type, int $id, array $data): void
    {
        try {
            // In production, this would use Laravel Broadcasting (Pusher/WebSockets)
            event(new \App\Events\LocationUpdated($type, $id, $data));
        } catch (\Exception $e) {
            // Silent fail for broadcasting
        }
    }

    public function getLocation(string $type, int $id): ?array
    {
        $key = "{$type}_location_{$id}";
        return Cache::get($key);
    }

    public function getMultipleLocations(string $type, array $ids): array
    {
        $locations = [];
        
        foreach ($ids as $id) {
            $location = $this->getLocation($type, $id);
            if ($location) {
                $locations[$id] = $location;
            }
        }

        return $locations;
    }

    public function isLocationStale(string $type, int $id): bool
    {
        $location = $this->getLocation($type, $id);
        
        if (!$location) {
            return true;
        }

        $timestamp = Carbon::parse($location['timestamp']);
        return $timestamp->diffInSeconds(now()) > $this->staleThreshold;
    }

    protected function checkNearbyDeliveries(Driver $driver, float $latitude, float $longitude): void
    {
        // Get driver's upcoming deliveries
        $upcomingDeliveries = $driver->activeDeliveries()
            ->whereIn('status', ['assigned', 'picked_up'])
            ->get();

        foreach ($upcomingDeliveries as $delivery) {
            if ($delivery->status === 'assigned') {
                // Check proximity to pickup location (market)
                $marketDistance = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    -33.7169, // Sydney Markets latitude
                    150.9050  // Sydney Markets longitude
                );

                if ($marketDistance < $this->nearbyThreshold) {
                    $this->notificationService->sendDriverArrivingAtPickup($delivery);
                }
            } elseif ($delivery->status === 'picked_up') {
                // Check proximity to delivery address
                $this->checkDestinationProximity($delivery, $latitude, $longitude);
            }
        }
    }

    protected function checkDestinationProximity(Delivery $delivery, float $latitude, float $longitude): void
    {
        // Get delivery destination coordinates
        $destination = $this->getDeliveryDestinationCoordinates($delivery);
        
        if (!$destination) {
            return;
        }

        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $destination['lat'],
            $destination['lng']
        );

        // Within 1km - send "arriving soon" notification
        if ($distance < 1000 && !$this->hasRecentNotification($delivery, 'near_arrival')) {
            $this->notificationService->sendNearArrival($delivery);
            $this->markNotificationSent($delivery, 'near_arrival');
        }

        // Within 100m - prepare for delivery
        if ($distance < 100 && !$this->hasRecentNotification($delivery, 'arrived')) {
            $this->notificationService->sendDriverArrived($delivery);
            $this->markNotificationSent($delivery, 'arrived');
        }
    }

    protected function checkGeofenceEvents(Delivery $delivery, float $latitude, float $longitude): void
    {
        if (!$delivery->deliveryZone) {
            return;
        }

        $boundaries = $delivery->deliveryZone->boundaries ?? [];
        
        if (empty($boundaries)) {
            return;
        }

        // Check if point is inside polygon (simplified - in production use proper polygon containment algorithm)
        $insideZone = $this->isPointInPolygon($latitude, $longitude, $boundaries);

        $cacheKey = "delivery_{$delivery->id}_in_zone";
        $wasInZone = Cache::get($cacheKey, false);

        if ($insideZone && !$wasInZone) {
            // Entered zone
            $this->logGeofenceEvent($delivery, 'entered_zone', $delivery->deliveryZone->name);
            Cache::put($cacheKey, true, 3600);
        } elseif (!$insideZone && $wasInZone) {
            // Exited zone
            $this->logGeofenceEvent($delivery, 'exited_zone', $delivery->deliveryZone->name);
            Cache::put($cacheKey, false, 3600);
        }
    }

    protected function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (empty($polygon)) {
            return false;
        }

        // Ray-casting algorithm
        $inside = false;
        $p1x = $polygon[0]['lat'] ?? 0;
        $p1y = $polygon[0]['lng'] ?? 0;

        $n = count($polygon);
        for ($i = 0; $i < $n; $i++) {
            $p2x = $polygon[($i + 1) % $n]['lat'] ?? 0;
            $p2y = $polygon[($i + 1) % $n]['lng'] ?? 0;

            if ($lng > min($p1y, $p2y)) {
                if ($lng <= max($p1y, $p2y)) {
                    if ($lat <= max($p1x, $p2x)) {
                        if ($p1y != $p2y) {
                            $xinters = ($lng - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                        }
                        if ($p1x == $p2x || $lat <= $xinters) {
                            $inside = !$inside;
                        }
                    }
                }
            }
            $p1x = $p2x;
            $p1y = $p2y;
        }

        return $inside;
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Haversine formula
        $earthRadius = 6371000; // meters

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    protected function getDeliveryDestinationCoordinates(Delivery $delivery): ?array
    {
        // In production, this would use geocoding or stored coordinates
        $cacheKey = "delivery_destination_{$delivery->id}";
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        // For now, return zone center coordinates
        if ($delivery->deliveryZone) {
            $coordinates = match($delivery->deliveryZone->code) {
                'SML' => ['lat' => -33.7169, 'lng' => 150.9050],
                'IW' => ['lat' => -33.8688, 'lng' => 151.0709],
                'ES' => ['lat' => -33.8988, 'lng' => 151.2427],
                'NS' => ['lat' => -33.8089, 'lng' => 151.1822],
                'WS' => ['lat' => -33.8148, 'lng' => 150.9914],
                default => null
            };

            if ($coordinates) {
                Cache::put($cacheKey, $coordinates, 3600);
                return $coordinates;
            }
        }

        return null;
    }

    protected function logLocationHistory(string $type, int $id, float $latitude, float $longitude, array $metadata = []): void
    {
        // Store location history for analytics and replay
        $historyKey = "{$type}_history_{$id}_" . date('Y-m-d');
        $history = Cache::get($historyKey, []);
        
        $history[] = [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata
        ];

        // Keep only last 1000 points per day
        if (count($history) > 1000) {
            $history = array_slice($history, -1000);
        }

        // Cache for 7 days
        Cache::put($historyKey, $history, 604800);
    }

    protected function logGeofenceEvent(Delivery $delivery, string $event, string $zone): void
    {
        $log = $delivery->notification_log ?? [];
        $log[] = [
            'event' => 'geofence',
            'type' => $event,
            'zone' => $zone,
            'timestamp' => now()->toIso8601String()
        ];
        
        $delivery->update(['notification_log' => $log]);
    }

    protected function hasRecentNotification(Delivery $delivery, string $type): bool
    {
        $cacheKey = "delivery_{$delivery->id}_notification_{$type}";
        return Cache::has($cacheKey);
    }

    protected function markNotificationSent(Delivery $delivery, string $type): void
    {
        $cacheKey = "delivery_{$delivery->id}_notification_{$type}";
        // Prevent duplicate notifications for 30 minutes
        Cache::put($cacheKey, true, 1800);
    }

    public function getLocationHistory(string $type, int $id, Carbon $date): array
    {
        $historyKey = "{$type}_history_{$id}_" . $date->format('Y-m-d');
        return Cache::get($historyKey, []);
    }

    public function calculateRouteProgress(Delivery $delivery): array
    {
        if (!$delivery->route_waypoints || !$delivery->driver) {
            return ['progress' => 0, 'remaining_distance' => 0, 'eta' => null];
        }

        $currentLocation = $this->getLocation('driver', $delivery->driver->id);
        
        if (!$currentLocation) {
            return ['progress' => 0, 'remaining_distance' => 0, 'eta' => null];
        }

        $totalDistance = $delivery->total_distance_km * 1000; // Convert to meters
        $destination = $this->getDeliveryDestinationCoordinates($delivery);
        
        if (!$destination) {
            return ['progress' => 0, 'remaining_distance' => $totalDistance, 'eta' => null];
        }

        $remainingDistance = $this->calculateDistance(
            $currentLocation['lat'],
            $currentLocation['lng'],
            $destination['lat'],
            $destination['lng']
        );

        $progress = $totalDistance > 0 ? 
            max(0, min(100, (($totalDistance - $remainingDistance) / $totalDistance) * 100)) : 0;

        // Calculate ETA based on average speed (40 km/h in city)
        $averageSpeed = 40; // km/h
        $remainingTimeMinutes = ($remainingDistance / 1000) / $averageSpeed * 60;
        $eta = now()->addMinutes($remainingTimeMinutes);

        return [
            'progress' => round($progress, 2),
            'remaining_distance' => round($remainingDistance / 1000, 2), // Convert to km
            'remaining_time' => round($remainingTimeMinutes),
            'eta' => $eta->format('Y-m-d H:i:s'),
            'current_location' => $currentLocation,
            'destination' => $destination
        ];
    }
}