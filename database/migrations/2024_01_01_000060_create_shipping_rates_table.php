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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['flat', 'weight_based', 'price_based', 'zone_based', 'free']);
            $table->decimal('base_rate', 10, 2)->default(0);
            $table->decimal('per_kg_rate', 10, 2)->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable(); // For free shipping threshold
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->json('zones')->nullable(); // Delivery zones with rates
            $table->json('rules')->nullable(); // Complex shipping rules
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(3);
            $table->boolean('is_express')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['vendor_id', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};