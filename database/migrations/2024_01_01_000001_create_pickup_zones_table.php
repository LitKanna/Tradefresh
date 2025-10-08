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
        Schema::create('pickup_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A, B, C, D, E, F
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location_description')->nullable();
            $table->integer('total_bays')->default(0);
            $table->integer('truck_bays')->default(0);
            $table->integer('van_bays')->default(0);
            $table->integer('car_spots')->default(0);
            $table->boolean('has_forklift')->default(false);
            $table->boolean('has_trolley_area')->default(false);
            $table->boolean('is_covered')->default(false);
            $table->decimal('distance_from_entrance', 8, 2)->nullable(); // meters
            $table->json('operating_hours')->nullable();
            $table->json('equipment')->nullable(); // Available equipment in zone
            $table->boolean('is_active')->default(true);
            $table->integer('priority_order')->default(0);
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_zones');
    }
};