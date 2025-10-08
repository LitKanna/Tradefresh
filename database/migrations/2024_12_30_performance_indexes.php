<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for performance optimization
     */
    public function up()
    {
        // Add indexes for orders table
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for buyer dashboard queries
            if (!$this->indexExists('orders', 'idx_buyer_dashboard')) {
                $table->index(['buyer_id', 'created_at', 'status'], 'idx_buyer_dashboard');
            }
            
            // Index for date range queries
            if (!$this->indexExists('orders', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }
            
            // Index for status filtering
            if (!$this->indexExists('orders', 'idx_status')) {
                $table->index('status', 'idx_status');
            }
            
            // Index for supplier relationship
            if (!$this->indexExists('orders', 'idx_supplier_id')) {
                $table->index('supplier_id', 'idx_supplier_id');
            }
            
            // Composite index for aggregation queries
            if (!$this->indexExists('orders', 'idx_buyer_supplier_date')) {
                $table->index(['buyer_id', 'supplier_id', 'created_at'], 'idx_buyer_supplier_date');
            }
        });
        
        // Add indexes for buyers table
        Schema::table('buyers', function (Blueprint $table) {
            // Index for email lookups (login)
            if (!$this->indexExists('buyers', 'idx_email')) {
                $table->index('email', 'idx_email');
            }
            
            // Index for active buyers
            if (!$this->indexExists('buyers', 'idx_is_active')) {
                $table->index('is_active', 'idx_is_active');
            }
        });
        
        // Add indexes for suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            // Index for active suppliers
            if (!$this->indexExists('suppliers', 'idx_is_active')) {
                $table->index('is_active', 'idx_is_active');
            }
            
            // Index for name searches
            if (!$this->indexExists('suppliers', 'idx_name')) {
                $table->index('name', 'idx_name');
            }
        });
        
        // Add indexes for buyer_supplier pivot table
        if (Schema::hasTable('buyer_supplier')) {
            Schema::table('buyer_supplier', function (Blueprint $table) {
                // Composite index for relationship queries
                if (!$this->indexExists('buyer_supplier', 'idx_buyer_supplier')) {
                    $table->index(['buyer_id', 'supplier_id'], 'idx_buyer_supplier');
                }
                
                // Index for created_at (for growth calculations)
                if (!$this->indexExists('buyer_supplier', 'idx_created_at')) {
                    $table->index('created_at', 'idx_created_at');
                }
            });
        }
        
        // Add indexes for activities table
        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                // Composite index for buyer activities
                if (!$this->indexExists('activities', 'idx_buyer_activities')) {
                    $table->index(['buyer_id', 'created_at'], 'idx_buyer_activities');
                }
                
                // Index for type filtering
                if (!$this->indexExists('activities', 'idx_type')) {
                    $table->index('type', 'idx_type');
                }
            });
        }
        
        // Add indexes for notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                // Composite index for unread notifications
                if (!$this->indexExists('notifications', 'idx_buyer_unread')) {
                    $table->index(['buyer_id', 'is_read', 'created_at'], 'idx_buyer_unread');
                }
            });
        }
        
        // Add indexes for invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Composite index for buyer invoices
                if (!$this->indexExists('invoices', 'idx_buyer_status')) {
                    $table->index(['buyer_id', 'status'], 'idx_buyer_status');
                }
            });
        }
        
        // Add indexes for products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                // Index for low stock queries
                if (!$this->indexExists('products', 'idx_stock_quantity')) {
                    $table->index('stock_quantity', 'idx_stock_quantity');
                }
                
                // Index for buyer products
                if (!$this->indexExists('products', 'idx_buyer_id')) {
                    $table->index('buyer_id', 'idx_buyer_id');
                }
            });
        }
        
        // Add indexes for messages table
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                // Composite index for unread messages
                if (!$this->indexExists('messages', 'idx_buyer_unread')) {
                    $table->index(['buyer_id', 'is_read', 'created_at'], 'idx_buyer_unread');
                }
            });
        }
        
        // Create dashboard_cache table for persistent caching
        if (!Schema::hasTable('dashboard_cache')) {
            Schema::create('dashboard_cache', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('buyer_id');
                $table->string('cache_key')->index();
                $table->json('data');
                $table->timestamp('expires_at')->index();
                $table->timestamps();
                
                $table->unique(['buyer_id', 'cache_key']);
                $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
            });
        }
        
        // Add database-specific optimizations
        $this->performDatabaseOptimizations();
    }
    
    /**
     * Reverse the migrations
     */
    public function down()
    {
        // Remove indexes from orders table
        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists('orders', 'idx_buyer_dashboard');
            $this->dropIndexIfExists('orders', 'idx_created_at');
            $this->dropIndexIfExists('orders', 'idx_status');
            $this->dropIndexIfExists('orders', 'idx_supplier_id');
            $this->dropIndexIfExists('orders', 'idx_buyer_supplier_date');
        });
        
        // Remove indexes from buyers table
        Schema::table('buyers', function (Blueprint $table) {
            $this->dropIndexIfExists('buyers', 'idx_email');
            $this->dropIndexIfExists('buyers', 'idx_is_active');
        });
        
        // Remove indexes from suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $this->dropIndexIfExists('suppliers', 'idx_is_active');
            $this->dropIndexIfExists('suppliers', 'idx_name');
        });
        
        // Remove indexes from buyer_supplier table
        if (Schema::hasTable('buyer_supplier')) {
            Schema::table('buyer_supplier', function (Blueprint $table) {
                $this->dropIndexIfExists('buyer_supplier', 'idx_buyer_supplier');
                $this->dropIndexIfExists('buyer_supplier', 'idx_created_at');
            });
        }
        
        // Remove indexes from activities table
        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                $this->dropIndexIfExists('activities', 'idx_buyer_activities');
                $this->dropIndexIfExists('activities', 'idx_type');
            });
        }
        
        // Remove indexes from notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $this->dropIndexIfExists('notifications', 'idx_buyer_unread');
            });
        }
        
        // Remove indexes from invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $this->dropIndexIfExists('invoices', 'idx_buyer_status');
            });
        }
        
        // Remove indexes from products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->dropIndexIfExists('products', 'idx_stock_quantity');
                $this->dropIndexIfExists('products', 'idx_buyer_id');
            });
        }
        
        // Remove indexes from messages table
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $this->dropIndexIfExists('messages', 'idx_buyer_unread');
            });
        }
        
        // Drop dashboard_cache table
        Schema::dropIfExists('dashboard_cache');
    }
    
    /**
     * Check if an index exists
     */
    private function indexExists($table, $indexName)
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        
        if ($connection === 'mysql') {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$database, $table, $indexName]);
            
            return $result[0]->count > 0;
        }
        
        // For SQLite or other databases
        try {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Index doesn't exist
        }
        
        return false;
    }
    
    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists($table, $indexName)
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
    
    /**
     * Perform database-specific optimizations
     */
    private function performDatabaseOptimizations()
    {
        $connection = config('database.default');
        
        if ($connection === 'mysql') {
            // Optimize MySQL settings for better performance
            try {
                // Increase query cache size (if available)
                DB::statement("SET GLOBAL query_cache_size = 67108864"); // 64MB
                DB::statement("SET GLOBAL query_cache_type = 1");
                
                // Optimize table statistics
                $tables = ['orders', 'buyers', 'suppliers', 'products', 'activities'];
                foreach ($tables as $table) {
                    if (Schema::hasTable($table)) {
                        DB::statement("ANALYZE TABLE {$table}");
                    }
                }
            } catch (\Exception $e) {
                // Some settings might require SUPER privilege
                // Log the error but don't fail the migration
                \Log::info('Could not optimize MySQL settings: ' . $e->getMessage());
            }
        }
    }
};