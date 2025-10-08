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
        // Fix invoices table - add buyer_id column
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'buyer_id')) {
                $table->foreignId('buyer_id')->nullable()->after('user_id')->constrained('buyers')->onDelete('set null');
                $table->index('buyer_id');
            }
            
            // Add vendor_id if missing
            if (!Schema::hasColumn('invoices', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('buyer_id')->constrained('vendors')->onDelete('set null');
                $table->index('vendor_id');
            }
        });

        // Create products table if it doesn't exist
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 15, 2);
                $table->decimal('cost', 15, 2)->nullable();
                $table->integer('quantity')->default(0);
                $table->integer('min_quantity')->default(0);
                $table->string('unit')->default('piece');
                $table->string('barcode')->nullable();
                $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('category_id')->nullable()->constrained('vendor_categories')->onDelete('set null');
                $table->string('image')->nullable();
                $table->json('images')->nullable();
                $table->json('attributes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->decimal('weight', 10, 3)->nullable();
                $table->string('weight_unit')->nullable();
                $table->json('dimensions')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['sku', 'is_active']);
                $table->index('vendor_id');
                $table->index('category_id');
                $table->fullText(['name', 'description']);
            });
        } else {
            // Add missing columns to existing products table
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'cost')) {
                    $table->decimal('cost', 15, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('products', 'min_quantity')) {
                    $table->integer('min_quantity')->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('products', 'unit')) {
                    $table->string('unit')->default('piece')->after('min_quantity');
                }
                if (!Schema::hasColumn('products', 'weight')) {
                    $table->decimal('weight', 10, 3)->nullable();
                }
                if (!Schema::hasColumn('products', 'weight_unit')) {
                    $table->string('weight_unit')->nullable();
                }
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
                $table->string('type')->default('main'); // main, satellite, dropship
                $table->boolean('is_active')->default(true);
                $table->json('operating_hours')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();
                
                $table->index(['code', 'is_active']);
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
                $table->string('payment_method'); // credit_card, bank_transfer, check, cash, paypal
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

        // Create customers table if it doesn't exist (for B2B relationships)
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

        // Create shipments table if it doesn't exist
        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->string('tracking_number')->unique();
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
                $table->string('carrier')->nullable(); // UPS, FedEx, USPS, DHL
                $table->string('service_type')->nullable(); // Ground, Express, Overnight
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

        // Fix orders table - ensure all relationships exist
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'buyer_id')) {
                $table->foreignId('buyer_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
                $table->index('buyer_id');
            }
            
            if (!Schema::hasColumn('orders', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('buyer_id')->constrained()->onDelete('set null');
                $table->index('vendor_id');
            }
            
            if (!Schema::hasColumn('orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('vendor_id')->constrained()->onDelete('set null');
            }
        });

        // Fix order_items table - ensure product relationship exists
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('order_id')->constrained()->onDelete('set null');
                $table->index('product_id');
            }
        });

        // Create analytics_summary table for dashboard
        if (!Schema::hasTable('analytics_summary')) {
            Schema::create('analytics_summary', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('metric_type'); // revenue, orders, customers, products
                $table->string('metric_name');
                $table->decimal('value', 20, 2);
                $table->json('breakdown')->nullable();
                $table->timestamps();
                
                $table->unique(['date', 'metric_type', 'metric_name']);
                $table->index(['date', 'metric_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('analytics_summary');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('products');
        
        // Remove added columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['buyer_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['buyer_id', 'vendor_id', 'warehouse_id']);
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'buyer_id')) {
                $table->dropForeign(['buyer_id']);
                $table->dropColumn('buyer_id');
            }
            if (Schema::hasColumn('invoices', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};