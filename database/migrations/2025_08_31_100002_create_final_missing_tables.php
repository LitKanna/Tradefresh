<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create transactions table
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

        // 2. Create activities table
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

        // 3. Add any missing columns to orders table
        $this->addMissingOrderColumns();
        
        // 4. Add any missing columns to order_items table
        $this->addMissingOrderItemsColumns();
    }

    /**
     * Add missing columns to orders table
     */
    private function addMissingOrderColumns(): void
    {
        $orderColumns = [
            'supplier_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'buyer_business_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
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
    }

    /**
     * Add missing columns to order_items table
     */
    private function addMissingOrderItemsColumns(): void
    {
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
        Schema::dropIfExists('activities');
        Schema::dropIfExists('transactions');
    }
};