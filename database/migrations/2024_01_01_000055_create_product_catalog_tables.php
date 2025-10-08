<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Product comparisons table
        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->json('product_ids');
            $table->json('comparison_data')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['buyer_id', 'expires_at']);
        });

        // Wishlist table
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('priority')->default(3); // 1-5
            $table->boolean('notify_on_sale')->default(true);
            $table->boolean('notify_on_stock')->default(true);
            $table->timestamps();
            
            $table->unique(['buyer_id', 'product_id', 'variation_id']);
            $table->index(['buyer_id', 'priority']);
        });

        // Product recommendations table
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('recommendation_type'); // based_on_history, frequently_bought, similar, etc.
            $table->decimal('score', 3, 2); // 0.00 to 1.00
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('clicked')->default(false);
            $table->boolean('purchased')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['buyer_id', 'expires_at', 'score']);
            $table->index(['buyer_id', 'recommendation_type']);
            $table->index(['product_id', 'clicked']);
        });

        // Product search history
        Schema::create('product_search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('search_query');
            $table->json('filters')->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('selected_product_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'created_at']);
            $table->index('search_query');
        });

        // Product quick order templates
        Schema::create('quick_order_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('products'); // Array of product_id, quantity, variation_id
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'is_active']);
        });

        // Bulk order uploads
        Schema::create('bulk_order_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->json('error_log')->nullable();
            $table->json('result_summary')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
        });

        // Product availability alerts
        Schema::create('product_availability_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->enum('alert_type', ['back_in_stock', 'price_drop', 'low_stock']);
            $table->decimal('target_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_triggered')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'is_active']);
            $table->index(['product_id', 'alert_type', 'is_active']);
        });

        // Product view analytics
        Schema::create('product_view_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id');
            $table->string('referrer')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('time_spent')->default(0); // seconds
            $table->boolean('added_to_cart')->default(false);
            $table->boolean('added_to_wishlist')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
            $table->index(['buyer_id', 'created_at']);
        });

        // Add indexes to existing tables
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'products_price_index')) {
                $table->index('price');
            }
            if (!Schema::hasIndex('products', 'products_created_at_index')) {
                $table->index('created_at');
            }
            if (!Schema::hasIndex('products', 'products_brand_index')) {
                $table->index('brand');
            }
            if (!Schema::hasIndex('products', 'products_origin_index')) {
                $table->index('origin');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_view_analytics');
        Schema::dropIfExists('product_availability_alerts');
        Schema::dropIfExists('bulk_order_uploads');
        Schema::dropIfExists('quick_order_templates');
        Schema::dropIfExists('product_search_history');
        Schema::dropIfExists('product_recommendations');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('product_comparisons');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['price']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['brand']);
            $table->dropIndex(['origin']);
        });
    }
};