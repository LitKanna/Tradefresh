<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryMonitorService
{
    protected $queries = [];

    protected $threshold = 100; // Alert if query takes more than 100ms

    protected $enabled = true;

    public function __construct()
    {
        $this->enabled = config('app.debug', false);
    }

    /**
     * Start monitoring database queries
     */
    public function startMonitoring()
    {
        if (! $this->enabled) {
            return;
        }

        DB::listen(function ($query) {
            $sql = $query->sql;
            $bindings = $query->bindings;
            $time = $query->time;

            // Store query data
            $queryData = [
                'sql' => $sql,
                'bindings' => $bindings,
                'time' => $time,
                'timestamp' => now()->toDateTimeString(),
                'slow' => $time > $this->threshold,
            ];

            $this->queries[] = $queryData;

            // Log slow queries
            if ($time > $this->threshold) {
                Log::warning('Slow query detected', [
                    'sql' => $sql,
                    'time' => $time.'ms',
                    'bindings' => $bindings,
                ]);
            }

            // Store in cache for dashboard
            $this->storeQueryMetrics($queryData);
        });
    }

    /**
     * Store query metrics for performance dashboard
     */
    protected function storeQueryMetrics($queryData)
    {
        $key = 'query_metrics_'.date('Y-m-d_H');

        $metrics = Cache::get($key, [
            'total_queries' => 0,
            'slow_queries' => 0,
            'total_time' => 0,
            'queries' => [],
        ]);

        $metrics['total_queries']++;
        $metrics['total_time'] += $queryData['time'];

        if ($queryData['slow']) {
            $metrics['slow_queries']++;
        }

        // Keep only last 100 queries
        $metrics['queries'][] = $queryData;
        if (count($metrics['queries']) > 100) {
            array_shift($metrics['queries']);
        }

        Cache::put($key, $metrics, 3600); // Store for 1 hour
    }

    /**
     * Get current query metrics
     */
    public function getMetrics()
    {
        $key = 'query_metrics_'.date('Y-m-d_H');

        return Cache::get($key, [
            'total_queries' => 0,
            'slow_queries' => 0,
            'total_time' => 0,
            'average_time' => 0,
            'queries' => [],
        ]);
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary()
    {
        $metrics = $this->getMetrics();

        $avgTime = $metrics['total_queries'] > 0
            ? round($metrics['total_time'] / $metrics['total_queries'], 2)
            : 0;

        return [
            'total_queries' => $metrics['total_queries'],
            'slow_queries' => $metrics['slow_queries'],
            'total_time' => round($metrics['total_time'], 2),
            'average_time' => $avgTime,
            'slow_query_percentage' => $metrics['total_queries'] > 0
                ? round(($metrics['slow_queries'] / $metrics['total_queries']) * 100, 2)
                : 0,
            'health_status' => $this->getHealthStatus($avgTime, $metrics['slow_queries']),
        ];
    }

    /**
     * Determine health status based on metrics
     */
    protected function getHealthStatus($avgTime, $slowQueries)
    {
        if ($avgTime < 50 && $slowQueries < 5) {
            return 'excellent';
        } elseif ($avgTime < 100 && $slowQueries < 10) {
            return 'good';
        } elseif ($avgTime < 200 && $slowQueries < 20) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get slowest queries
     */
    public function getSlowestQueries($limit = 10)
    {
        $metrics = $this->getMetrics();

        $queries = collect($metrics['queries'])
            ->sortByDesc('time')
            ->take($limit)
            ->map(function ($query) {
                return [
                    'sql' => $this->formatSql($query['sql']),
                    'time' => $query['time'].'ms',
                    'timestamp' => $query['timestamp'],
                ];
            })
            ->values()
            ->toArray();

        return $queries;
    }

    /**
     * Format SQL for display
     */
    protected function formatSql($sql)
    {
        // Truncate long queries
        if (strlen($sql) > 100) {
            return substr($sql, 0, 97).'...';
        }

        return $sql;
    }

    /**
     * Clear all metrics
     */
    public function clearMetrics()
    {
        $key = 'query_metrics_'.date('Y-m-d_H');
        Cache::forget($key);
    }
}
