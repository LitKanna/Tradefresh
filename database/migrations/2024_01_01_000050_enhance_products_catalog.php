<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add additional fields to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'featured')) {
                $table->boolean('featured')->default(false)->index();
            }
            if (!Schema::hasColumn('products', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0);
            }
            if (!Schema::hasColumn('products', 'min_order_quantity')) {
                $table->unsignedInteger('min_order_quantity')->default(1);
            }
            if (!Schema::hasColumn('products', 'max_order_quantity')) {
                $table->unsignedInteger('max_order_quantity')->nullable();
            }
            if (!Schema::hasColumn('products', 'unit_type')) {
                $table->string('unit_type')->default('piece'); // piece, kg, box, carton, pallet
            }
            if (!Schema::hasColumn('products', 'origin')) {
                $table->string('origin')->nullable();
            }
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable();
            }
            if (!Schema::hasColumn('products', 'certification')) {
                $table->text('certification')->nullable();
            }
            if (!Schema::hasColumn('products', 'meta_keywords')) {
                $table->text('meta_keywords')->nullable();
            }
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
        });

        // Product images table
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'is_primary']);
        });

        // Product variations table
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Large Box", "5kg Pack"
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('size')->nullable();
            $table->string('packaging')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index('sku');
        });

        // Bulk pricing tiers
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->unsignedInteger('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->index(['product_id', 'min_quantity']);
            $table->index(['variation_id', 'min_quantity']);
        });

        // Product attributes
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('attribute_name');
            $table->string('attribute_value');
            $table->timestamps();
            
            $table->index(['product_id', 'attribute_name']);
        });

        // Recently viewed products
        Schema::create('recently_viewed_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->unsignedInteger('view_count')->default(1);
            
            $table->unique(['buyer_id', 'product_id']);
            $table->index(['buyer_id', 'viewed_at']);
        });

        // Product reviews
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('review')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'is_approved']);
            $table->index(['buyer_id', 'product_id']);
        });

        // Product inventory tracking
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('available_quantity');
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->boolean('track_inventory')->default(true);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'variation_id', 'vendor_id']);
            $table->index(['vendor_id', 'available_quantity']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_inventory');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('recently_viewed_products');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_images');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'featured', 'views_count', 'min_order_quantity', 
                'max_order_quantity', 'unit_type', 'origin', 
                'brand', 'certification', 'meta_keywords', 'meta_description'
            ]);
        });
    }
};