<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhanced indexes for high-performance B2B operations
     */
    public function up(): void
    {
        // Products table performance indexes
        Schema::table('products', function (Blueprint $table) {
            // Full-text search indexes for product search optimization
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE products ADD FULLTEXT(name, description, brand)');
                DB::statement('ALTER TABLE products ADD FULLTEXT(sku)');
            }
            
            // Composite indexes for common query patterns
            $table->index(['is_active', 'stock_quantity', 'price'], 'idx_products_active_stock_price');
            $table->index(['category_id', 'is_active', 'stock_quantity'], 'idx_products_category_active_stock');
            $table->index(['vendor_id', 'is_active', 'stock_quantity'], 'idx_products_vendor_active_stock');
            $table->index(['brand', 'is_active'], 'idx_products_brand_active');
            $table->index(['origin_country', 'is_active'], 'idx_products_origin_active');
            $table->index(['quality_grade', 'is_active'], 'idx_products_quality_active');
            $table->index(['is_featured', 'is_active', 'created_at'], 'idx_products_featured_active_created');
            $table->index(['views_count', 'is_active'], 'idx_products_popularity');
            
            // Search optimization indexes
            $table->index(['name', 'is_active'], 'idx_products_name_active');
            $table->index(['sku', 'is_active'], 'idx_products_sku_active');
            
            // Price range queries
            $table->index(['price', 'is_active', 'stock_quantity'], 'idx_products_price_active_stock');
        });

        // Orders table performance indexes
        Schema::table('orders', function (Blueprint $table) {
            // Composite indexes for dashboard queries
            $table->index(['buyer_id', 'status', 'created_at'], 'idx_orders_buyer_status_created');
            $table->index(['vendor_id', 'status', 'created_at'], 'idx_orders_vendor_status_created');
            $table->index(['status', 'payment_status', 'delivery_date'], 'idx_orders_status_payment_delivery');
            $table->index(['payment_status', 'payment_due_date'], 'idx_orders_payment_due');
            $table->index(['delivery_date', 'status'], 'idx_orders_delivery_status');
            $table->index(['total_amount', 'status'], 'idx_orders_amount_status');
            
            // Analytics and reporting indexes
            $table->index(['created_at', 'status', 'total_amount'], 'idx_orders_analytics');
            $table->index(['vendor_id', 'delivered_at'], 'idx_orders_vendor_delivered');
            $table->index(['buyer_id', 'delivered_at'], 'idx_orders_buyer_delivered');
        });

        // RFQs table performance indexes
        Schema::table('rfqs', function (Blueprint $table) {
            $table->index(['buyer_id', 'status', 'created_at'], 'idx_rfqs_buyer_status_created');
            $table->index(['status', 'expires_at'], 'idx_rfqs_status_expires');
            $table->index(['category_id', 'status'], 'idx_rfqs_category_status');
            $table->index(['is_urgent', 'status'], 'idx_rfqs_urgent_status');
        });

        // Quotes table performance indexes
        Schema::table('quotes', function (Blueprint $table) {
            $table->index(['vendor_id', 'status', 'created_at'], 'idx_quotes_vendor_status_created');
            $table->index(['rfq_id', 'status'], 'idx_quotes_rfq_status');
            $table->index(['expires_at', 'status'], 'idx_quotes_expires_status');
            $table->index(['total_amount', 'status'], 'idx_quotes_amount_status');
        });

        // Order items table performance indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
            $table->index(['product_id', 'created_at'], 'idx_order_items_product_created');
        });

        // Conversations and messages for real-time features
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['updated_at', 'status'], 'idx_conversations_updated_status');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'idx_messages_conversation_created');
            $table->index(['sender_id', 'sender_type', 'created_at'], 'idx_messages_sender_created');
            $table->index(['is_read', 'created_at'], 'idx_messages_read_created');
        });

        // Cart optimization for session management
        Schema::table('carts', function (Blueprint $table) {
            $table->index(['buyer_id', 'updated_at'], 'idx_carts_buyer_updated');
            $table->index(['session_id', 'updated_at'], 'idx_carts_session_updated');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id', 'product_id'], 'idx_cart_items_cart_product');
        });

        // Price history for analytics
        Schema::table('price_history', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'idx_price_history_product_created');
        });

        // Notifications optimization
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notifications_notifiable_read');
            $table->index(['created_at', 'read_at'], 'idx_notifications_created_read');
        });

        // Vendor ratings and reviews
        Schema::table('vendor_ratings', function (Blueprint $table) {
            $table->index(['vendor_id', 'created_at'], 'idx_vendor_ratings_vendor_created');
        });

        // Activity logs for audit trails
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'user_type', 'created_at'], 'idx_activity_logs_user_created');
            $table->index(['auditable_id', 'auditable_type', 'created_at'], 'idx_activity_logs_auditable_created');
        });

        // Jobs table for queue performance
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['queue', 'available_at'], 'idx_jobs_queue_available');
            $table->index(['attempts', 'available_at'], 'idx_jobs_attempts_available');
        });

        // Sessions table for user management
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['last_activity', 'user_id'], 'idx_sessions_activity_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                DB::statement('ALTER TABLE products DROP INDEX name');
                DB::statement('ALTER TABLE products DROP INDEX sku');
            });
        }

        // Drop composite indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_stock_price');
            $table->dropIndex('idx_products_category_active_stock');
            $table->dropIndex('idx_products_vendor_active_stock');
            $table->dropIndex('idx_products_brand_active');
            $table->dropIndex('idx_products_origin_active');
            $table->dropIndex('idx_products_quality_active');
            $table->dropIndex('idx_products_featured_active_created');
            $table->dropIndex('idx_products_popularity');
            $table->dropIndex('idx_products_name_active');
            $table->dropIndex('idx_products_sku_active');
            $table->dropIndex('idx_products_price_active_stock');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_buyer_status_created');
            $table->dropIndex('idx_orders_vendor_status_created');
            $table->dropIndex('idx_orders_status_payment_delivery');
            $table->dropIndex('idx_orders_payment_due');
            $table->dropIndex('idx_orders_delivery_status');
            $table->dropIndex('idx_orders_amount_status');
            $table->dropIndex('idx_orders_analytics');
            $table->dropIndex('idx_orders_vendor_delivered');
            $table->dropIndex('idx_orders_buyer_delivered');
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropIndex('idx_rfqs_buyer_status_created');
            $table->dropIndex('idx_rfqs_status_expires');
            $table->dropIndex('idx_rfqs_category_status');
            $table->dropIndex('idx_rfqs_urgent_status');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('idx_quotes_vendor_status_created');
            $table->dropIndex('idx_quotes_rfq_status');
            $table->dropIndex('idx_quotes_expires_status');
            $table->dropIndex('idx_quotes_amount_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_product');
            $table->dropIndex('idx_order_items_product_created');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_updated_status');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation_created');
            $table->dropIndex('idx_messages_sender_created');
            $table->dropIndex('idx_messages_read_created');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_buyer_updated');
            $table->dropIndex('idx_carts_session_updated');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart_product');
        });

        Schema::table('price_history', function (Blueprint $table) {
            $table->dropIndex('idx_price_history_product_created');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_notifiable_read');
            $table->dropIndex('idx_notifications_created_read');
        });

        Schema::table('vendor_ratings', function (Blueprint $table) {
            $table->dropIndex('idx_vendor_ratings_vendor_created');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_user_created');
            $table->dropIndex('idx_activity_logs_auditable_created');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_queue_available');
            $table->dropIndex('idx_jobs_attempts_available');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_activity_user');
        });
    }
};