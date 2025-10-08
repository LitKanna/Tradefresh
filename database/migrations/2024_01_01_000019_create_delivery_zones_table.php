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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->jsonb('postcodes'); // Array of postcodes in this zone
            $table->jsonb('suburbs'); // Array of suburbs in this zone
            $table->jsonb('coordinates')->nullable(); // Polygon coordinates for zone boundaries
            $table->decimal('base_delivery_fee', 10, 2)->default(0);
            $table->decimal('per_km_rate', 8, 2)->default(0);
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('delivery_time_hours')->default(24); // Expected delivery time
            $table->jsonb('delivery_windows')->nullable(); // Available delivery time slots
            $table->jsonb('vendor_rates')->nullable(); // Custom rates per vendor
            $table->jsonb('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['is_active', 'sort_order']);
            // GIN indexes for JSONB
            $table->index('postcodes', null, 'gin');
            $table->index('suburbs', null, 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};