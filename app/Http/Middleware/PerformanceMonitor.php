<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Process the request
        $response = $next($request);
        
        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = round(($endTime - $startTime) * 1000, 2); // in milliseconds
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2); // in MB
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2); // in MB
        
        // Add performance headers
        $response->headers->set('X-Response-Time', $executionTime . 'ms');
        $response->headers->set('X-Memory-Usage', $memoryUsed . 'MB');
        $response->headers->set('X-Peak-Memory', $peakMemory . 'MB');
        
        // Log slow requests
        if ($executionTime > 2000) { // Over 2 seconds
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime,
                'memory_used' => $memoryUsed,
                'user' => $request->user()?->id
            ]);
        }
        
        // Track performance metrics for monitoring
        $this->trackMetrics($request, $executionTime, $memoryUsed);
        
        return $response;
    }
    
    /**
     * Track performance metrics
     */
    private function trackMetrics(Request $request, float $executionTime, float $memoryUsed)
    {
        $route = $request->route()?->getName() ?? $request->path();
        $method = $request->method();
        
        // Increment request count
        Cache::increment("performance:requests:{$route}:{$method}");
        
        // Track average response time
        $avgKey = "performance:avg_time:{$route}:{$method}";
        $countKey = "performance:count:{$route}:{$method}";
        $totalKey = "performance:total_time:{$route}:{$method}";
        
        $count = Cache::increment($countKey);
        $total = Cache::increment($totalKey, $executionTime);
        $avg = round($total / $count, 2);
        
        Cache::put($avgKey, $avg, 3600); // Store for 1 hour
        
        // Track slowest requests
        $slowestKey = "performance:slowest";
        $slowest = Cache::get($slowestKey, []);
        
        $slowest[] = [
            'route' => $route,
            'method' => $method,
            'time' => $executionTime,
            'memory' => $memoryUsed,
            'timestamp' => now()->toIso8601String()
        ];
        
        // Keep only top 100 slowest
        usort($slowest, function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        $slowest = array_slice($slowest, 0, 100);
        Cache::put($slowestKey, $slowest, 3600);
    }
}