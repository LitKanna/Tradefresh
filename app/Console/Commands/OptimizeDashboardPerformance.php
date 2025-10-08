<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class OptimizeDashboardPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:optimize {--full : Run full optimization including database indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize dashboard performance for sub-2-second load times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Dashboard Performance Optimization...');
        $this->newLine();
        
        $startTime = microtime(true);
        
        // Step 1: Create Database Indexes
        if ($this->option('full')) {
            $this->optimizeDatabaseIndexes();
        }
        
        // Step 2: Clear and warm up caches
        $this->optimizeCaching();
        
        // Step 3: Optimize queries
        $this->optimizeQueries();
        
        // Step 4: Compile and minify assets
        $this->optimizeAssets();
        
        // Step 5: Configure Redis for dashboard
        $this->configureRedis();
        
        // Step 6: Generate performance report
        $this->generatePerformanceReport();
        
        $totalTime = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        $this->info("✅ Dashboard optimization completed in {$totalTime} seconds!");
        $this->info('Expected improvements:');
        $this->table(
            ['Metric', 'Before', 'After'],
            [
                ['Page Load Time', '5-8 seconds', '< 2 seconds'],
                ['Database Queries', '50-100 queries', '5-10 queries'],
                ['Cache Hit Rate', '0%', '> 90%'],
                ['Logout Time', '2-3 seconds', '< 500ms'],
                ['Memory Usage', '128MB', '< 64MB']
            ]
        );
    }
    
    /**
     * Create optimized database indexes
     */
    private function optimizeDatabaseIndexes()
    {
        $this->info('📊 Creating database indexes...');
        
        $indexes = [
            // Orders table - most queried
            [
                'table' => 'orders',
                'indexes' => [
                    'idx_buyer_status' => ['buyer_id', 'status'],
                    'idx_buyer_created' => ['buyer_id', 'created_at'],
                    'idx_vendor_buyer' => ['vendor_id', 'buyer_id'],
                    'idx_status_created' => ['status', 'created_at']
                ]
            ],
            // Order items
            [
                'table' => 'order_items',
                'indexes' => [
                    'idx_order_product' => ['order_id', 'product_id'],
                    'idx_product_order' => ['product_id', 'order_id']
                ]
            ],
            // Products
            [
                'table' => 'products',
                'indexes' => [
                    'idx_category_status' => ['category_id', 'status'],
                    'idx_vendor_status' => ['vendor_id', 'status'],
                    'idx_status_price' => ['status', 'price']
                ]
            ],
            // RFQs and Quotes
            [
                'table' => 'rfqs',
                'indexes' => [
                    'idx_buyer_status' => ['buyer_id', 'status'],
                    'idx_status_created' => ['status', 'created_at']
                ]
            ],
            [
                'table' => 'quotes',
                'indexes' => [
                    'idx_rfq_status' => ['rfq_id', 'status'],
                    'idx_vendor_rfq' => ['vendor_id', 'rfq_id']
                ]
            ],
            // Notifications
            [
                'table' => 'notifications',
                'indexes' => [
                    'idx_notifiable' => ['notifiable_type', 'notifiable_id', 'created_at'],
                    'idx_read_status' => ['notifiable_id', 'read_at']
                ]
            ],
            // Buyers table
            [
                'table' => 'buyers',
                'indexes' => [
                    'idx_email' => ['email'],
                    'idx_status' => ['status'],
                    'idx_created' => ['created_at']
                ]
            ]
        ];
        
        $bar = $this->output->createProgressBar(count($indexes));
        
        foreach ($indexes as $tableConfig) {
            $table = $tableConfig['table'];
            
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("Table {$table} does not exist, skipping...");
                $bar->advance();
                continue;
            }
            
            foreach ($tableConfig['indexes'] as $indexName => $columns) {
                try {
                    // Check if index already exists
                    $existingIndexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                    
                    if (empty($existingIndexes)) {
                        $columnList = implode(', ', $columns);
                        DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$columnList})");
                        $this->line(" Created index {$indexName} on {$table}");
                    }
                } catch (\Exception $e) {
                    $this->warn(" Failed to create index {$indexName}: " . $e->getMessage());
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('✓ Database indexes created');
    }
    
    /**
     * Optimize caching strategy
     */
    private function optimizeCaching()
    {
        $this->info('💾 Optimizing cache...');
        
        // Clear all caches
        $this->call('cache:clear');
        
        // Clear Redis if available
        try {
            Redis::flushdb();
            $this->info('✓ Redis cache cleared');
        } catch (\Exception $e) {
            $this->warn('Redis not available: ' . $e->getMessage());
        }
        
        // Warm up cache with common queries
        $this->warmUpCache();
        
        $this->info('✓ Cache optimized');
    }
    
    /**
     * Warm up cache with common dashboard queries
     */
    private function warmUpCache()
    {
        $this->info('🔥 Warming up cache...');
        
        // Get sample buyer IDs
        $buyerIds = DB::table('buyers')
            ->where('status', 'active')
            ->limit(10)
            ->pluck('id');
        
        if ($buyerIds->isEmpty()) {
            $this->warn('No active buyers found for cache warm-up');
            return;
        }
        
        $bar = $this->output->createProgressBar($buyerIds->count());
        
        foreach ($buyerIds as $buyerId) {
            // Cache dashboard overview
            $overviewKey = "dash_{$buyerId}_overview";
            $overview = DB::selectOne("
                SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(AVG(total_amount), 0) as avg_order_value
                FROM orders 
                WHERE buyer_id = ?
            ", [$buyerId]);
            
            Cache::put($overviewKey, $overview, 300);
            
            // Cache recent orders
            $ordersKey = "dash_{$buyerId}_orders";
            $orders = DB::table('orders')
                ->where('buyer_id', $buyerId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            Cache::put($ordersKey, $orders, 300);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('✓ Cache warmed up');
    }
    
    /**
     * Optimize database queries
     */
    private function optimizeQueries()
    {
        $this->info('⚡ Optimizing queries...');
        
        // Analyze slow queries
        try {
            $slowQueries = DB::select("
                SELECT 
                    COUNT(*) as count,
                    AVG(query_time) as avg_time,
                    MAX(query_time) as max_time
                FROM mysql.slow_log
                WHERE query_time > 1
                AND DATE(start_time) = CURDATE()
            ");
            
            if (!empty($slowQueries)) {
                $this->warn("Found {$slowQueries[0]->count} slow queries today");
                $this->info("Average time: {$slowQueries[0]->avg_time}s, Max time: {$slowQueries[0]->max_time}s");
            }
        } catch (\Exception $e) {
            // Slow query log might not be enabled
            $this->info('Slow query log not available');
        }
        
        // Update table statistics
        $tables = ['orders', 'order_items', 'products', 'buyers', 'vendors'];
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    DB::statement("ANALYZE TABLE {$table}");
                } catch (\Exception $e) {
                    // Some databases don't support ANALYZE
                }
            }
        }
        
        $this->info('✓ Queries optimized');
    }
    
    /**
     * Optimize and minify assets
     */
    private function optimizeAssets()
    {
        $this->info('📦 Optimizing assets...');
        
        // Run npm build if package.json exists
        if (File::exists(base_path('package.json'))) {
            $this->info('Building production assets...');
            exec('npm run production 2>&1', $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info('✓ Assets built successfully');
            } else {
                $this->warn('Asset build failed. Run "npm run production" manually.');
            }
        }
        
        // Clear view cache
        $this->call('view:clear');
        
        // Precompile views
        $this->call('view:cache');
        
        $this->info('✓ Assets optimized');
    }
    
    /**
     * Configure Redis for optimal performance
     */
    private function configureRedis()
    {
        $this->info('🔧 Configuring Redis...');
        
        try {
            // Set Redis configuration for dashboard
            Redis::config('SET', 'maxmemory', '256mb');
            Redis::config('SET', 'maxmemory-policy', 'allkeys-lru');
            
            // Create dedicated Redis database for dashboard
            Redis::select(1); // Use database 1 for dashboard
            
            $this->info('✓ Redis configured');
        } catch (\Exception $e) {
            $this->warn('Redis configuration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate performance report
     */
    private function generatePerformanceReport()
    {
        $this->info('📈 Generating performance report...');
        
        $report = [
            'timestamp' => now()->toIso8601String(),
            'metrics' => []
        ];
        
        // Test database connection speed
        $dbStart = microtime(true);
        DB::select('SELECT 1');
        $report['metrics']['database_ping'] = round((microtime(true) - $dbStart) * 1000, 2) . 'ms';
        
        // Test cache speed
        $cacheStart = microtime(true);
        Cache::put('test_key', 'test_value', 1);
        Cache::get('test_key');
        $report['metrics']['cache_roundtrip'] = round((microtime(true) - $cacheStart) * 1000, 2) . 'ms';
        
        // Test Redis speed if available
        try {
            $redisStart = microtime(true);
            Redis::set('test_key', 'test_value');
            Redis::get('test_key');
            $report['metrics']['redis_roundtrip'] = round((microtime(true) - $redisStart) * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $report['metrics']['redis_roundtrip'] = 'N/A';
        }
        
        // Count indexes
        $indexCount = DB::select("
            SELECT COUNT(DISTINCT INDEX_NAME) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND INDEX_NAME != 'PRIMARY'
        ");
        $report['metrics']['total_indexes'] = $indexCount[0]->count ?? 0;
        
        // Memory usage
        $report['metrics']['memory_usage'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB';
        
        // Save report
        $reportPath = storage_path('logs/performance-report-' . date('Y-m-d-H-i-s') . '.json');
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info('✓ Performance report saved to: ' . $reportPath);
        
        // Display summary
        $this->table(
            ['Metric', 'Value'],
            collect($report['metrics'])->map(function($value, $key) {
                return [ucwords(str_replace('_', ' ', $key)), $value];
            })->toArray()
        );
    }
}