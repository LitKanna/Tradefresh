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
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_code')->unique();
            $table->foreignId('driver_id')->constrained('delivery_drivers')->onDelete('cascade');
            $table->date('route_date');
            
            // Route details
            $table->enum('route_type', ['standard', 'express', 'scheduled', 'on_demand'])->default('standard');
            $table->enum('status', [
                'draft', 
                'planned', 
                'assigned', 
                'in_progress', 
                'completed', 
                'cancelled'
            ])->default('draft');
            
            // Timing
            $table->time('planned_start_time');
            $table->time('planned_end_time');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->integer('estimated_duration_minutes');
            $table->integer('actual_duration_minutes')->nullable();
            
            // Route metrics
            $table->integer('total_stops');
            $table->integer('completed_stops')->default(0);
            $table->integer('failed_stops')->default(0);
            $table->integer('pending_stops')->default(0);
            $table->decimal('total_distance_km', 10, 2);
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->decimal('total_weight_kg', 10, 2);
            $table->decimal('total_volume_m3', 10, 2);
            
            // Route optimization
            $table->json('optimized_sequence'); // Array of stop IDs in optimal order
            $table->json('original_sequence')->nullable(); // Original order before optimization
            $table->decimal('optimization_score', 5, 2)->nullable(); // Efficiency score 0-100
            $table->string('optimization_method')->nullable(); // TSP algorithm used
            $table->integer('optimization_time_ms')->nullable(); // Time taken to optimize
            
            // Geographic data
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->json('route_polyline')->nullable(); // Encoded polyline for map display
            $table->json('zones_covered'); // Array of zone codes
            
            // Cost calculations
            $table->decimal('estimated_fuel_cost', 10, 2)->nullable();
            $table->decimal('actual_fuel_cost', 10, 2)->nullable();
            $table->decimal('driver_cost', 10, 2)->nullable();
            $table->decimal('total_revenue', 10, 2)->nullable();
            $table->decimal('profit_margin', 10, 2)->nullable();
            
            // Performance
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->decimal('average_stop_time_minutes', 5, 2)->nullable();
            $table->decimal('idle_time_minutes', 10, 2)->nullable();
            
            // Weather and conditions
            $table->string('weather_condition')->nullable();
            $table->string('traffic_condition')->nullable();
            $table->json('road_closures')->nullable();
            
            // Notes and issues
            $table->text('route_notes')->nullable();
            $table->json('reported_issues')->nullable();
            $table->boolean('requires_review')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['driver_id', 'route_date']);
            $table->index(['status']);
            $table->index(['route_type']);
            $table->index(['route_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};