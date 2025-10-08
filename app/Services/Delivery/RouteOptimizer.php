<?php

namespace App\Services\Delivery;

use App\Models\Delivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RouteOptimizer
{
    protected string $googleMapsApiKey;
    protected array $marketLocation = [
        'lat' => -33.7169,
        'lng' => 150.9050,
        'address' => 'Sydney Markets, Flemington NSW 2140'
    ];

    public function __construct()
    {
        $this->googleMapsApiKey = config('services.google_maps.api_key', '');
    }

    public function optimizeRoute(Delivery $delivery): array
    {
        try {
            $origin = $this->getOrigin($delivery);
            $destination = $this->getDestination($delivery);

            if (!$origin || !$destination) {
                return $this->getDefaultRoute($delivery);
            }

            // Check cache first
            $cacheKey = 'route_' . md5($origin . $destination);
            $cachedRoute = Cache::get($cacheKey);
            
            if ($cachedRoute) {
                return $cachedRoute;
            }

            // Call Google Directions API
            $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => $origin,
                'destination' => $destination,
                'mode' => 'driving',
                'departure_time' => 'now',
                'traffic_model' => 'best_guess',
                'key' => $this->googleMapsApiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['routes'])) {
                    $route = $this->parseGoogleRoute($data['routes'][0]);
                    
                    // Cache for 1 hour
                    Cache::put($cacheKey, $route, 3600);
                    
                    return $route;
                }
            }

            return $this->getDefaultRoute($delivery);

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());
            return $this->getDefaultRoute($delivery);
        }
    }

    public function optimizeMultipleDeliveries(Collection $deliveries): array
    {
        if ($deliveries->isEmpty()) {
            return ['deliveries' => [], 'total_distance' => 0, 'total_duration' => 0];
        }

        // Sort deliveries by priority and scheduled time
        $sortedDeliveries = $deliveries->sortBy([
            ['priority', 'desc'],
            ['scheduled_date', 'asc']
        ]);

        // Use Google's Distance Matrix API for multiple destinations
        $waypoints = $this->getWaypoints($sortedDeliveries);
        
        if (count($waypoints) <= 1) {
            return $this->getSingleDeliveryRoute($sortedDeliveries->first());
        }

        try {
            // For multiple waypoints, use Traveling Salesman Problem approximation
            $optimizedOrder = $this->solveTSP($waypoints);
            
            return $this->buildOptimizedRoute($sortedDeliveries, $optimizedOrder);

        } catch (\Exception $e) {
            Log::error('Multiple delivery optimization failed: ' . $e->getMessage());
            return $this->getDefaultMultipleRoute($sortedDeliveries);
        }
    }

    protected function solveTSP(array $waypoints): array
    {
        if (count($waypoints) <= 10 && $this->googleMapsApiKey) {
            // Use Google's optimization for small sets
            return $this->googleOptimizeWaypoints($waypoints);
        }
        
        // Use nearest neighbor heuristic for larger sets
        return $this->nearestNeighborTSP($waypoints);
    }

    protected function googleOptimizeWaypoints(array $waypoints): array
    {
        try {
            $origin = $this->marketLocation['address'];
            $destination = end($waypoints)['address'];
            $intermediateWaypoints = array_slice($waypoints, 0, -1);

            $waypointString = implode('|', array_map(function ($wp) {
                return 'optimize:true|' . $wp['address'];
            }, $intermediateWaypoints));

            $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => $origin,
                'destination' => $destination,
                'waypoints' => $waypointString,
                'mode' => 'driving',
                'key' => $this->googleMapsApiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['routes'])) {
                    return $data['routes'][0]['waypoint_order'] ?? range(0, count($waypoints) - 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Google waypoint optimization failed: ' . $e->getMessage());
        }

        return range(0, count($waypoints) - 1);
    }

    protected function nearestNeighborTSP(array $waypoints): array
    {
        $n = count($waypoints);
        if ($n <= 1) {
            return [0];
        }

        $visited = array_fill(0, $n, false);
        $order = [];
        $current = 0; // Start from market (index 0)
        
        $visited[$current] = true;
        $order[] = $current;

        while (count($order) < $n) {
            $nearestIndex = -1;
            $nearestDistance = PHP_FLOAT_MAX;

            for ($i = 0; $i < $n; $i++) {
                if (!$visited[$i]) {
                    $distance = $this->calculateDistance(
                        $waypoints[$current]['lat'],
                        $waypoints[$current]['lng'],
                        $waypoints[$i]['lat'],
                        $waypoints[$i]['lng']
                    );

                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearestIndex = $i;
                    }
                }
            }

            if ($nearestIndex !== -1) {
                $visited[$nearestIndex] = true;
                $order[] = $nearestIndex;
                $current = $nearestIndex;
            } else {
                break;
            }
        }

        return $order;
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Haversine formula
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    protected function getOrigin(Delivery $delivery): ?string
    {
        if ($delivery->parkingLocation) {
            return $delivery->parkingLocation->full_location . ', Sydney Markets, Flemington NSW';
        }

        return $this->marketLocation['address'];
    }

    protected function getDestination(Delivery $delivery): ?string
    {
        return $delivery->delivery_address;
    }

    protected function getWaypoints(Collection $deliveries): array
    {
        $waypoints = [];

        // Add market as starting point
        $waypoints[] = [
            'id' => 'market',
            'lat' => $this->marketLocation['lat'],
            'lng' => $this->marketLocation['lng'],
            'address' => $this->marketLocation['address']
        ];

        foreach ($deliveries as $delivery) {
            if ($delivery->delivery_address) {
                // In production, you would geocode the address
                $coords = $this->geocodeAddress($delivery->delivery_address);
                
                $waypoints[] = [
                    'id' => $delivery->id,
                    'lat' => $coords['lat'] ?? $this->marketLocation['lat'],
                    'lng' => $coords['lng'] ?? $this->marketLocation['lng'],
                    'address' => $delivery->delivery_address
                ];
            }
        }

        return $waypoints;
    }

    protected function geocodeAddress(string $address): array
    {
        $cacheKey = 'geocode_' . md5($address);
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        try {
            if ($this->googleMapsApiKey) {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address . ', Sydney, NSW, Australia',
                    'key' => $this->googleMapsApiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        $location = $data['results'][0]['geometry']['location'];
                        $result = [
                            'lat' => $location['lat'],
                            'lng' => $location['lng']
                        ];
                        
                        // Cache for 7 days
                        Cache::put($cacheKey, $result, 604800);
                        
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Geocoding failed: ' . $e->getMessage());
        }

        // Return market location as fallback
        return [
            'lat' => $this->marketLocation['lat'],
            'lng' => $this->marketLocation['lng']
        ];
    }

    protected function parseGoogleRoute(array $route): array
    {
        $legs = $route['legs'][0] ?? [];
        
        return [
            'distance' => $legs['distance']['value'] ?? 0, // meters
            'distance_text' => $legs['distance']['text'] ?? '0 km',
            'duration' => $legs['duration']['value'] ?? 0, // seconds
            'duration_text' => $legs['duration']['text'] ?? '0 mins',
            'duration_in_traffic' => $legs['duration_in_traffic']['value'] ?? $legs['duration']['value'] ?? 0,
            'start_address' => $legs['start_address'] ?? '',
            'end_address' => $legs['end_address'] ?? '',
            'steps' => array_map(function ($step) {
                return [
                    'instruction' => strip_tags($step['html_instructions'] ?? ''),
                    'distance' => $step['distance']['text'] ?? '',
                    'duration' => $step['duration']['text'] ?? ''
                ];
            }, $legs['steps'] ?? []),
            'overview_polyline' => $route['overview_polyline']['points'] ?? '',
            'bounds' => $route['bounds'] ?? []
        ];
    }

    protected function buildOptimizedRoute(Collection $deliveries, array $order): array
    {
        $optimizedDeliveries = [];
        $totalDistance = 0;
        $totalDuration = 0;

        foreach ($order as $index) {
            if ($index === 0) continue; // Skip market location
            
            $delivery = $deliveries->skip($index - 1)->first();
            if ($delivery) {
                $route = $this->optimizeRoute($delivery);
                
                $optimizedDeliveries[] = [
                    'id' => $delivery->id,
                    'order' => $index,
                    'address' => $delivery->delivery_address,
                    'distance' => $route['distance'] ?? 0,
                    'duration' => $route['duration'] ?? 0,
                    'waypoints' => $route['steps'] ?? []
                ];
                
                $totalDistance += $route['distance'] ?? 0;
                $totalDuration += $route['duration'] ?? 0;
            }
        }

        return [
            'deliveries' => $optimizedDeliveries,
            'total_distance' => round($totalDistance / 1000, 2), // Convert to km
            'total_duration' => round($totalDuration / 60), // Convert to minutes
            'optimized' => true
        ];
    }

    protected function getDefaultRoute(Delivery $delivery): array
    {
        $zone = $delivery->deliveryZone;
        
        return [
            'distance' => 10000, // 10km default
            'distance_text' => '10 km',
            'duration' => 1800, // 30 minutes default
            'duration_text' => '30 mins',
            'duration_in_traffic' => 2100, // 35 minutes with traffic
            'start_address' => $this->marketLocation['address'],
            'end_address' => $delivery->delivery_address,
            'steps' => [],
            'overview_polyline' => '',
            'bounds' => [],
            'estimated_minutes' => $zone ? $zone->estimated_minutes : 30
        ];
    }

    protected function getDefaultMultipleRoute(Collection $deliveries): array
    {
        $routes = [];
        $totalDistance = 0;
        $totalDuration = 0;

        foreach ($deliveries as $index => $delivery) {
            $route = $this->getDefaultRoute($delivery);
            $routes[] = [
                'id' => $delivery->id,
                'order' => $index + 1,
                'address' => $delivery->delivery_address,
                'distance' => $route['distance'],
                'duration' => $route['duration'],
                'waypoints' => []
            ];
            
            $totalDistance += $route['distance'];
            $totalDuration += $route['duration'];
        }

        return [
            'deliveries' => $routes,
            'total_distance' => round($totalDistance / 1000, 2),
            'total_duration' => round($totalDuration / 60),
            'optimized' => false
        ];
    }

    protected function getSingleDeliveryRoute(Delivery $delivery): array
    {
        $route = $this->optimizeRoute($delivery);
        
        return [
            'deliveries' => [[
                'id' => $delivery->id,
                'order' => 1,
                'address' => $delivery->delivery_address,
                'distance' => $route['distance'] ?? 0,
                'duration' => $route['duration'] ?? 0,
                'waypoints' => $route['steps'] ?? []
            ]],
            'total_distance' => round(($route['distance'] ?? 0) / 1000, 2),
            'total_duration' => round(($route['duration'] ?? 0) / 60),
            'optimized' => true
        ];
    }
}