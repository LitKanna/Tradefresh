<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Complete Sydney Markets B2B Platform Database Schema
     * Consolidated and optimized for production use
     */
    public function up(): void
    {
        // Disable foreign key checks for clean migration
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // ========================================
        // CORE BUSINESS ENTITIES
        // ========================================
        
        // Businesses table - Core entity for all business types
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('abn', 11)->unique()->index();
            $table->string('business_name');
            $table->string('trading_name')->nullable();
            $table->enum('business_type', ['vendor', 'buyer', 'both'])->default('buyer');
            $table->enum('entity_type', ['sole_trader', 'partnership', 'company', 'trust']);
            $table->date('abn_registration_date')->nullable();
            $table->boolean('gst_registered')->default(false);
            $table->string('acn', 9)->nullable();
            
            // Contact Information
            $table->string('primary_email')->unique();
            $table->string('secondary_email')->nullable();
            $table->string('phone');
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            
            // Address Information
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('suburb');
            $table->string('state', 3);
            $table->string('postcode', 4);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Business Details
            $table->text('business_description')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('delivery_areas')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            
            // Status and Verification
            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            // Subscription and Billing
            $table->enum('subscription_tier', ['free', 'basic', 'premium', 'enterprise'])->default('free');
            $table->date('subscription_expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            
            // Compliance and Documentation
            $table->json('certifications')->nullable();
            $table->json('insurance_details')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->boolean('haccp_certified')->default(false);
            $table->boolean('organic_certified')->default(false);
            
            // Performance Metrics
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->default(100);
            
            // Settings and Preferences
            $table->json('notification_preferences')->nullable();
            $table->json('payment_methods')->nullable();
            $table->string('preferred_currency', 3)->default('AUD');
            $table->string('timezone')->default('Australia/Sydney');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['business_type', 'status']);
            $table->index(['suburb', 'state']);
            $table->index('created_at');
            $table->index(['latitude', 'longitude']);
            // SQLite incompatible: // ->fullText(['business_name', 'trading_name']);
        });
        
        // Users table - All system users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            
            // User Type and Role
            $table->enum('user_type', ['admin', 'vendor', 'buyer', 'driver', 'staff']);
            $table->string('role')->nullable();
            $table->json('permissions')->nullable();
            
            // Profile Information
            $table->string('avatar')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('employee_id')->nullable();
            
            // Authentication and Security
            $table->string('remember_token')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            
            // Preferences
            $table->json('preferences')->nullable();
            $table->string('language', 5)->default('en');
            $table->string('timezone')->default('Australia/Sydney');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            
            // Indexes
            $table->index('business_id');
            $table->index('user_type');
            $table->index(['email', 'password']);
            $table->index('last_login_at');
        });
        
        // ========================================
        // PRODUCT CATALOG
        // ========================================
        
        // Categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Self-referencing foreign key
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            
            // Indexes
            $table->index('slug');
            $table->index('parent_id');
            $table->index(['is_active', 'sort_order']);
        });
        
        // Products table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            
            // Pricing
            $table->decimal('base_price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('price_unit')->default('each');
            $table->boolean('is_negotiable')->default(false);
            
            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_order_quantity')->default(1);
            $table->integer('max_order_quantity')->nullable();
            $table->integer('step_quantity')->default(1);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->integer('low_stock_threshold')->default(10);
            
            // Product Details
            $table->string('unit_of_measure')->default('each');
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->json('dimensions')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            
            // Images and Media
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->json('documents')->nullable();
            
            // Attributes
            $table->json('attributes')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->json('allergens')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('shelf_life_days')->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // Status and Visibility
            $table->enum('status', ['draft', 'active', 'inactive', 'out_of_stock'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_organic')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('requires_refrigeration')->default(false);
            
            // Performance
            $table->integer('view_count')->default(0);
            $table->integer('order_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories');
            
            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('sku');
            $table->index(['is_featured', 'status']);
            $table->index('created_at');
            // SQLite incompatible: // ->fullText(['name', 'description']);
        });
        
        // Product price tiers for bulk pricing
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['product_id', 'min_quantity']);
        });
        
        // ========================================
        // ORDER MANAGEMENT
        // ========================================
        
        // Orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('buyer_business_id');
            $table->unsignedBigInteger('vendor_business_id');
            $table->unsignedBigInteger('placed_by_user_id');
            
            // Order Details
            $table->enum('order_type', ['standard', 'express', 'scheduled', 'recurring']);
            $table->enum('status', [
                'draft', 'pending', 'confirmed', 'processing', 
                'ready', 'dispatched', 'delivered', 'completed', 
                'cancelled', 'refunded'
            ])->default('pending');
            
            // Dates
            $table->timestamp('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Financials
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('AUD');
            
            // Payment
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue', 'refunded'])->default('pending');
            $table->enum('payment_method', ['credit', 'card', 'bpay', 'bank_transfer', 'cash'])->nullable();
            $table->date('payment_due_date')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            
            // Delivery
            $table->enum('delivery_method', ['pickup', 'delivery', 'express'])->default('pickup');
            $table->text('delivery_address')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->unsignedBigInteger('pickup_bay_id')->nullable();
            $table->unsignedBigInteger('pickup_slot_id')->nullable();
            $table->string('tracking_number')->nullable();
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('po_number')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('buyer_business_id')->references('id')->on('businesses');
            $table->foreign('vendor_business_id')->references('id')->on('businesses');
            $table->foreign('placed_by_user_id')->references('id')->on('users');
            
            // Indexes
            $table->index('order_number');
            $table->index(['buyer_business_id', 'status']);
            $table->index(['vendor_business_id', 'status']);
            $table->index(['status', 'order_date']);
            $table->index('payment_status');
            $table->index('requested_delivery_date');
        });
        
        // Order items table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            
            // Item Details
            $table->string('product_name');
            $table->string('product_sku');
            $table->integer('quantity');
            $table->string('unit_of_measure')->default('each');
            
            // Pricing
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'packed', 'delivered', 'returned'])->default('pending');
            $table->integer('quantity_delivered')->default(0);
            $table->integer('quantity_returned')->default(0);
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('product_snapshot')->nullable(); // Store product details at time of order
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            
            // Indexes
            $table->index(['order_id', 'status']);
            $table->index('product_id');
        });
        
        // ========================================
        // PICKUP BAY MANAGEMENT
        // ========================================
        
        // Pickup zones
        Schema::create('pickup_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->string('location_description')->nullable();
            $table->integer('total_bays')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('operating_hours')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'code']);
        });
        
        // Pickup bays
        Schema::create('pickup_bays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->string('bay_number');
            $table->string('bay_code')->unique();
            $table->enum('size', ['small', 'medium', 'large', 'xlarge'])->default('medium');
            $table->enum('type', ['standard', 'refrigerated', 'frozen', 'hazmat'])->default('standard');
            $table->boolean('is_covered')->default(true);
            $table->boolean('has_loading_dock')->default(false);
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_height_m', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
            $table->json('amenities')->nullable();
            $table->timestamps();
            
            $table->foreign('zone_id')->references('id')->on('pickup_zones');
            $table->unique(['zone_id', 'bay_number']);
            $table->index(['zone_id', 'status', 'is_active']);
        });
        
        // Pickup time slots
        Schema::create('pickup_time_slots', function (Blueprint $table) {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->integer('max_bookings')->default(10);
            $table->json('available_days'); // ['monday', 'tuesday', ...]
            $table->boolean('is_peak')->default(false);
            $table->decimal('peak_surcharge', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['start_time', 'end_time', 'is_active']);
        });
        
        // Pickup bookings
        Schema::create('pickup_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('bay_id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('booked_by_user_id');
            
            // Booking Details
            $table->date('pickup_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->timestamp('actual_arrival_time')->nullable();
            $table->timestamp('actual_departure_time')->nullable();
            
            // Vehicle Information
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'confirmed', 'arrived', 'loading', 
                'completed', 'no_show', 'cancelled'
            ])->default('pending');
            
            // Additional Info
            $table->text('special_requirements')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('sms_reminder_sent')->default(false);
            $table->boolean('email_reminder_sent')->default(false);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('bay_id')->references('id')->on('pickup_bays');
            $table->foreign('slot_id')->references('id')->on('pickup_time_slots');
            $table->foreign('booked_by_user_id')->references('id')->on('users');
            
            // Indexes
            $table->index('booking_reference');
            $table->index(['pickup_date', 'status']);
            $table->index(['bay_id', 'pickup_date']);
            $table->index(['slot_id', 'pickup_date']);
        });
        
        // ========================================
        // DELIVERY MANAGEMENT
        // ========================================
        
        // Delivery zones
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->json('postcodes'); // Array of postcodes
            $table->json('suburbs'); // Array of suburbs
            $table->decimal('base_delivery_fee', 10, 2)->default(0);
            $table->decimal('per_km_rate', 8, 2)->default(0);
            $table->integer('estimated_minutes')->default(30);
            $table->boolean('express_available')->default(true);
            $table->decimal('express_surcharge', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('delivery_days')->nullable();
            $table->json('cutoff_times')->nullable();
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
        });
        
        // Delivery drivers
        Schema::create('delivery_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('driver_code')->unique();
            $table->string('license_number');
            $table->date('license_expiry');
            $table->string('vehicle_type');
            $table->string('vehicle_registration');
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->integer('vehicle_year')->nullable();
            $table->decimal('max_load_kg', 10, 2);
            $table->boolean('has_refrigeration')->default(false);
            $table->boolean('has_freezer')->default(false);
            
            // Driver Status
            $table->enum('status', ['available', 'on_route', 'break', 'offline'])->default('offline');
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();
            
            // Performance
            $table->integer('total_deliveries')->default(0);
            $table->decimal('on_time_rate', 5, 2)->default(100);
            $table->decimal('average_rating', 3, 2)->default(5);
            $table->integer('total_ratings')->default(0);
            
            // Availability
            $table->json('working_days')->nullable();
            $table->time('shift_start')->nullable();
            $table->time('shift_end')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['status', 'is_active']);
            $table->index(['current_latitude', 'current_longitude']);
        });
        
        // Delivery routes
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_code')->unique();
            $table->unsignedBigInteger('driver_id');
            $table->date('delivery_date');
            $table->time('planned_start_time');
            $table->time('planned_end_time');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            
            // Route Details
            $table->integer('total_stops')->default(0);
            $table->integer('completed_stops')->default(0);
            $table->decimal('total_distance_km', 10, 2)->default(0);
            $table->decimal('estimated_duration_hours', 5, 2)->default(0);
            $table->json('optimized_sequence')->nullable();
            
            // Status
            $table->enum('status', [
                'planning', 'assigned', 'started', 'in_progress', 
                'completed', 'cancelled'
            ])->default('planning');
            
            $table->timestamps();
            
            $table->foreign('driver_id')->references('id')->on('delivery_drivers');
            $table->index(['driver_id', 'delivery_date']);
            $table->index(['delivery_date', 'status']);
        });
        
        // Deliveries
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('zone_id');
            
            // Delivery Details
            $table->integer('stop_sequence')->nullable();
            $table->text('delivery_address');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('delivery_instructions')->nullable();
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'assigned', 'en_route', 'arrived', 
                'delivered', 'failed', 'returned'
            ])->default('pending');
            
            // Proof of Delivery
            $table->string('signature_image')->nullable();
            $table->string('photo_proof')->nullable();
            $table->string('received_by')->nullable();
            $table->text('delivery_notes')->nullable();
            
            // Issues
            $table->boolean('has_issue')->default(false);
            $table->text('issue_description')->nullable();
            $table->enum('issue_type', [
                'damaged', 'wrong_item', 'missing_item', 
                'refused', 'not_home', 'address_issue'
            ])->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('route_id')->references('id')->on('delivery_routes')->nullOnDelete();
            $table->foreign('driver_id')->references('id')->on('delivery_drivers')->nullOnDelete();
            $table->foreign('zone_id')->references('id')->on('delivery_zones');
            
            // Indexes
            $table->index('tracking_number');
            $table->index(['route_id', 'stop_sequence']);
            $table->index(['driver_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
        
        // ========================================
        // PAYMENT AND FINANCIAL
        // ========================================
        
        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('order_id')->nullable();
            
            // Invoice Details
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            
            // Amounts
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2);
            
            // Payment Information
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            
            // Indexes
            $table->index('invoice_number');
            $table->index(['business_id', 'status']);
            $table->index(['status', 'due_date']);
        });
        
        // Payment transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->morphs('payable'); // Can be linked to orders, invoices, etc.
            $table->unsignedBigInteger('business_id');
            
            // Transaction Details
            $table->enum('type', ['payment', 'refund', 'credit', 'debit', 'adjustment']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled']);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('AUD');
            
            // Payment Method
            $table->enum('payment_method', [
                'credit_card', 'debit_card', 'bank_transfer', 
                'bpay', 'paypal', 'account_credit', 'cash'
            ]);
            $table->string('gateway')->nullable(); // stripe, paypal, etc.
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Card Details (encrypted)
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable();
            
            // Additional Info
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('business_id')->references('id')->on('businesses');
            
            // Indexes
            $table->index('transaction_id');
            $table->index(['business_id', 'status']);
            $table->index(['payable_type', 'payable_id']);
            $table->index('created_at');
        });
        
        // Credit accounts
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('available_credit', 12, 2)->default(0);
            $table->decimal('used_credit', 12, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->date('next_review_date')->nullable();
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('businesses');
            $table->unique('business_id');
        });
        
        // ========================================
        // COMMUNICATION AND NOTIFICATIONS
        // ========================================
        
        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('id_hash')->unique();
            $table->morphs('notifiable');
            $table->string('type');
            $table->string('channel')->default('database');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['type', 'created_at']);
            $table->index('read_at');
        });
        
        // Messages (Internal messaging system)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_important')->default(false);
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('sender_id')->references('id')->on('users');
            $table->foreign('recipient_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reply_to_id')->references('id')->on('messages')->nullOnDelete();
            
            // Indexes
            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'is_read']);
            $table->index('conversation_id');
        });
        
        // ========================================
        // ANALYTICS AND REPORTING
        // ========================================
        
        // Analytics events
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('event_category');
            $table->morphs('trackable');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['event_type', 'occurred_at']);
            $table->index(['trackable_type', 'trackable_id']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('session_id');
        });
        
        // ========================================
        // SYSTEM TABLES
        // ========================================
        
        // Activity logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->morphs('subject');
            $table->morphs('causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('log_name');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('created_at');
        });
        
        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        
        // Jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        
        // Failed jobs
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        
        // Cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
        
        // Cache locks
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
        
        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Drop tables in reverse order to avoid foreign key conflicts
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('credit_accounts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('delivery_routes');
        Schema::dropIfExists('delivery_drivers');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('pickup_bookings');
        Schema::dropIfExists('pickup_time_slots');
        Schema::dropIfExists('pickup_bays');
        Schema::dropIfExists('pickup_zones');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('businesses');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};