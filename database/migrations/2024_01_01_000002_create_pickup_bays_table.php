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
        Schema::create('pickup_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('pickup_zones')->onDelete('cascade');
            $table->string('bay_number', 20)->unique();
            $table->enum('bay_type', ['truck_bay', 'van_bay', 'car_spot', 'loading_dock']);
            $table->integer('capacity')->default(1); // Number of vehicles it can accommodate
            $table->decimal('width', 8, 2)->nullable(); // meters
            $table->decimal('length', 8, 2)->nullable(); // meters
            $table->decimal('height_clearance', 8, 2)->nullable(); // meters for covered bays
            $table->boolean('has_dock_leveler')->default(false);
            $table->boolean('has_forklift_access')->default(false);
            $table->boolean('has_power_outlet')->default(false);
            $table->boolean('has_lighting')->default(true);
            $table->json('equipment')->nullable(); // Available equipment at this bay
            $table->json('restrictions')->nullable(); // Time restrictions, vehicle restrictions
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'closed'])->default('available');
            $table->boolean('is_premium')->default(false);
            $table->decimal('premium_rate', 8, 2)->nullable(); // Additional cost for premium bays
            $table->integer('priority_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['zone_id', 'bay_type']);
            $table->index('status');
            $table->index('is_active');
            $table->index('bay_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_bays');
    }
};