<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerformanceOptimizationService
{
    protected $slowQueryThreshold = 1000; // milliseconds
    protected $cachePrefix = 'db_perf_';
    protected $analysisResults = [];
    
    /**
     * Run complete database performance analysis and optimization
     */
    public function optimizeDatabase(): array
    {
        $this->analysisResults = [
            'timestamp' => Carbon::now(),
            'optimizations' => [],
            'warnings' => [],
            'recommendations' => [],
        ];
        
        // Run various optimization checks
        $this->analyzeIndexUsage();
        $this->analyzeSlowQueries();
        $this->optimizeTableStructure();
        $this->analyzeQueryPatterns();
        $this->checkMissingIndexes();
        $this->analyzeTableSizes();
        $this->optimizeConnectionPool();
        $this->implementQueryCaching();
        $this->createMaterializedViews();
        
        return $this->analysisResults;
    }
    
    /**
     * Analyze index usage across all tables
     */
    protected function analyzeIndexUsage(): void
    {
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $indexes = $this->getTableIndexes($table);
            $tableStats = $this->getTableStatistics($table);
            
            // Check for unused indexes
            foreach ($indexes as $index) {
                if ($this->isIndexUnused($table, $index)) {
                    $this->analysisResults['warnings'][] = [
                        'type' => 'unused_index',
                        'table' => $table,
                        'index' => $index->Key_name,
                        'recommendation' => "Consider dropping unused index '{$index->Key_name}' on table '{$table}'",
                    ];
                }
            }
            
            // Check for duplicate indexes
            $this->checkDuplicateIndexes($table, $indexes);
            
            // Recommend composite indexes for common query patterns
            $this->recommendCompositeIndexes($table);
        }
    }
    
    /**
     * Analyze slow queries from query log
     */
    protected function analyzeSlowQueries(): void
    {
        // Enable query logging temporarily
        DB::enableQueryLog();
        
        // Get slow query patterns from cache or database
        $slowQueries = Cache::remember($this->cachePrefix . 'slow_queries', 3600, function () {
            return DB::table('activity_logs')
                ->where('log_name', 'slow_query')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->get();
        });
        
        foreach ($slowQueries as $query) {
            $this->analyzeQueryExecution($query);
        }
        
        DB::disableQueryLog();
    }
    
    /**
     * Optimize table structure and storage
     */
    protected function optimizeTableStructure(): void
    {
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            // Analyze table fragmentation
            $fragmentation = $this->getTableFragmentation($table);
            
            if ($fragmentation > 20) {
                $this->analysisResults['recommendations'][] = [
                    'type' => 'table_optimization',
                    'table' => $table,
                    'action' => 'OPTIMIZE TABLE',
                    'reason' => "Table has {$fragmentation}% fragmentation",
                    'command' => "OPTIMIZE TABLE `{$table}`",
                ];
            }
            
            // Check for optimal data types
            $this->checkDataTypes($table);
            
            // Recommend partitioning for large tables
            $this->checkPartitioningNeeds($table);
        }
    }
    
    /**
     * Analyze common query patterns and optimize
     */
    protected function analyzeQueryPatterns(): void
    {
        $patterns = [
            // Orders table optimizations
            'orders' => [
                'frequent_queries' => [
                    ['columns' => ['buyer_business_id', 'status', 'created_at'], 'type' => 'btree'],
                    ['columns' => ['vendor_business_id', 'status', 'created_at'], 'type' => 'btree'],
                    ['columns' => ['status', 'payment_status', 'created_at'], 'type' => 'btree'],
                    ['columns' => ['order_number'], 'type' => 'unique'],
                ],
                'covering_indexes' => [
                    ['columns' => ['id', 'order_number', 'status', 'total_amount', 'created_at']],
                ],
            ],
            // Products table optimizations
            'products' => [
                'frequent_queries' => [
                    ['columns' => ['business_id', 'status', 'is_featured'], 'type' => 'btree'],
                    ['columns' => ['category_id', 'status', 'base_price'], 'type' => 'btree'],
                    ['columns' => ['sku'], 'type' => 'unique'],
                    ['columns' => ['name', 'description'], 'type' => 'fulltext'],
                ],
                'partial_indexes' => [
                    ['columns' => ['status'], 'where' => "status = 'active'"],
                ],
            ],
            // Users table optimizations
            'users' => [
                'frequent_queries' => [
                    ['columns' => ['email', 'password'], 'type' => 'btree'],
                    ['columns' => ['business_id', 'user_type', 'is_active'], 'type' => 'btree'],
                    ['columns' => ['last_login_at'], 'type' => 'btree'],
                ],
            ],
        ];
        
        foreach ($patterns as $table => $optimizations) {
            if (Schema::hasTable($table)) {
                $this->applyQueryOptimizations($table, $optimizations);
            }
        }
    }
    
    /**
     * Check for missing indexes based on query patterns
     */
    protected function checkMissingIndexes(): void
    {
        $missingIndexes = [
            'orders' => [
                ['column' => 'requested_delivery_date', 'reason' => 'Frequent date range queries'],
                ['column' => 'payment_due_date', 'reason' => 'Overdue payment queries'],
                ['columns' => ['delivery_method', 'status'], 'reason' => 'Delivery tracking queries'],
            ],
            'products' => [
                ['column' => 'stock_quantity', 'reason' => 'Low stock queries'],
                ['columns' => ['is_organic', 'is_gluten_free', 'is_vegan'], 'reason' => 'Dietary filter queries'],
            ],
            'pickup_bookings' => [
                ['columns' => ['pickup_date', 'bay_id'], 'reason' => 'Bay availability queries'],
                ['columns' => ['pickup_date', 'slot_id'], 'reason' => 'Slot availability queries'],
            ],
            'deliveries' => [
                ['columns' => ['scheduled_at', 'status'], 'reason' => 'Delivery schedule queries'],
                ['column' => 'tracking_number', 'reason' => 'Tracking lookups'],
            ],
        ];
        
        foreach ($missingIndexes as $table => $indexes) {
            if (Schema::hasTable($table)) {
                foreach ($indexes as $index) {
                    $this->recommendIndex($table, $index);
                }
            }
        }
    }
    
    /**
     * Analyze table sizes and recommend optimizations
     */
    protected function analyzeTableSizes(): void
    {
        $tables = DB::select("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows as row_count
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ", [config('database.connections.mysql.database')]);
        
        foreach ($tables as $table) {
            // Large table optimization recommendations
            if ($table->size_mb > 1000) {
                $this->analysisResults['recommendations'][] = [
                    'type' => 'large_table',
                    'table' => $table->table_name,
                    'size' => $table->size_mb . ' MB',
                    'rows' => number_format($table->row_count),
                    'suggestions' => $this->getLargeTableSuggestions($table->table_name),
                ];
            }
            
            // Archive old data recommendations
            if ($this->hasTimestampColumn($table->table_name)) {
                $this->recommendArchiving($table->table_name, $table->row_count);
            }
        }
    }
    
    /**
     * Optimize database connection pool settings
     */
    protected function optimizeConnectionPool(): array
    {
        $currentConnections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 151;
        
        $recommendations = [];
        
        // Connection pool size optimization
        $optimalPoolSize = min(100, max(20, $currentConnections * 1.5));
        
        $recommendations['connection_pool'] = [
            'current_connections' => $currentConnections,
            'max_connections' => $maxConnections,
            'recommended_pool_size' => round($optimalPoolSize),
            'recommended_idle_timeout' => 300,
            'recommended_max_lifetime' => 1800,
        ];
        
        // Query cache recommendations
        $queryCacheSize = DB::select("SHOW VARIABLES LIKE 'query_cache_size'")[0]->Value ?? 0;
        
        if ($queryCacheSize == 0) {
            $recommendations['query_cache'] = [
                'status' => 'disabled',
                'recommendation' => 'Enable query cache with size 64MB for improved read performance',
            ];
        }
        
        $this->analysisResults['optimizations'][] = [
            'type' => 'connection_pool',
            'recommendations' => $recommendations,
        ];
        
        return $recommendations;
    }
    
    /**
     * Implement query caching strategies
     */
    protected function implementQueryCaching(): void
    {
        $cacheStrategies = [
            'hot_products' => [
                'query' => 'SELECT * FROM products WHERE status = "active" AND is_featured = 1',
                'cache_key' => 'featured_products',
                'ttl' => 3600, // 1 hour
            ],
            'vendor_list' => [
                'query' => 'SELECT * FROM businesses WHERE business_type = "vendor" AND status = "active"',
                'cache_key' => 'active_vendors',
                'ttl' => 7200, // 2 hours
            ],
            'category_tree' => [
                'query' => 'SELECT * FROM categories WHERE is_active = 1 ORDER BY parent_id, sort_order',
                'cache_key' => 'category_hierarchy',
                'ttl' => 86400, // 24 hours
            ],
            'pickup_slots' => [
                'query' => 'SELECT * FROM pickup_time_slots WHERE is_active = 1',
                'cache_key' => 'available_pickup_slots',
                'ttl' => 1800, // 30 minutes
            ],
        ];
        
        foreach ($cacheStrategies as $name => $strategy) {
            $this->analysisResults['optimizations'][] = [
                'type' => 'query_cache',
                'name' => $name,
                'cache_key' => $strategy['cache_key'],
                'ttl' => $strategy['ttl'],
                'implementation' => $this->generateCacheImplementation($strategy),
            ];
        }
    }
    
    /**
     * Create materialized views for complex queries
     */
    protected function createMaterializedViews(): void
    {
        $views = [
            'order_summary_daily' => "
                CREATE OR REPLACE VIEW order_summary_daily AS
                SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(DISTINCT buyer_business_id) as unique_buyers,
                    COUNT(DISTINCT vendor_business_id) as unique_vendors
                FROM orders
                WHERE status NOT IN ('cancelled', 'refunded')
                GROUP BY DATE(order_date)
            ",
            'vendor_performance' => "
                CREATE OR REPLACE VIEW vendor_performance AS
                SELECT 
                    b.id as business_id,
                    b.trading_name,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as avg_order_value,
                    b.average_rating,
                    b.on_time_delivery_rate
                FROM businesses b
                LEFT JOIN orders o ON b.id = o.vendor_business_id
                WHERE b.business_type IN ('vendor', 'both')
                GROUP BY b.id
            ",
            'inventory_status' => "
                CREATE OR REPLACE VIEW inventory_status AS
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    p.low_stock_threshold,
                    CASE 
                        WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                        WHEN p.stock_quantity <= p.low_stock_threshold THEN 'low_stock'
                        ELSE 'in_stock'
                    END as stock_status,
                    b.trading_name as vendor_name
                FROM products p
                JOIN businesses b ON p.business_id = b.id
                WHERE p.track_inventory = 1
            ",
        ];
        
        foreach ($views as $viewName => $viewSql) {
            $this->analysisResults['optimizations'][] = [
                'type' => 'materialized_view',
                'name' => $viewName,
                'purpose' => $this->getViewPurpose($viewName),
                'refresh_strategy' => $this->getRefreshStrategy($viewName),
                'sql' => $viewSql,
            ];
        }
    }
    
    /**
     * Helper method to get all tables
     */
    protected function getAllTables(): array
    {
        $tables = DB::select("SHOW TABLES");
        $tableNames = [];
        
        foreach ($tables as $table) {
            $tableNames[] = array_values((array)$table)[0];
        }
        
        return $tableNames;
    }
    
    /**
     * Get table indexes
     */
    protected function getTableIndexes(string $table): array
    {
        try {
            return DB::select("SHOW INDEX FROM `{$table}`");
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get table statistics
     */
    protected function getTableStatistics(string $table): array
    {
        try {
            $stats = DB::select("
                SELECT 
                    table_rows,
                    avg_row_length,
                    data_length,
                    index_length,
                    data_free
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ", [config('database.connections.mysql.database'), $table]);
            
            return $stats[0] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Check if an index is unused
     */
    protected function isIndexUnused(string $table, $index): bool
    {
        // Skip primary and unique indexes
        if ($index->Key_name === 'PRIMARY' || !$index->Non_unique) {
            return false;
        }
        
        // Check index usage statistics (simplified version)
        // In production, this would query performance_schema
        return false;
    }
    
    /**
     * Check for duplicate indexes
     */
    protected function checkDuplicateIndexes(string $table, array $indexes): void
    {
        $indexColumns = [];
        
        foreach ($indexes as $index) {
            if (!isset($indexColumns[$index->Key_name])) {
                $indexColumns[$index->Key_name] = [];
            }
            $indexColumns[$index->Key_name][] = $index->Column_name;
        }
        
        // Check for redundant indexes
        foreach ($indexColumns as $name1 => $columns1) {
            foreach ($indexColumns as $name2 => $columns2) {
                if ($name1 !== $name2 && $this->isIndexRedundant($columns1, $columns2)) {
                    $this->analysisResults['warnings'][] = [
                        'type' => 'redundant_index',
                        'table' => $table,
                        'redundant_index' => $name1,
                        'covered_by' => $name2,
                    ];
                }
            }
        }
    }
    
    /**
     * Check if one index is redundant to another
     */
    protected function isIndexRedundant(array $columns1, array $columns2): bool
    {
        if (count($columns1) > count($columns2)) {
            return false;
        }
        
        foreach ($columns1 as $i => $column) {
            if (!isset($columns2[$i]) || $columns2[$i] !== $column) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Recommend composite indexes
     */
    protected function recommendCompositeIndexes(string $table): void
    {
        // This would analyze actual query patterns in production
        // For now, we'll use predefined recommendations
    }
    
    /**
     * Analyze query execution plan
     */
    protected function analyzeQueryExecution($query): void
    {
        try {
            $explain = DB::select("EXPLAIN {$query->description}");
            
            foreach ($explain as $row) {
                if ($row->type === 'ALL' || $row->type === 'index') {
                    $this->analysisResults['warnings'][] = [
                        'type' => 'full_table_scan',
                        'query' => substr($query->description, 0, 100),
                        'table' => $row->table,
                        'rows_examined' => $row->rows,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Query might not be valid for EXPLAIN
        }
    }
    
    /**
     * Get table fragmentation percentage
     */
    protected function getTableFragmentation(string $table): float
    {
        try {
            $stats = DB::select("
                SELECT 
                    data_free,
                    (data_length + index_length) as total_size
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ", [config('database.connections.mysql.database'), $table]);
            
            if ($stats && $stats[0]->total_size > 0) {
                return ($stats[0]->data_free / $stats[0]->total_size) * 100;
            }
        } catch (\Exception $e) {
            // Table might not exist
        }
        
        return 0;
    }
    
    /**
     * Check data types for optimization
     */
    protected function checkDataTypes(string $table): void
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            
            foreach ($columns as $column) {
                // Check for oversized VARCHAR columns
                if (preg_match('/varchar\((\d+)\)/i', $column->Type, $matches)) {
                    if ($matches[1] > 255) {
                        $this->analysisResults['recommendations'][] = [
                            'type' => 'data_type',
                            'table' => $table,
                            'column' => $column->Field,
                            'current' => $column->Type,
                            'suggestion' => 'Consider using TEXT type for large VARCHAR columns',
                        ];
                    }
                }
                
                // Check for DATETIME vs TIMESTAMP
                if (stripos($column->Type, 'datetime') !== false) {
                    $this->analysisResults['recommendations'][] = [
                        'type' => 'data_type',
                        'table' => $table,
                        'column' => $column->Field,
                        'current' => $column->Type,
                        'suggestion' => 'Consider TIMESTAMP for better storage efficiency',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Table might not exist
        }
    }
    
    /**
     * Check if table needs partitioning
     */
    protected function checkPartitioningNeeds(string $table): void
    {
        $stats = $this->getTableStatistics($table);
        
        if (isset($stats->table_rows) && $stats->table_rows > 1000000) {
            $this->analysisResults['recommendations'][] = [
                'type' => 'partitioning',
                'table' => $table,
                'rows' => number_format($stats->table_rows),
                'suggestion' => 'Consider partitioning this large table',
                'strategies' => $this->getPartitioningStrategies($table),
            ];
        }
    }
    
    /**
     * Get partitioning strategies for a table
     */
    protected function getPartitioningStrategies(string $table): array
    {
        $strategies = [];
        
        // Date-based partitioning for tables with timestamps
        if ($this->hasTimestampColumn($table)) {
            $strategies[] = [
                'type' => 'RANGE',
                'column' => 'created_at',
                'interval' => 'monthly',
            ];
        }
        
        // Hash partitioning for even distribution
        $strategies[] = [
            'type' => 'HASH',
            'column' => 'id',
            'partitions' => 10,
        ];
        
        return $strategies;
    }
    
    /**
     * Check if table has timestamp columns
     */
    protected function hasTimestampColumn(string $table): bool
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field IN ('created_at', 'updated_at')");
            return count($columns) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Recommend archiving for old data
     */
    protected function recommendArchiving(string $table, int $rowCount): void
    {
        if ($rowCount > 100000) {
            $this->analysisResults['recommendations'][] = [
                'type' => 'archiving',
                'table' => $table,
                'rows' => number_format($rowCount),
                'suggestion' => 'Consider archiving data older than 1 year',
                'archive_strategy' => [
                    'method' => 'partition_exchange',
                    'retention_period' => '1 year',
                    'archive_table' => $table . '_archive',
                ],
            ];
        }
    }
    
    /**
     * Apply query optimizations
     */
    protected function applyQueryOptimizations(string $table, array $optimizations): void
    {
        foreach ($optimizations as $type => $items) {
            foreach ($items as $item) {
                $this->analysisResults['optimizations'][] = [
                    'type' => $type,
                    'table' => $table,
                    'details' => $item,
                ];
            }
        }
    }
    
    /**
     * Recommend index creation
     */
    protected function recommendIndex(string $table, array $index): void
    {
        $columns = isset($index['columns']) ? implode(', ', $index['columns']) : $index['column'];
        
        $this->analysisResults['recommendations'][] = [
            'type' => 'missing_index',
            'table' => $table,
            'columns' => $columns,
            'reason' => $index['reason'],
            'sql' => "CREATE INDEX idx_{$table}_" . str_replace(', ', '_', $columns) . " ON {$table} ({$columns})",
        ];
    }
    
    /**
     * Get suggestions for large tables
     */
    protected function getLargeTableSuggestions(string $table): array
    {
        return [
            'Enable table partitioning',
            'Archive old data',
            'Implement data retention policies',
            'Consider sharding for horizontal scaling',
            'Optimize queries to use covering indexes',
        ];
    }
    
    /**
     * Generate cache implementation code
     */
    protected function generateCacheImplementation(array $strategy): string
    {
        return "
Cache::remember('{$strategy['cache_key']}', {$strategy['ttl']}, function () {
    return DB::select(\"{$strategy['query']}\");
});";
    }
    
    /**
     * Get view purpose description
     */
    protected function getViewPurpose(string $viewName): string
    {
        $purposes = [
            'order_summary_daily' => 'Aggregated daily order statistics for reporting',
            'vendor_performance' => 'Vendor performance metrics and KPIs',
            'inventory_status' => 'Real-time inventory status monitoring',
        ];
        
        return $purposes[$viewName] ?? 'Optimized data aggregation';
    }
    
    /**
     * Get refresh strategy for materialized views
     */
    protected function getRefreshStrategy(string $viewName): string
    {
        $strategies = [
            'order_summary_daily' => 'Refresh daily at 2 AM',
            'vendor_performance' => 'Refresh every 6 hours',
            'inventory_status' => 'Refresh every 15 minutes',
        ];
        
        return $strategies[$viewName] ?? 'Refresh hourly';
    }
    
    /**
     * Generate optimization report
     */
    public function generateReport(): array
    {
        return [
            'summary' => [
                'total_optimizations' => count($this->analysisResults['optimizations']),
                'total_warnings' => count($this->analysisResults['warnings']),
                'total_recommendations' => count($this->analysisResults['recommendations']),
            ],
            'critical_issues' => array_filter($this->analysisResults['warnings'], function ($warning) {
                return in_array($warning['type'], ['full_table_scan', 'missing_index']);
            }),
            'quick_wins' => array_filter($this->analysisResults['recommendations'], function ($rec) {
                return in_array($rec['type'], ['unused_index', 'redundant_index', 'query_cache']);
            }),
            'long_term_improvements' => array_filter($this->analysisResults['recommendations'], function ($rec) {
                return in_array($rec['type'], ['partitioning', 'archiving', 'large_table']);
            }),
            'full_results' => $this->analysisResults,
        ];
    }
}