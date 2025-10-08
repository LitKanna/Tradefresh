<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sydney Markets B2B Marketplace - Optimized 30-Table Schema
     * This consolidates 106 tables down to 30 essential tables for B2B fresh produce trading
     */
    public function up(): void
    {
        // 1. BUYERS TABLE - Business buyers at Sydney Markets
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            
            // Business Information
            $table->string('abn', 20)->nullable();
            $table->string('business_name', 100);
            $table->enum('business_type', ['company', 'partnership', 'sole_trader', 'trust']);
            $table->enum('buyer_type', ['owner', 'manager', 'buyer', 'authorized_rep']);
            
            // Contact Information
            $table->string('contact_name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 20);
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            
            // Address
            $table->string('address', 200);
            $table->string('suburb', 50);
            $table->string('state', 3);
            $table->string('postcode', 4);
            
            // Denormalized Performance Fields
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->date('last_order_date')->nullable();
            
            // Status & System
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('abn');
            $table->index('business_name');
            $table->index('status');
        });

        // 2. VENDORS TABLE - Sellers at Sydney Markets
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            
            // Business Information
            $table->string('abn', 20);
            $table->string('business_name', 100);
            $table->enum('business_type', ['company', 'partnership', 'sole_trader', 'trust']);
            $table->string('stall_number', 20)->nullable();
            
            // Contact Information
            $table->string('contact_name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 20);
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            
            // Address
            $table->string('address', 200);
            $table->string('suburb', 50);
            $table->string('state', 3);
            $table->string('postcode', 4);
            
            // Denormalized Performance Fields
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            
            // Status & System
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('verification_status', ['unverified', 'verified'])->default('unverified');
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('abn');
            $table->index('business_name');
            $table->index(['status', 'verification_status']);
            $table->index('rating_average');
        });

        // 3. ADMINS TABLE - Platform administrators
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'support']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
        });

        // 4. CATEGORIES TABLE - Product categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('description', 200)->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // 5. PRODUCTS TABLE - Master product catalog
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('unit', 20); // kg, bunch, box, tray, etc.
            $table->string('image_url')->nullable();
            
            // Denormalized price tracking
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('avg_price', 10, 2)->nullable();
            $table->integer('vendor_count')->default(0);
            $table->timestamp('last_price_update')->nullable();
            
            $table->boolean('is_seasonal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('category_id');
            $table->index('slug');
            $table->index('name');
            $table->index(['is_active', 'category_id']);
            $table->index('vendor_count');
        });

        // 6. VENDOR_PRODUCTS TABLE - Vendor-specific offerings
        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Pricing & Inventory
            $table->decimal('price', 10, 2);
            $table->decimal('min_order_quantity', 10, 2)->default(1);
            $table->decimal('stock_quantity', 10, 2)->nullable();
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock'])->default('in_stock');
            
            // Quality & Grading
            $table->enum('quality_grade', ['premium', 'standard', 'economy'])->default('standard');
            $table->string('origin', 50)->nullable(); // Farm/Region
            $table->date('harvest_date')->nullable();
            
            $table->boolean('is_organic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Composite unique constraint
            $table->unique(['vendor_id', 'product_id']);
            
            // Indexes
            $table->index(['vendor_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
            $table->index('price');
            $table->index('stock_status');
            $table->index(['product_id', 'price']); // For price comparisons
        });

        // 7. PRODUCT_IMAGES TABLE - Additional product images
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_product_id')->constrained('vendor_products')->onDelete('cascade');
            $table->string('image_url');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['vendor_product_id', 'sort_order']);
        });

        // 8. RFQS TABLE - Request for Quotes
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 20)->unique();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('delivery_date');
            $table->string('delivery_location', 200);
            
            $table->enum('status', ['draft', 'open', 'closed', 'awarded', 'cancelled'])->default('draft');
            $table->timestamp('closes_at');
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('rfq_number');
            $table->index(['buyer_id', 'status']);
            $table->index('status');
            $table->index('closes_at');
        });

        // 9. RFQ_ITEMS TABLE - Line items in RFQ
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20);
            $table->string('specifications', 500)->nullable();
            $table->enum('quality_required', ['premium', 'standard', 'economy', 'any'])->default('any');
            
            $table->timestamps();
            
            $table->index(['rfq_id', 'product_id']);
        });

        // 10. QUOTES TABLE - Vendor responses to RFQs
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            
            $table->decimal('total_amount', 10, 2);
            $table->date('valid_until');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'accepted', 'rejected', 'expired'])->default('draft');
            
            // Quote items stored as JSON for simplicity
            $table->json('items'); // [{product_id, quantity, unit_price, total}]
            
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            
            // Unique: One quote per vendor per RFQ
            $table->unique(['rfq_id', 'vendor_id']);
            
            // Indexes
            $table->index('quote_number');
            $table->index(['rfq_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // 11. ORDERS TABLE - Purchase orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('buyer_id')->constrained()->onDelete('restrict');
            $table->foreignId('vendor_id')->constrained()->onDelete('restrict');
            $table->foreignId('quote_id')->nullable()->constrained();
            
            // Order Details
            $table->decimal('subtotal', 10, 2);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            
            // Delivery
            $table->date('delivery_date');
            $table->string('delivery_address', 200);
            $table->string('delivery_instructions', 500)->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'ready', 
                'delivered', 'completed', 'cancelled', 'refunded'
            ])->default('pending');
            
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded'])->default('pending');
            $table->enum('payment_terms', ['immediate', 'net_7', 'net_14', 'net_30'])->default('immediate');
            
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('order_number');
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('delivery_date');
            $table->index('payment_status');
            
            // Covering index for vendor dashboard
            $table->index(['vendor_id', 'status', 'created_at']);
        });

        // 12. ORDER_ITEMS TABLE - Order line items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            
            $table->string('product_name', 100); // Denormalized for history
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->string('quality_grade', 20)->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'product_id']);
        });

        // 13. ORDER_STATUS_LOGS TABLE - Order status history
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status', 20);
            $table->string('changed_by_type', 20); // buyer, vendor, admin, system
            $table->unsignedBigInteger('changed_by_id');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'created_at']);
        });

        // 14. DELIVERIES TABLE - Delivery tracking
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('vehicle_number', 20)->nullable();
            
            $table->enum('status', ['scheduled', 'in_transit', 'delivered', 'failed'])->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->string('delivery_proof_url')->nullable(); // Photo/signature
            $table->string('recipient_name', 100)->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index('scheduled_at');
        });

        // 15. RETURNS TABLE - Returns and refunds
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 20)->unique();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('buyer_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            
            $table->enum('reason', ['damaged', 'quality_issue', 'wrong_item', 'not_as_described', 'other']);
            $table->text('description');
            $table->json('items'); // [{order_item_id, quantity, reason}]
            
            $table->decimal('refund_amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            
            $table->string('photo_proof_url')->nullable();
            $table->text('vendor_response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            
            $table->index('return_number');
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });

        // 16. INVOICES TABLE - Tax invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 20)->unique();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('buyer_id')->constrained();
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            $table->timestamps();
            
            $table->index('invoice_number');
            $table->index(['vendor_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        // 17. INVOICE_ITEMS TABLE - Invoice line items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items');
            
            $table->string('description', 200);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });

        // 18. PAYMENTS TABLE - Consolidated payment records
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 20)->unique();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('buyer_id')->constrained();
            
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['credit_card', 'debit_card', 'bank_transfer', 'paypal', 'account'])->default('credit_card');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            
            // Stripe integration
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            
            $table->string('reference_number')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('payment_number');
            $table->index('invoice_id');
            $table->index(['buyer_id', 'status']);
            $table->index('stripe_payment_intent_id');
        });

        // 19. PAYMENT_METHODS TABLE - Stored payment methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable'); // buyer_id or vendor_id
            
            $table->enum('type', ['card', 'bank_account']);
            $table->string('stripe_payment_method_id')->nullable();
            
            // Card details (masked)
            $table->string('card_brand', 20)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            
            // Bank details (masked)
            $table->string('bank_name', 50)->nullable();
            $table->string('account_last4', 4)->nullable();
            
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['payable_type', 'payable_id']);
        });

        // 20. VENDOR_RATINGS TABLE - Buyer ratings of vendors
        Schema::create('vendor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            $table->tinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            
            // Detailed ratings
            $table->tinyInteger('quality_rating')->nullable();
            $table->tinyInteger('delivery_rating')->nullable();
            $table->tinyInteger('service_rating')->nullable();
            
            $table->timestamps();
            
            // One rating per order
            $table->unique(['order_id']);
            
            $table->index(['vendor_id', 'rating']);
            $table->index(['buyer_id', 'vendor_id']);
        });

        // 21. FAVORITE_VENDORS TABLE - Buyer's preferred vendors
        Schema::create('favorite_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['buyer_id', 'vendor_id']);
            $table->index('buyer_id');
        });

        // 22. PRICE_ALERTS TABLE - Price change notifications
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->decimal('target_price', 10, 2)->nullable();
            $table->enum('alert_type', ['below', 'above', 'any_change'])->default('below');
            $table->boolean('is_active')->default(true);
            
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['buyer_id', 'product_id']);
            $table->index(['product_id', 'is_active']);
        });

        // 23. CARTS TABLE - Shopping carts
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('buyer_id');
            $table->index('session_id');
            $table->index('expires_at');
        });

        // 24. CART_ITEMS TABLE - Cart line items
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_product_id')->constrained('vendor_products');
            
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->timestamps();
            
            $table->unique(['cart_id', 'vendor_product_id']);
            $table->index('cart_id');
        });

        // 25. SESSIONS TABLE - User sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('user_type', 20)->nullable(); // buyer, vendor, admin
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            
            $table->index(['user_id', 'user_type']);
        });

        // 26. PASSWORD_RESETS TABLE - Password reset tokens
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->string('user_type', 20); // buyer, vendor, admin
            $table->timestamp('created_at')->nullable();
            
            $table->index(['email', 'user_type']);
        });

        // 27. NOTIFICATIONS TABLE - System notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
        });

        // 28. AUDIT_LOGS TABLE - Critical action logging
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditable');
            $table->morphs('user');
            $table->string('event', 50);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamps();
            
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_type', 'user_id']);
            $table->index('event');
            $table->index('created_at');
        });

        // 29. SETTINGS TABLE - System configuration
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('type', 20)->default('string'); // string, boolean, integer, json
            $table->string('group', 50)->default('general');
            $table->string('description', 500)->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index('group');
        });

        // 30. MIGRATIONS TABLE - Created by Laravel
        // Already exists - no need to create

        // Seed initial categories
        $this->seedCategories();
        
        // Seed initial settings
        $this->seedSettings();
    }

    /**
     * Seed initial categories for Sydney Markets
     */
    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Fruits', 'slug' => 'fruits', 'sort_order' => 1],
            ['name' => 'Vegetables', 'slug' => 'vegetables', 'sort_order' => 2],
            ['name' => 'Herbs', 'slug' => 'herbs', 'sort_order' => 3],
            ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs', 'sort_order' => 4],
            ['name' => 'Meat & Seafood', 'slug' => 'meat-seafood', 'sort_order' => 5],
            ['name' => 'Bakery', 'slug' => 'bakery', 'sort_order' => 6],
            ['name' => 'Beverages', 'slug' => 'beverages', 'sort_order' => 7],
            ['name' => 'Dry Goods', 'slug' => 'dry-goods', 'sort_order' => 8],
            ['name' => 'Frozen Foods', 'slug' => 'frozen-foods', 'sort_order' => 9],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                ...$category,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Seed initial system settings
     */
    private function seedSettings(): void
    {
        $settings = [
            [
                'key' => 'platform_name',
                'value' => 'Sydney Markets B2B',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Platform display name'
            ],
            [
                'key' => 'gst_rate',
                'value' => '10',
                'type' => 'integer',
                'group' => 'billing',
                'description' => 'GST percentage rate'
            ],
            [
                'key' => 'min_order_amount',
                'value' => '50',
                'type' => 'integer',
                'group' => 'orders',
                'description' => 'Minimum order amount in AUD'
            ],
            [
                'key' => 'rfq_auto_close_hours',
                'value' => '72',
                'type' => 'integer',
                'group' => 'rfq',
                'description' => 'Hours before RFQ auto-closes'
            ],
            [
                'key' => 'cart_expiry_hours',
                'value' => '24',
                'type' => 'integer',
                'group' => 'cart',
                'description' => 'Hours before cart expires'
            ],
            [
                'key' => 'stripe_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'payment',
                'description' => 'Enable Stripe payment processing'
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                ...$setting,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('favorite_vendors');
        Schema::dropIfExists('vendor_ratings');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('vendor_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('buyers');
    }
};