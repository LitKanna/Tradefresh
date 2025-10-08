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
        Schema::create('bulk_discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('cascade');
            $table->string('name');
            $table->decimal('min_quantity', 10, 2);
            $table->decimal('max_quantity', 10, 2)->nullable();
            $table->enum('discount_type', ['percentage', 'fixed', 'price']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('unit_price', 10, 2)->nullable(); // For price type discount
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('customer_type')->nullable(); // wholesale, retail, vip
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index(['vendor_id', 'is_active']);
            $table->index(['min_quantity', 'max_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_discount_tiers');
    }
};