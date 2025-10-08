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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->string('unit', 50); // kg, box, dozen, etc.
            $table->decimal('unit_size', 10, 3)->nullable(); // Size of the unit
            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable(); // Original price for discounts
            $table->decimal('cost', 12, 2)->nullable(); // Cost to vendor
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_order_quantity')->default(1);
            $table->integer('max_order_quantity')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_organic')->default(false);
            $table->boolean('is_seasonal')->default(false);
            $table->string('origin_country', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->json('images')->nullable(); // Array of image URLs
            $table->json('specifications')->nullable(); // Product specifications
            $table->json('certifications')->nullable(); // Halal, Kosher, Organic certs
            $table->json('tags')->nullable(); // Product tags for search
            $table->json('metadata')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->date('harvest_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->decimal('weight', 10, 3)->nullable(); // Weight in kg
            $table->string('dimensions')->nullable(); // LxWxH
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');

            // Indexes
            $table->index('vendor_id');
            $table->index('category_id');
            $table->index('slug');
            $table->index('sku');
            $table->index('barcode');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('price');
            $table->index('stock_quantity');
            $table->index(['vendor_id', 'is_active']); // Vendor's active products
            $table->index(['category_id', 'is_active', 'price']); // Category browsing with price sort
            $table->index(['is_active', 'is_featured', 'created_at']); // Featured products
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};