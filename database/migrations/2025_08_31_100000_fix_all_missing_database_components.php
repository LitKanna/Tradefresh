<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to fix all missing database components
     */
    public function up(): void
    {
        // 1. Create missing tables
        
        // Create categories table (referenced by products)
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique()->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('parent_id');
                $table->index('slug');
                $table->index('is_active');
            });
        }
        
        // Create credit_transactions table
        if (!Schema::hasTable('credit_transactions')) {
            Schema::create('credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['debit', 'credit', 'adjustment']);
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('description');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                
                $table->index(['buyer_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
        
        // Create price_history table
        if (!Schema::hasTable('price_history')) {
            Schema::create('price_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('old_price', 15, 2);
                $table->decimal('new_price', 15, 2);
                $table->decimal('change_amount', 15, 2);
                $table->decimal('change_percentage', 5, 2);
                $table->string('change_reason')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users');
                $table->timestamps();
                
                $table->index(['product_id', 'created_at']);
                $table->index('change_percentage');
            });
        }
        
        // Create favorite_vendors table
        if (!Schema::hasTable('favorite_vendors')) {
            Schema::create('favorite_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
                $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
                $table->integer('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->unique(['buyer_id', 'vendor_id']);
                $table->index('buyer_id');
                $table->index('vendor_id');
            });
        }
        
        // 2. Add missing columns to existing tables
        
        // Add missing columns to buyers table
        if (Schema::hasTable('buyers')) {
            Schema::table('buyers', function (Blueprint $table) {
                if (!Schema::hasColumn('buyers', 'name')) {
                    $table->string('name')->after('id')->nullable();
                }
                if (!Schema::hasColumn('buyers', 'loyalty_points')) {
                    $table->integer('loyalty_points')->default(0)->after('credit_used');
                }
            });
        }
        
        // Add missing columns to orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable()->after('vendor_id')
                        ->constrained()->onDelete('set null');
                }
                if (!Schema::hasColumn('orders', 'expected_delivery_date')) {
                    $table->datetime('expected_delivery_date')->nullable()->after('delivery_route_id');
                }
                if (!Schema::hasColumn('orders', 'delivery_date')) {
                    $table->datetime('delivery_date')->nullable()->after('expected_delivery_date');
                }
                if (!Schema::hasColumn('orders', 'rating')) {
                    $table->decimal('rating', 2, 1)->nullable()->after('status');
                }
                if (!Schema::hasColumn('orders', 'delivered_at')) {
                    // Already exists according to Order model
                }
            });
        }
        
        // Add missing columns to invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'payment_status')) {
                    $table->enum('payment_status', ['pending', 'processing', 'paid', 'partial', 'overdue', 'failed'])
                        ->default('pending')->after('status');
                }
                if (!Schema::hasColumn('invoices', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('paid_date');
                }
            });
        }
        
        // Add missing columns to order_items table
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'original_price')) {
                    $table->decimal('original_price', 15, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('order_items', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('discount_amount');
                }
                if (!Schema::hasColumn('order_items', 'total')) {
                    // This might already exist as 'total_price'
                    if (!Schema::hasColumn('order_items', 'total_price')) {
                        $table->decimal('total', 15, 2)->default(0)->after('subtotal');
                    }
                }
            });
        }
        
        // Fix vendor_categories table structure
        if (Schema::hasTable('vendor_categories')) {
            Schema::table('vendor_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('vendor_categories', 'vendor_id')) {
                    $table->foreignId('vendor_id')->after('id')
                        ->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('vendor_categories', 'category_id')) {
                    $table->foreignId('category_id')->after('vendor_id')
                        ->constrained()->onDelete('cascade');
                }
            });
            
            // Check for unique constraint separately
            $indexExists = collect(\DB::select("PRAGMA index_list('vendor_categories')"))
                ->pluck('name')
                ->contains('vendor_categories_vendor_id_category_id_unique');
            
            if (!$indexExists) {
                Schema::table('vendor_categories', function (Blueprint $table) {
                    $table->unique(['vendor_id', 'category_id']);
                });
            }
        }
        
        // Add missing columns to notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'is_read')) {
                    $table->boolean('is_read')->default(false)->after('read_at');
                }
            });
        }
        
        // Add missing columns to order_status_history table
        if (Schema::hasTable('order_status_history')) {
            Schema::table('order_status_history', function (Blueprint $table) {
                if (!Schema::hasColumn('order_status_history', 'status')) {
                    $table->string('status')->after('order_id');
                }
                if (!Schema::hasColumn('order_status_history', 'notes')) {
                    $table->text('notes')->nullable()->after('status');
                }
                if (!Schema::hasColumn('order_status_history', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('notes')
                        ->constrained()->onDelete('set null');
                }
            });
        }
        
        // 3. Create missing models if they don't exist
        $this->createMissingModels();
        
        // 4. Add indexes for performance optimization
        $this->addPerformanceIndexes();
    }
    
    /**
     * Create model files for missing models
     */
    private function createMissingModels(): void
    {
        // This would typically be done via artisan commands
        // but we're documenting what models need to be created
        $missingModels = [
            'CreditTransaction',
            'PriceHistory',
            'FavoriteVendor',
            'BuyerActivity',
            'DashboardPreference',
            'DashboardMetric',
            'Approval',
            'Address',
            'Notification',
            'Activity',
            'ShoppingList',
            'Business',
            'BusinessUser',
            'PickupBooking'
        ];
        
        // Log which models need to be created
        foreach ($missingModels as $model) {
            $modelPath = app_path("Models/{$model}.php");
            if (!file_exists($modelPath)) {
                // Model needs to be created
                \Log::info("Model needs to be created: {$model}");
            }
        }
    }
    
    /**
     * Add performance indexes
     */
    private function addPerformanceIndexes(): void
    {
        // Add composite indexes for dashboard queries
        // Using SQLite-specific index checking
        
        if (Schema::hasTable('orders')) {
            $existingIndexes = collect(\DB::select("PRAGMA index_list('orders')"))->pluck('name');
            
            Schema::table('orders', function (Blueprint $table) use ($existingIndexes) {
                if (!$existingIndexes->contains('orders_buyer_id_created_at_status_index')) {
                    $table->index(['buyer_id', 'created_at', 'status']);
                }
                if (!$existingIndexes->contains('orders_buyer_id_vendor_id_created_at_index')) {
                    $table->index(['buyer_id', 'vendor_id', 'created_at']);
                }
            });
        }
        
        if (Schema::hasTable('invoices')) {
            $existingIndexes = collect(\DB::select("PRAGMA index_list('invoices')"))->pluck('name');
            
            Schema::table('invoices', function (Blueprint $table) use ($existingIndexes) {
                if (!$existingIndexes->contains('invoices_buyer_id_payment_status_created_at_index')) {
                    $table->index(['buyer_id', 'payment_status', 'created_at']);
                }
                if (!$existingIndexes->contains('invoices_buyer_id_due_date_index')) {
                    $table->index(['buyer_id', 'due_date']);
                }
            });
        }
        
        if (Schema::hasTable('order_items')) {
            $existingIndexes = collect(\DB::select("PRAGMA index_list('order_items')"))->pluck('name');
            
            Schema::table('order_items', function (Blueprint $table) use ($existingIndexes) {
                if (!$existingIndexes->contains('order_items_order_id_product_id_index')) {
                    $table->index(['order_id', 'product_id']);
                }
            });
        }
        
        if (Schema::hasTable('buyer_activities')) {
            $existingIndexes = collect(\DB::select("PRAGMA index_list('buyer_activities')"))->pluck('name');
            
            Schema::table('buyer_activities', function (Blueprint $table) use ($existingIndexes) {
                if (!$existingIndexes->contains('buyer_activities_buyer_id_created_at_index')) {
                    $table->index(['buyer_id', 'created_at']);
                }
                if (!$existingIndexes->contains('buyer_activities_buyer_id_is_read_index')) {
                    $table->index(['buyer_id', 'is_read']);
                }
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop created tables
        Schema::dropIfExists('favorite_vendors');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('categories');
        
        // Remove added columns
        if (Schema::hasTable('buyers')) {
            Schema::table('buyers', function (Blueprint $table) {
                $table->dropColumn(['name', 'loyalty_points']);
            });
        }
        
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn(['supplier_id', 'expected_delivery_date', 'delivery_date', 'rating']);
            });
        }
        
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn(['payment_status', 'paid_at']);
            });
        }
        
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn(['original_price', 'subtotal', 'total']);
            });
        }
        
        if (Schema::hasTable('vendor_categories')) {
            Schema::table('vendor_categories', function (Blueprint $table) {
                $table->dropForeign(['vendor_id']);
                $table->dropForeign(['category_id']);
                $table->dropColumn(['vendor_id', 'category_id']);
            });
        }
        
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }
        
        if (Schema::hasTable('order_status_history')) {
            Schema::table('order_status_history', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['status', 'notes', 'user_id']);
            });
        }
    }
};