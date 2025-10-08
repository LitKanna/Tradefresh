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
        // Create analytics_summary table for dashboard if it doesn't exist
        if (!Schema::hasTable('analytics_summary')) {
            Schema::create('analytics_summary', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('metric_type');
                $table->string('metric_name');
                $table->decimal('value', 20, 2);
                $table->json('breakdown')->nullable();
                $table->timestamps();
                
                $table->unique(['date', 'metric_type', 'metric_name']);
                $table->index(['date', 'metric_type']);
            });
        }

        // Create shipments table if it doesn't exist
        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->string('tracking_number')->unique();
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
                $table->string('carrier')->nullable();
                $table->string('service_type')->nullable();
                $table->enum('status', ['pending', 'processing', 'shipped', 'in_transit', 'delivered', 'returned', 'lost']);
                $table->decimal('weight', 10, 2)->nullable();
                $table->string('weight_unit')->default('lbs');
                $table->json('dimensions')->nullable();
                $table->decimal('shipping_cost', 10, 2)->nullable();
                $table->text('ship_from_address')->nullable();
                $table->text('ship_to_address')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->string('signature')->nullable();
                $table->json('tracking_updates')->nullable();
                $table->timestamps();
                
                $table->index(['tracking_number', 'status']);
                $table->index('order_id');
            });
        }

        // Create purchase_orders table if it doesn't exist
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number')->unique();
                $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
                $table->foreignId('buyer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->enum('status', ['draft', 'pending', 'approved', 'ordered', 'partial', 'received', 'cancelled']);
                $table->decimal('subtotal', 15, 2);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('shipping_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->date('order_date');
                $table->date('expected_date')->nullable();
                $table->date('received_date')->nullable();
                $table->string('ship_to_warehouse')->nullable();
                $table->text('shipping_address')->nullable();
                $table->text('notes')->nullable();
                $table->string('payment_terms')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['po_number', 'status']);
                $table->index('vendor_id');
                $table->index('buyer_id');
            });
        }

        // Create purchase_order_items table if it doesn't exist
        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('item_name');
                $table->string('item_sku')->nullable();
                $table->text('description')->nullable();
                $table->integer('quantity_ordered');
                $table->integer('quantity_received')->default(0);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('total_price', 15, 2);
                $table->string('unit')->default('piece');
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('purchase_order_id');
                $table->index('product_id');
            });
        }

        // Create quotes table if it doesn't exist
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->id();
                $table->string('quote_number')->unique();
                $table->foreignId('buyer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted']);
                $table->decimal('subtotal', 15, 2);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('shipping_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->date('quote_date');
                $table->date('valid_until');
                $table->foreignId('converted_to_order')->nullable()->constrained('orders')->onDelete('set null');
                $table->foreignId('converted_to_invoice')->nullable()->constrained('invoices')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['quote_number', 'status']);
                $table->index('buyer_id');
                $table->index('vendor_id');
            });
        }

        // Create quote_items table if it doesn't exist
        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quote_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('item_name');
                $table->text('description')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_price', 15, 2);
                $table->string('unit')->default('piece');
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('quote_id');
                $table->index('product_id');
            });
        }

        // Create customers table if it doesn't exist
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code')->unique();
                $table->string('company_name');
                $table->string('contact_name')->nullable();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->text('billing_address')->nullable();
                $table->text('shipping_address')->nullable();
                $table->string('tax_id')->nullable();
                $table->decimal('credit_limit', 15, 2)->nullable();
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->string('payment_terms')->nullable();
                $table->integer('payment_days')->default(30);
                $table->enum('status', ['active', 'inactive', 'on_hold', 'blocked']);
                $table->enum('customer_type', ['wholesale', 'retail', 'distributor', 'manufacturer']);
                $table->foreignId('assigned_salesperson')->nullable()->constrained('users')->onDelete('set null');
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['customer_code', 'status']);
                $table->index('email');
            });
        }

        // Create warehouses table if it doesn't exist
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('address');
                $table->string('city');
                $table->string('state');
                $table->string('zip_code');
                $table->string('country')->default('USA');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('type')->default('main');
                $table->boolean('is_active')->default(true);
                $table->json('operating_hours')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();
                
                $table->index(['code', 'is_active']);
            });
        }

        // Create inventory table if it doesn't exist
        if (!Schema::hasTable('inventory')) {
            Schema::create('inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
                $table->integer('quantity_on_hand')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('quantity_available')->default(0);
                $table->integer('reorder_point')->default(0);
                $table->integer('reorder_quantity')->default(0);
                $table->string('location')->nullable();
                $table->string('bin_number')->nullable();
                $table->date('last_restocked')->nullable();
                $table->date('last_counted')->nullable();
                $table->timestamps();
                
                $table->unique(['product_id', 'warehouse_id']);
                $table->index('warehouse_id');
            });
        }

        // Create payments table if it doesn't exist
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_number')->unique();
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('buyer_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('amount', 15, 2);
                $table->string('currency', 3)->default('USD');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'partial_refund']);
                $table->string('payment_method');
                $table->string('reference_number')->nullable();
                $table->string('transaction_id')->nullable();
                $table->json('gateway_response')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                
                $table->index(['payment_number', 'status']);
                $table->index('invoice_id');
                $table->index('user_id');
                $table->index('buyer_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('analytics_summary');
    }
};