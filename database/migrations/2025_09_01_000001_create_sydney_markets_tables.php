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
        // Market vendors table
        Schema::create('market_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('vendor_code')->unique();
            $table->string('contact_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('stall_number')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending']);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('specialties')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->timestamps();
            $table->index(['status', 'is_online']);
        });

        // Product categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color')->default('#4F46E5');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products table
        Schema::create('market_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('market_vendors')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories');
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->string('unit')->default('kg'); // kg, box, bunch, dozen, etc.
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('min_order_quantity', 10, 2)->default(1);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('origin')->nullable();
            $table->string('grade')->nullable(); // Premium, Standard, etc.
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'category_id', 'is_available']);
            $table->index('is_featured');
        });

        // Quote requests
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone');
            $table->string('company_name')->nullable();
            $table->json('products'); // Array of product requests
            $table->text('special_requirements')->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['pending', 'quoted', 'accepted', 'rejected', 'expired']);
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // Quote responses from vendors
        Schema::create('quote_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('market_vendors')->onDelete('cascade');
            $table->json('quoted_items'); // Detailed pricing for each item
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->date('valid_until');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired']);
            $table->timestamps();
            $table->index(['quote_request_id', 'vendor_id']);
        });

        // Market orders
        Schema::create('market_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->foreignId('vendor_id')->constrained('market_vendors')->onDelete('cascade');
            $table->foreignId('quote_response_id')->nullable()->constrained();
            $table->json('order_items');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded']);
            $table->enum('order_status', ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled']);
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('pickup_time')->nullable();
            $table->json('delivery_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['order_status', 'payment_status']);
            $table->index('order_number');
        });

        // Trading hours configuration
        Schema::create('market_trading_hours', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week'); // Monday, Tuesday, etc.
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_trading_day')->default(true);
            $table->text('special_notes')->nullable();
            $table->timestamps();
        });

        // Market announcements
        Schema::create('market_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'urgent']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_announcements');
        Schema::dropIfExists('market_trading_hours');
        Schema::dropIfExists('market_orders');
        Schema::dropIfExists('quote_responses');
        Schema::dropIfExists('quote_requests');
        Schema::dropIfExists('market_products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('market_vendors');
    }
};