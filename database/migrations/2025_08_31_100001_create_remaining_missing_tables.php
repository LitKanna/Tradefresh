<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add buyer_id to invoices table if not exists
        if (!Schema::hasColumn('invoices', 'buyer_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('user_id');
                $table->index('buyer_id');
            });
            
            // Copy user_id to buyer_id for existing records
            DB::statement('UPDATE invoices SET buyer_id = user_id WHERE buyer_id IS NULL');
        }

        // 2. Create cart_items table
        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('buyer_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_price', 12, 2);
                $table->json('options')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->index(['buyer_id']);
                $table->unique(['buyer_id', 'product_id']);
            });
        }

        // 3. Create order_statuses table (for Order->statusHistory relationship)
        if (!Schema::hasTable('order_statuses')) {
            Schema::create('order_statuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('status');
                $table->string('previous_status')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('changed_by_type')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->index(['order_id', 'created_at']);
            });
        }

        // 4. Create delivery_routes table
        if (!Schema::hasTable('delivery_routes')) {
            Schema::create('delivery_routes', function (Blueprint $table) {
                $table->id();
                $table->string('route_number')->unique();
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->date('delivery_date');
                $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
                $table->json('stops')->nullable();
                $table->integer('total_stops')->default(0);
                $table->integer('completed_stops')->default(0);
                $table->decimal('total_distance', 10, 2)->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['delivery_date', 'status']);
                $table->index('driver_id');
            });
        }

        // 5. Create payments table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->morphs('payable');
                $table->unsignedBigInteger('buyer_id')->nullable();
                $table->string('payment_number')->unique();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('AUD');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'partially_refunded'])->default('pending');
                $table->string('payment_method');
                $table->string('gateway')->nullable();
                $table->string('gateway_transaction_id')->nullable();
                $table->json('gateway_response')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['payable_type', 'payable_id']);
                $table->index(['buyer_id', 'status']);
                $table->index('payment_number');
            });
        }

        // 6. Create transactions table
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->morphs('transactionable');
                $table->string('transaction_id')->unique();
                $table->enum('type', ['debit', 'credit', 'refund', 'adjustment']);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_before', 12, 2)->nullable();
                $table->decimal('balance_after', 12, 2)->nullable();
                $table->string('currency', 3)->default('AUD');
                $table->text('description');
                $table->string('reference')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['transactionable_type', 'transactionable_id']);
                $table->index('transaction_id');
                $table->index('type');
            });
        }

        // 7. Create activities table
        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->morphs('subject');
                $table->morphs('causer');
                $table->string('type');
                $table->string('event');
                $table->text('description')->nullable();
                $table->json('properties')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['subject_type', 'subject_id']);
                $table->index(['causer_type', 'causer_id']);
                $table->index('event');
                $table->index('created_at');
            });
        }

        // 8. Add missing columns to orders table
        $orderColumns = [
            'supplier_id' => ['type' => 'unsignedBigInteger', 'nullable' => true, 'after' => 'vendor_id'],
            'buyer_business_id' => ['type' => 'unsignedBigInteger', 'nullable' => true, 'after' => 'buyer_id'],
            'fulfillment_type' => ['type' => 'string', 'default' => 'delivery'],
            'delivery_fee' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
            'pickup_booking_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'delivery_route_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'delivery_address_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'expected_delivery_date' => ['type' => 'datetime', 'nullable' => true],
            'actual_delivery_date' => ['type' => 'datetime', 'nullable' => true],
            'invoice_date' => ['type' => 'date', 'nullable' => true],
            'payment_terms_days' => ['type' => 'integer', 'nullable' => true],
            'payment_due_date' => ['type' => 'date', 'nullable' => true],
            'is_urgent' => ['type' => 'boolean', 'default' => false],
            'buyer_notes' => ['type' => 'text', 'nullable' => true],
            'vendor_notes' => ['type' => 'text', 'nullable' => true],
            'confirmed_at' => ['type' => 'datetime', 'nullable' => true],
            'preparing_at' => ['type' => 'datetime', 'nullable' => true],
            'ready_at' => ['type' => 'datetime', 'nullable' => true],
            'picked_up_at' => ['type' => 'datetime', 'nullable' => true],
            'completed_at' => ['type' => 'datetime', 'nullable' => true],
            'cancelled_by' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'metadata' => ['type' => 'json', 'nullable' => true]
        ];

        foreach ($orderColumns as $column => $config) {
            if (!Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column, $config) {
                    $col = null;
                    switch ($config['type']) {
                        case 'string':
                            $col = $table->string($column);
                            break;
                        case 'text':
                            $col = $table->text($column);
                            break;
                        case 'decimal':
                            $col = $table->decimal($column, $config['precision'], $config['scale']);
                            break;
                        case 'unsignedBigInteger':
                            $col = $table->unsignedBigInteger($column);
                            break;
                        case 'datetime':
                            $col = $table->datetime($column);
                            break;
                        case 'date':
                            $col = $table->date($column);
                            break;
                        case 'integer':
                            $col = $table->integer($column);
                            break;
                        case 'boolean':
                            $col = $table->boolean($column);
                            break;
                        case 'json':
                            $col = $table->json($column);
                            break;
                    }
                    
                    if ($col) {
                        if (isset($config['nullable']) && $config['nullable']) {
                            $col->nullable();
                        }
                        if (isset($config['default'])) {
                            $col->default($config['default']);
                        }
                    }
                });
            }
        }

        // 9. Add missing columns to order_items table
        if (Schema::hasTable('order_items')) {
            $existingColumns = Schema::getColumnListing('order_items');
            $requiredColumns = [
                'product_id' => 'unsignedBigInteger',
                'product_name' => 'string',
                'product_sku' => 'string',
                'vendor_id' => 'unsignedBigInteger',
                'metadata' => 'json'
            ];
            
            foreach ($requiredColumns as $column => $type) {
                if (!in_array($column, $existingColumns)) {
                    Schema::table('order_items', function (Blueprint $table) use ($column, $type) {
                        switch ($type) {
                            case 'unsignedBigInteger':
                                $table->unsignedBigInteger($column)->nullable();
                                break;
                            case 'string':
                                $table->string($column)->nullable();
                                break;
                            case 'json':
                                $table->json($column)->nullable();
                                break;
                        }
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('activities');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('delivery_routes');
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('cart_items');
        
        // Remove added columns
        if (Schema::hasColumn('invoices', 'buyer_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('buyer_id');
            });
        }
        
        if (Schema::hasColumn('orders', 'supplier_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }
        
        if (Schema::hasColumn('orders', 'buyer_business_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('buyer_business_id');
            });
        }
    }
};