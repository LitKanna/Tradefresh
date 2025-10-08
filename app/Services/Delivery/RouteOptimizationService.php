<?php

namespace App\Services\Delivery;

use App\Models\DeliveryRoute;
use App\Models\DeliveryStop;
use App\Models\DeliveryDriver;
use App\Models\PickupZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RouteOptimizationService
{
    protected $distanceMatrix = [];
    protected $timeMatrix = [];
    protected $maxIterations = 10000;
    protected $temperatureStart = 100;
    protected $coolingRate = 0.995;
    
    // Sydney Markets specific configuration
    protected $depotLocation = [
        'lat' => -33.8688, // Sydney Markets location
        'lng' => 151.2093,
        'address' => 'Sydney Markets, Sydney NSW',
    ];
    
    protected $trafficPatterns = [
        'peak_morning' => ['06:00', '09:00'],
        'peak_evening' => ['16:00', '19:00'],
        'off_peak' => ['10:00', '15:00'],
        'night' => ['20:00', '05:00'],
    ];

    /**
     * Optimize routes for a given date
     */
    public function optimizeRoutesForDate(Carbon $date, array $options = []): Collection
    {
        $stops = $this->getUnassignedStops($date);
        $drivers = $this->getAvailableDrivers($date);
        
        if ($stops->isEmpty() || $drivers->isEmpty()) {
            return collect();
        }

        // Group stops by priority and zone
        $groupedStops = $this->groupStopsByPriorityAndZone($stops);
        
        // Create optimized routes
        $routes = collect();
        
        foreach ($groupedStops as $priority => $zoneGroups) {
            foreach ($zoneGroups as $zone => $zoneStops) {
                $route = $this->createOptimizedRoute(
                    $zoneStops,
                    $drivers,
                    $date,
                    $priority,
                    $zone,
                    $options
                );
                
                if ($route) {
                    $routes->push($route);
                }
            }
        }
        
        return $routes;
    }

    /**
     * Optimize a single route using TSP solver
     */
    public function optimizeRoute(DeliveryRoute $route): DeliveryRoute
    {
        $startTime = microtime(true);
        
        $stops = $route->stops()->orderBy('stop_sequence')->get();
        
        if ($stops->count() <= 2) {
            // No optimization needed for 1-2 stops
            return $route;
        }
        
        // Build distance and time matrices
        $this->buildMatrices($stops);
        
        // Choose optimization method based on number of stops
        if ($stops->count() <= 10) {
            // Use exact solution for small routes
            $optimizedSequence = $this->bruteForceOptimization($stops);
        } elseif ($stops->count() <= 25) {
            // Use 2-opt for medium routes
            $optimizedSequence = $this->twoOptOptimization($stops);
        } else {
            // Use simulated annealing for large routes
            $optimizedSequence = $this->simulatedAnnealingOptimization($stops);
        }
        
        // Calculate optimization score
        $originalDistance = $this->calculateRouteDistance($stops->pluck('id')->toArray());
        $optimizedDistance = $this->calculateRouteDistance($optimizedSequence);
        $optimizationScore = (($originalDistance - $optimizedDistance) / $originalDistance) * 100;
        
        // Update route with optimized sequence
        $route->update([
            'optimized_sequence' => $optimizedSequence,
            'original_sequence' => $stops->pluck('id')->toArray(),
            'optimization_score' => max(0, $optimizationScore),
            'optimization_method' => $this->getOptimizationMethod($stops->count()),
            'optimization_time_ms' => (microtime(true) - $startTime) * 1000,
            'total_distance_km' => $optimizedDistance,
        ]);
        
        // Resequence stops
        $route->resequenceStops($optimizedSequence);
        
        // Update estimated arrival times
        $this->updateEstimatedArrivalTimes($route);
        
        return $route;
    }

    /**
     * Build distance and time matrices for stops
     */
    protected function buildMatrices(Collection $stops): void
    {
        // Add depot as first location
        $locations = collect([
            [
                'id' => 'depot',
                'lat' => $this->depotLocation['lat'],
                'lng' => $this->depotLocation['lng'],
            ]
        ]);
        
        $locations = $locations->merge(
            $stops->map(function ($stop) {
                return [
                    'id' => $stop->id,
                    'lat' => $stop->latitude,
                    'lng' => $stop->longitude,
                ];
            })
        );
        
        // Check cache first
        $cacheKey = 'distance_matrix_' . md5($locations->toJson());
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            $this->distanceMatrix = $cached['distance'];
            $this->timeMatrix = $cached['time'];
            return;
        }
        
        // Build matrices
        foreach ($locations as $from) {
            foreach ($locations as $to) {
                if ($from['id'] === $to['id']) {
                    $this->distanceMatrix[$from['id']][$to['id']] = 0;
                    $this->timeMatrix[$from['id']][$to['id']] = 0;
                } else {
                    $distance = $this->calculateDistance(
                        $from['lat'], $from['lng'],
                        $to['lat'], $to['lng']
                    );
                    
                    $time = $this->estimateTravelTime($distance);
                    
                    $this->distanceMatrix[$from['id']][$to['id']] = $distance;
                    $this->timeMatrix[$from['id']][$to['id']] = $time;
                }
            }
        }
        
        // Cache for 15 minutes
        Cache::put($cacheKey, [
            'distance' => $this->distanceMatrix,
            'time' => $this->timeMatrix,
        ], 900);
    }

    /**
     * Brute force optimization for small routes (exact solution)
     */
    protected function bruteForceOptimization(Collection $stops): array
    {
        $stopIds = $stops->pluck('id')->toArray();
        $n = count($stopIds);
        
        if ($n > 10) {
            throw new \Exception('Brute force only suitable for <= 10 stops');
        }
        
        $bestSequence = $stopIds;
        $bestDistance = $this->calculateRouteDistance($stopIds);
        
        // Generate all permutations
        $permutations = $this->generatePermutations($stopIds);
        
        foreach ($permutations as $sequence) {
            $distance = $this->calculateRouteDistance($sequence);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestSequence = $sequence;
            }
        }
        
        return $bestSequence;
    }

    /**
     * 2-opt optimization for medium routes
     */
    protected function twoOptOptimization(Collection $stops): array
    {
        $sequence = $stops->pluck('id')->toArray();
        $improved = true;
        
        while ($improved) {
            $improved = false;
            $n = count($sequence);
            
            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 2; $j < $n; $j++) {
                    // Calculate current distance
                    $currentDistance = 0;
                    if ($i > 0) {
                        $currentDistance += $this->distanceMatrix[$sequence[$i - 1]][$sequence[$i]];
                    } else {
                        $currentDistance += $this->distanceMatrix['depot'][$sequence[$i]];
                    }
                    $currentDistance += $this->distanceMatrix[$sequence[$j - 1]][$sequence[$j]];
                    
                    // Calculate new distance after swap
                    $newDistance = 0;
                    if ($i > 0) {
                        $newDistance += $this->distanceMatrix[$sequence[$i - 1]][$sequence[$j - 1]];
                    } else {
                        $newDistance += $this->distanceMatrix['depot'][$sequence[$j - 1]];
                    }
                    $newDistance += $this->distanceMatrix[$sequence[$i]][$sequence[$j]];
                    
                    // If improvement found, reverse the segment
                    if ($newDistance < $currentDistance) {
                        $this->reverseSegment($sequence, $i, $j - 1);
                        $improved = true;
                    }
                }
            }
        }
        
        return $sequence;
    }

    /**
     * Simulated annealing optimization for large routes
     */
    protected function simulatedAnnealingOptimization(Collection $stops): array
    {
        $currentSequence = $stops->pluck('id')->toArray();
        $currentDistance = $this->calculateRouteDistance($currentSequence);
        
        $bestSequence = $currentSequence;
        $bestDistance = $currentDistance;
        
        $temperature = $this->temperatureStart;
        
        for ($iteration = 0; $iteration < $this->maxIterations; $iteration++) {
            // Generate neighbor solution
            $newSequence = $this->generateNeighbor($currentSequence);
            $newDistance = $this->calculateRouteDistance($newSequence);
            
            // Calculate acceptance probability
            $delta = $newDistance - $currentDistance;
            $acceptanceProbability = $delta < 0 ? 1 : exp(-$delta / $temperature);
            
            // Accept or reject new solution
            if (rand() / getrandmax() < $acceptanceProbability) {
                $currentSequence = $newSequence;
                $currentDistance = $newDistance;
                
                // Update best solution if improved
                if ($currentDistance < $bestDistance) {
                    $bestSequence = $currentSequence;
                    $bestDistance = $currentDistance;
                }
            }
            
            // Cool down
            $temperature *= $this->coolingRate;
            
            // Early termination if temperature is too low
            if ($temperature < 0.01) {
                break;
            }
        }
        
        return $bestSequence;
    }

    /**
     * Calculate total distance for a route sequence
     */
    protected function calculateRouteDistance(array $sequence): float
    {
        $distance = 0;
        $n = count($sequence);
        
        // From depot to first stop
        $distance += $this->distanceMatrix['depot'][$sequence[0]];
        
        // Between stops
        for ($i = 0; $i < $n - 1; $i++) {
            $distance += $this->distanceMatrix[$sequence[$i]][$sequence[$i + 1]];
        }
        
        // From last stop back to depot
        $distance += $this->distanceMatrix[$sequence[$n - 1]]['depot'];
        
        return $distance;
    }

    /**
     * Calculate distance between two coordinates
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
     * Estimate travel time based on distance and traffic
     */
    protected function estimateTravelTime(float $distance, Carbon $time = null): int
    {
        $time = $time ?? now();
        $baseSpeed = 40; // km/h base speed
        
        // Adjust for traffic patterns
        $hour = $time->format('H:i');
        
        if ($this->isInTimeRange($hour, $this->trafficPatterns['peak_morning']) ||
            $this->isInTimeRange($hour, $this->trafficPatterns['peak_evening'])) {
            $speed = $baseSpeed * 0.6; // 60% speed during peak
        } elseif ($this->isInTimeRange($hour, $this->trafficPatterns['night'])) {
            $speed = $baseSpeed * 1.2; // 120% speed at night
        } else {
            $speed = $baseSpeed;
        }
        
        return (int) (($distance / $speed) * 60); // Convert to minutes
    }

    /**
     * Generate all permutations of an array
     */
    protected function generatePermutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }
        
        $permutations = [];
        
        foreach ($items as $key => $item) {
            $remaining = $items;
            array_splice($remaining, $key, 1);
            
            foreach ($this->generatePermutations($remaining) as $permutation) {
                array_unshift($permutation, $item);
                $permutations[] = $permutation;
            }
        }
        
        return $permutations;
    }

    /**
     * Reverse a segment of the sequence
     */
    protected function reverseSegment(array &$sequence, int $start, int $end): void
    {
        while ($start < $end) {
            $temp = $sequence[$start];
            $sequence[$start] = $sequence[$end];
            $sequence[$end] = $temp;
            $start++;
            $end--;
        }
    }

    /**
     * Generate neighbor solution for simulated annealing
     */
    protected function generateNeighbor(array $sequence): array
    {
        $neighbor = $sequence;
        $n = count($neighbor);
        
        // Random swap of two stops
        $i = rand(0, $n - 1);
        $j = rand(0, $n - 1);
        
        while ($i === $j) {
            $j = rand(0, $n - 1);
        }
        
        $temp = $neighbor[$i];
        $neighbor[$i] = $neighbor[$j];
        $neighbor[$j] = $temp;
        
        return $neighbor;
    }

    /**
     * Update estimated arrival times for route stops
     */
    protected function updateEstimatedArrivalTimes(DeliveryRoute $route): void
    {
        $currentTime = Carbon::parse($route->route_date . ' ' . $route->planned_start_time);
        $previousStopId = 'depot';
        
        foreach ($route->orderedStops as $stop) {
            // Add travel time from previous stop
            $travelTime = $this->timeMatrix[$previousStopId][$stop->id] ?? 15;
            $currentTime->addMinutes($travelTime);
            
            // Update stop with estimated arrival
            $stop->update([
                'estimated_arrival_time' => $currentTime->copy(),
                'distance_from_previous_km' => $this->distanceMatrix[$previousStopId][$stop->id] ?? 0,
                'travel_time_minutes' => $travelTime,
            ]);
            
            // Add service time for next calculation
            $currentTime->addMinutes($stop->service_time_minutes);
            
            $previousStopId = $stop->id;
        }
        
        // Update route's planned end time
        $route->update([
            'planned_end_time' => $currentTime->format('H:i:s'),
            'estimated_duration_minutes' => $currentTime->diffInMinutes(
                Carbon::parse($route->route_date . ' ' . $route->planned_start_time)
            ),
        ]);
    }

    /**
     * Get unassigned stops for a date
     */
    protected function getUnassignedStops(Carbon $date): Collection
    {
        return DeliveryStop::whereNull('route_id')
            ->where('status', 'pending')
            ->whereDate('created_at', '<=', $date)
            ->orderBy('priority')
            ->orderBy('time_window_start')
            ->get();
    }

    /**
     * Get available drivers for a date
     */
    protected function getAvailableDrivers(Carbon $date): Collection
    {
        $dayOfWeek = strtolower($date->format('D'));
        
        return DeliveryDriver::available()
            ->whereJsonContains('working_days', $dayOfWeek)
            ->whereDoesntHave('routes', function ($query) use ($date) {
                $query->whereDate('route_date', $date)
                    ->whereIn('status', ['assigned', 'in_progress']);
            })
            ->orderByDesc('average_rating')
            ->orderByDesc('on_time_percentage')
            ->get();
    }

    /**
     * Group stops by priority and zone
     */
    protected function groupStopsByPriorityAndZone(Collection $stops): array
    {
        $grouped = [];
        
        foreach ($stops as $stop) {
            $zone = $this->determineZone($stop->latitude, $stop->longitude);
            $priority = $stop->priority;
            
            if (!isset($grouped[$priority])) {
                $grouped[$priority] = [];
            }
            
            if (!isset($grouped[$priority][$zone])) {
                $grouped[$priority][$zone] = collect();
            }
            
            $grouped[$priority][$zone]->push($stop);
        }
        
        // Sort by priority (urgent first)
        ksort($grouped);
        
        return $grouped;
    }

    /**
     * Determine zone for coordinates
     */
    protected function determineZone($latitude, $longitude): string
    {
        // Implementation would use actual Sydney zone boundaries
        // This is a simplified version
        
        $zones = [
            'CBD' => [
                'lat_min' => -33.88,
                'lat_max' => -33.86,
                'lng_min' => 151.20,
                'lng_max' => 151.22,
            ],
            'INNER_WEST' => [
                'lat_min' => -33.90,
                'lat_max' => -33.85,
                'lng_min' => 151.15,
                'lng_max' => 151.20,
            ],
            'EASTERN' => [
                'lat_min' => -33.95,
                'lat_max' => -33.85,
                'lng_min' => 151.22,
                'lng_max' => 151.28,
            ],
            'NORTHERN' => [
                'lat_min' => -33.85,
                'lat_max' => -33.70,
                'lng_min' => 151.15,
                'lng_max' => 151.25,
            ],
            'SOUTHERN' => [
                'lat_min' => -34.05,
                'lat_max' => -33.90,
                'lng_min' => 151.10,
                'lng_max' => 151.20,
            ],
            'WESTERN' => [
                'lat_min' => -33.95,
                'lat_max' => -33.75,
                'lng_min' => 150.90,
                'lng_max' => 151.15,
            ],
        ];
        
        foreach ($zones as $name => $bounds) {
            if ($latitude >= $bounds['lat_min'] && $latitude <= $bounds['lat_max'] &&
                $longitude >= $bounds['lng_min'] && $longitude <= $bounds['lng_max']) {
                return $name;
            }
        }
        
        return 'OUTER';
    }

    /**
     * Create optimized route for a group of stops
     */
    protected function createOptimizedRoute(
        Collection $stops,
        Collection $drivers,
        Carbon $date,
        string $priority,
        string $zone,
        array $options = []
    ): ?DeliveryRoute {
        if ($stops->isEmpty()) {
            return null;
        }
        
        // Calculate route requirements
        $totalWeight = $stops->sum('total_weight_kg');
        $totalVolume = $stops->sum('total_volume_m3');
        $requiresRefrigeration = $stops->where('requires_refrigeration', true)->isNotEmpty();
        
        // Find suitable driver
        $driver = $this->findSuitableDriver($drivers, [
            'weight_kg' => $totalWeight,
            'volume_m3' => $totalVolume,
            'requires_refrigeration' => $requiresRefrigeration,
            'zone' => $zone,
            'stops_count' => $stops->count(),
        ]);
        
        if (!$driver) {
            Log::warning("No suitable driver found for route", [
                'zone' => $zone,
                'stops' => $stops->count(),
                'weight' => $totalWeight,
                'volume' => $totalVolume,
            ]);
            return null;
        }
        
        // Determine route type based on priority
        $routeType = match($priority) {
            'urgent' => 'express',
            'high' => 'express',
            default => 'standard',
        };
        
        // Create route
        $route = DeliveryRoute::create([
            'driver_id' => $driver->id,
            'route_date' => $date,
            'route_type' => $routeType,
            'status' => 'planned',
            'planned_start_time' => $options['start_time'] ?? '08:00',
            'planned_end_time' => $options['end_time'] ?? '17:00',
            'total_stops' => $stops->count(),
            'pending_stops' => $stops->count(),
            'total_weight_kg' => $totalWeight,
            'total_volume_m3' => $totalVolume,
            'zones_covered' => [$zone],
            'start_latitude' => $this->depotLocation['lat'],
            'start_longitude' => $this->depotLocation['lng'],
        ]);
        
        // Add stops to route
        foreach ($stops as $index => $stop) {
            $stop->update([
                'route_id' => $route->id,
                'stop_sequence' => $index + 1,
            ]);
        }
        
        // Optimize the route
        $this->optimizeRoute($route);
        
        // Calculate costs
        $route->update([
            'estimated_fuel_cost' => $this->calculateFuelCost($route),
            'driver_cost' => $driver->calculateEarnings($route),
            'total_revenue' => $route->calculateEstimatedRevenue(),
        ]);
        
        return $route;
    }

    /**
     * Find suitable driver for requirements
     */
    protected function findSuitableDriver(Collection $drivers, array $requirements): ?DeliveryDriver
    {
        foreach ($drivers as $driver) {
            if ($driver->canHandleDelivery($requirements)) {
                return $driver;
            }
        }
        
        return null;
    }

    /**
     * Calculate fuel cost for route
     */
    protected function calculateFuelCost(DeliveryRoute $route): float
    {
        $fuelPrice = 1.85; // AUD per liter
        $avgConsumption = 10; // liters per 100km
        
        $distance = $route->total_distance_km;
        $fuelNeeded = ($distance / 100) * $avgConsumption;
        
        return $fuelNeeded * $fuelPrice;
    }

    /**
     * Check if time is in range
     */
    protected function isInTimeRange(string $time, array $range): bool
    {
        return $time >= $range[0] && $time <= $range[1];
    }

    /**
     * Get optimization method name
     */
    protected function getOptimizationMethod(int $stopCount): string
    {
        if ($stopCount <= 10) {
            return 'brute_force';
        } elseif ($stopCount <= 25) {
            return '2opt';
        } else {
            return 'simulated_annealing';
        }
    }
}