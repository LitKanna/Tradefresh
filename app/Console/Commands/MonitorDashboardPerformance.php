<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorDashboardPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:monitor 
                            {--user= : Specific user ID to test}
                            {--iterations=5 : Number of test iterations}
                            {--detailed : Show detailed query analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor dashboard performance metrics and identify bottlenecks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user') ?? 1;
        $iterations = (int) $this->option('iterations');
        $detailed = $this->option('detailed');
        
        $this->info('Dashboard Performance Monitor');
        $this->info('=============================');
        $this->newLine();
        
        // Enable query logging
        DB::enableQueryLog();
        
        $metrics = [
            'cold_cache' => [],
            'warm_cache' => [],
            'queries' => []
        ];
        
        // Test with cold cache
        $this->info('Testing with COLD cache...');
        Cache::tags(["user.{$userId}"])->flush();
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $this->simulateDashboardLoad($userId);
            $duration = (microtime(true) - $start) * 1000;
            $metrics['cold_cache'][] = $duration;
            $this->line("  Iteration " . ($i + 1) . ": {$duration}ms");
        }
        
        // Capture queries from cold cache run
        $metrics['queries'] = DB::getQueryLog();
        DB::flushQueryLog();
        
        $this->newLine();
        
        // Test with warm cache
        $this->info('Testing with WARM cache...');
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $this->simulateDashboardLoad($userId);
            $duration = (microtime(true) - $start) * 1000;
            $metrics['warm_cache'][] = $duration;
            $this->line("  Iteration " . ($i + 1) . ": {$duration}ms");
        }
        
        $this->newLine();
        $this->displayResults($metrics, $detailed);
        
        // Save results to log
        $this->saveMetricsToLog($metrics);
        
        DB::disableQueryLog();
        
        return Command::SUCCESS;
    }
    
    private function simulateDashboardLoad($userId)
    {
        // Simulate dashboard data loading
        $cacheKey = "dashboard.metrics.{$userId}.week";
        
        Cache::tags(['dashboard', "user.{$userId}"])->remember(
            $cacheKey,
            300,
            function() use ($userId) {
                return [
                    'activeRFQsCount' => DB::table('rfqs')
                        ->where('buyer_id', $userId)
                        ->whereIn('status', ['open', 'pending_review'])
                        ->count(),
                    
                    'pendingQuotesCount' => DB::table('quotes')
                        ->join('rfqs', 'quotes.rfq_id', '=', 'rfqs.id')
                        ->where('rfqs.buyer_id', $userId)
                        ->where('quotes.status', 'pending')
                        ->count(),
                    
                    'activeOrdersCount' => DB::table('orders')
                        ->where('buyer_id', $userId)
                        ->whereIn('status', ['confirmed', 'processing', 'shipped'])
                        ->count(),
                    
                    'totalSpentThisMonth' => DB::table('orders')
                        ->where('buyer_id', $userId)
                        ->where('status', 'delivered')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('total_amount'),
                ];
            }
        );
        
        // Simulate chart data loading
        $chartKey = "dashboard.charts.{$userId}.7";
        Cache::tags(['dashboard', "user.{$userId}"])->remember(
            $chartKey,
            600,
            function() use ($userId) {
                return DB::select("
                    SELECT 
                        DATE(created_at) as date,
                        SUM(total_amount) as total
                    FROM orders
                    WHERE buyer_id = ?
                        AND status = 'delivered'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ", [$userId]);
            }
        );
    }
    
    private function displayResults($metrics, $detailed)
    {
        $this->info('Performance Analysis Results');
        $this->info('============================');
        $this->newLine();
        
        // Cold cache statistics
        $coldAvg = round(array_sum($metrics['cold_cache']) / count($metrics['cold_cache']), 2);
        $coldMin = round(min($metrics['cold_cache']), 2);
        $coldMax = round(max($metrics['cold_cache']), 2);
        
        $this->table(
            ['Metric', 'Cold Cache', 'Warm Cache', 'Improvement'],
            [
                ['Average', "{$coldAvg}ms", $this->getWarmCacheAvg($metrics) . "ms", $this->getImprovement($metrics) . "%"],
                ['Min', "{$coldMin}ms", $this->getWarmCacheMin($metrics) . "ms", "-"],
                ['Max', "{$coldMax}ms", $this->getWarmCacheMax($metrics) . "ms", "-"],
            ]
        );
        
        $this->newLine();
        
        // Query Analysis
        $this->info('Query Analysis');
        $this->info('--------------');
        $this->line('Total queries executed: ' . count($metrics['queries']));
        $totalQueryTime = array_sum(array_column($metrics['queries'], 'time'));
        $this->line('Total query time: ' . round($totalQueryTime, 2) . 'ms');
        
        if ($detailed && count($metrics['queries']) > 0) {
            $this->newLine();
            $this->info('Top 5 Slowest Queries:');
            
            $queries = collect($metrics['queries'])
                ->sortByDesc('time')
                ->take(5);
            
            foreach ($queries as $index => $query) {
                $this->warn(($index + 1) . ". Time: {$query['time']}ms");
                $this->line("   SQL: " . $this->truncateQuery($query['query']));
                $this->newLine();
            }
        }
        
        // Recommendations
        $this->displayRecommendations($metrics);
    }
    
    private function displayRecommendations($metrics)
    {
        $this->newLine();
        $this->info('Performance Recommendations');
        $this->info('---------------------------');
        
        $coldAvg = array_sum($metrics['cold_cache']) / count($metrics['cold_cache']);
        $warmAvg = array_sum($metrics['warm_cache']) / count($metrics['warm_cache']);
        
        if ($coldAvg > 1000) {
            $this->error('⚠ Cold cache performance is slow (>1000ms)');
            $this->line('  → Consider adding database indexes');
            $this->line('  → Review and optimize complex queries');
        } elseif ($coldAvg > 500) {
            $this->warn('⚠ Cold cache performance could be improved (>500ms)');
            $this->line('  → Consider query optimization');
        } else {
            $this->info('✓ Cold cache performance is good (<500ms)');
        }
        
        if ($warmAvg > 200) {
            $this->warn('⚠ Warm cache performance could be better (>200ms)');
            $this->line('  → Check Redis connection latency');
            $this->line('  → Consider cache key optimization');
        } else {
            $this->info('✓ Warm cache performance is excellent (<200ms)');
        }
        
        $improvement = (($coldAvg - $warmAvg) / $coldAvg) * 100;
        if ($improvement < 50) {
            $this->warn('⚠ Cache effectiveness is low (<50% improvement)');
            $this->line('  → Verify cache is properly configured');
            $this->line('  → Check cache hit rates');
        } else {
            $this->info('✓ Cache is highly effective (' . round($improvement) . '% improvement)');
        }
        
        // Check for N+1 queries
        $duplicateQueries = $this->findDuplicateQueries($metrics['queries']);
        if (count($duplicateQueries) > 0) {
            $this->error('⚠ Potential N+1 query problem detected');
            $this->line('  → Found ' . count($duplicateQueries) . ' duplicate query patterns');
            $this->line('  → Consider using eager loading');
        }
    }
    
    private function findDuplicateQueries($queries)
    {
        $patterns = [];
        foreach ($queries as $query) {
            // Normalize query to find patterns
            $pattern = preg_replace('/\d+/', '?', $query['query']);
            $patterns[$pattern] = ($patterns[$pattern] ?? 0) + 1;
        }
        
        return array_filter($patterns, function($count) {
            return $count > 2;
        });
    }
    
    private function getWarmCacheAvg($metrics)
    {
        return round(array_sum($metrics['warm_cache']) / count($metrics['warm_cache']), 2);
    }
    
    private function getWarmCacheMin($metrics)
    {
        return round(min($metrics['warm_cache']), 2);
    }
    
    private function getWarmCacheMax($metrics)
    {
        return round(max($metrics['warm_cache']), 2);
    }
    
    private function getImprovement($metrics)
    {
        $coldAvg = array_sum($metrics['cold_cache']) / count($metrics['cold_cache']);
        $warmAvg = array_sum($metrics['warm_cache']) / count($metrics['warm_cache']);
        return round((($coldAvg - $warmAvg) / $coldAvg) * 100, 1);
    }
    
    private function truncateQuery($query, $maxLength = 100)
    {
        if (strlen($query) <= $maxLength) {
            return $query;
        }
        return substr($query, 0, $maxLength) . '...';
    }
    
    private function saveMetricsToLog($metrics)
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'cold_cache_avg' => array_sum($metrics['cold_cache']) / count($metrics['cold_cache']),
            'warm_cache_avg' => array_sum($metrics['warm_cache']) / count($metrics['warm_cache']),
            'total_queries' => count($metrics['queries']),
            'query_time' => array_sum(array_column($metrics['queries'], 'time')),
        ];
        
        Log::channel('performance')->info('Dashboard Performance Metrics', $logData);
        
        $this->newLine();
        $this->info('Results saved to logs/performance.log');
    }
}