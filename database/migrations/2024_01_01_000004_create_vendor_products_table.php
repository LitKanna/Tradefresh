<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('unit')->nullable();
            $table->integer('min_order_quantity')->default(1);
            $table->integer('stock_quantity')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->string('lead_time')->nullable();
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->json('bulk_pricing')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
            $table->index('sku');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_products');
    }
};