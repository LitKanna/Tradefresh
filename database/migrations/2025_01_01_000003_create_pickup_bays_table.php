<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for pickup bay management.
     * Manages the physical pickup locations at Sydney Markets.
     */
    public function up(): void
    {
        Schema::create('pickup_bays', function (Blueprint $table) {
            $table->id();
            $table->string('bay_number')->unique(); // e.g., "A1", "B12", "DOCK-3"
            $table->string('bay_name')->nullable(); // e.g., "North Loading Dock"
            
            // Location details
            $table->enum('zone', ['north', 'south', 'east', 'west', 'central']);
            $table->enum('area_type', ['loading_dock', 'parking_bay', 'drive_through', 'warehouse_entrance']);
            $table->string('building')->nullable(); // Building or warehouse identifier
            $table->integer('floor')->default(0); // Floor number (0 = ground)
            
            // Physical specifications
            $table->decimal('max_vehicle_height', 5, 2)->nullable(); // in meters
            $table->decimal('max_vehicle_length', 5, 2)->nullable(); // in meters
            $table->decimal('max_vehicle_weight', 8, 2)->nullable(); // in tonnes
            $table->enum('vehicle_type_restriction', ['any', 'small_van', 'large_truck', 'forklift_access'])->default('any');
            
            // Access control
            $table->boolean('requires_booking')->default(false);
            $table->boolean('requires_access_card')->default(false);
            $table->string('access_code')->nullable();
            $table->json('restricted_to_businesses')->nullable(); // Array of business_ids with exclusive access
            
            // Operational hours
            $table->json('operating_hours')->nullable(); // {"monday": {"start": "04:00", "end": "14:00"}, ...}
            $table->boolean('is_24_7')->default(false);
            
            // Capacity and usage
            $table->integer('simultaneous_vehicles')->default(1); // How many vehicles can use at once
            $table->integer('daily_capacity')->nullable(); // Max pickups per day
            
            // Features
            $table->boolean('has_forklift')->default(false);
            $table->boolean('has_loading_equipment')->default(false);
            $table->boolean('has_refrigeration')->default(false);
            $table->boolean('has_weighbridge')->default(false);
            $table->boolean('is_covered')->default(true);
            $table->boolean('is_secure')->default(true);
            
            // Status
            $table->enum('status', ['active', 'maintenance', 'closed', 'reserved'])->default('active');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('status_reason')->nullable();
            
            // GPS coordinates for mapping
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Instructions and notes
            $table->text('access_instructions')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('bay_number');
            $table->index('zone');
            $table->index('area_type');
            $table->index('status');
            $table->index(['zone', 'status']);
            $table->index(['area_type', 'status']);
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