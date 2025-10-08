<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardCacheService;
use App\Services\QueryMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    protected $queryMonitor;

    protected $cacheService;

    public function __construct(QueryMonitorService $queryMonitor, DashboardCacheService $cacheService)
    {
        $this->queryMonitor = $queryMonitor;
        $this->cacheService = $cacheService;
    }

    /**
     * Get performance metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            // Get query performance
            $queryMetrics = $this->queryMonitor->getPerformanceSummary();

            // Get cache statistics
            $cacheStats = $this->getCacheStatistics();

            // Get database statistics
            $dbStats = $this->getDatabaseStatistics();

            // Get application metrics
            $appMetrics = $this->getApplicationMetrics();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'query_performance' => $queryMetrics,
                    'cache_statistics' => $cacheStats,
                    'database_statistics' => $dbStats,
                    'application_metrics' => $appMetrics,
                    'timestamp' => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching performance metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch performance metrics',
            ], 500);
        }
    }

    /**
     * Get slowest queries
     */
    public function slowQueries(): JsonResponse
    {
        try {
            $slowQueries = $this->queryMonitor->getSlowestQueries(20);

            return response()->json([
                'status' => 'success',
                'data' => $slowQueries,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching slow queries', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch slow queries',
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    protected function getCacheStatistics(): array
    {
        $cacheDriver = config('cache.default');

        // Basic cache stats
        $stats = [
            'driver' => $cacheDriver,
            'hit_rate' => 'N/A',
            'miss_rate' => 'N/A',
            'total_keys' => 0,
        ];

        // Try to get cache hit/miss ratio from stored metrics
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;

        if ($total > 0) {
            $stats['hit_rate'] = round(($hits / $total) * 100, 2).'%';
            $stats['miss_rate'] = round(($misses / $total) * 100, 2).'%';
        }

        return $stats;
    }

    /**
     * Get database statistics
     */
    protected function getDatabaseStatistics(): array
    {
        try {
            // Get database size (SQLite specific)
            $dbPath = config('database.connections.sqlite.database');
            $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;

            // Get table counts
            $tables = [
                'users' => DB::table('users')->count(),
                'vendors' => DB::table('vendors')->count(),
                'products' => DB::table('products')->count(),
                'quotes' => DB::table('quotes')->count(),
                'orders' => DB::table('orders')->count(),
                'rfqs' => DB::table('rfqs')->count(),
            ];

            return [
                'database_size' => $this->formatBytes($dbSize),
                'total_records' => array_sum($tables),
                'table_counts' => $tables,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting database statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'database_size' => 'N/A',
                'total_records' => 0,
                'table_counts' => [],
            ];
        }
    }

    /**
     * Get application metrics
     */
    protected function getApplicationMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'cpu_usage' => function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 'N/A') : 'N/A (Windows)',
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($size, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }

    /**
     * Get application uptime
     */
    protected function getUptime(): string
    {
        $startTime = Cache::get('app_start_time', now());
        $uptime = now()->diffInMinutes($startTime);

        if ($uptime < 60) {
            return $uptime.' minutes';
        } elseif ($uptime < 1440) {
            return round($uptime / 60, 1).' hours';
        } else {
            return round($uptime / 1440, 1).' days';
        }
    }

    /**
     * Clear all performance metrics
     */
    public function clearMetrics(): JsonResponse
    {
        try {
            $this->queryMonitor->clearMetrics();
            Cache::forget('cache_hits');
            Cache::forget('cache_misses');

            return response()->json([
                'status' => 'success',
                'message' => 'Performance metrics cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing performance metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear performance metrics',
            ], 500);
        }
    }
}
