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
        Schema::create('delivery_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('driver_code')->unique();
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('license_number')->unique();
            $table->date('license_expiry');
            
            // Vehicle details
            $table->string('vehicle_type'); // van, truck, ute, motorcycle
            $table->string('vehicle_make');
            $table->string('vehicle_model');
            $table->string('vehicle_year');
            $table->string('vehicle_registration')->unique();
            $table->decimal('vehicle_capacity_kg', 10, 2);
            $table->decimal('vehicle_volume_m3', 10, 2);
            $table->boolean('has_refrigeration')->default(false);
            $table->boolean('has_lift_gate')->default(false);
            
            // Status and availability
            $table->enum('status', ['active', 'inactive', 'on_break', 'offline'])->default('offline');
            $table->enum('availability_status', ['available', 'on_route', 'returning', 'unavailable'])->default('available');
            $table->json('working_days')->nullable(); // ['mon', 'tue', 'wed', ...]
            $table->time('shift_start')->nullable();
            $table->time('shift_end')->nullable();
            
            // Location tracking
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->string('current_zone')->nullable();
            
            // Performance metrics
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->decimal('on_time_percentage', 5, 2)->default(100);
            $table->decimal('fuel_efficiency_rating', 3, 2)->nullable();
            
            // Compensation
            $table->enum('payment_type', ['hourly', 'per_delivery', 'salary'])->default('per_delivery');
            $table->decimal('base_rate', 10, 2);
            $table->decimal('per_km_rate', 10, 2)->nullable();
            $table->decimal('overtime_rate', 10, 2)->nullable();
            
            // Certifications and documents
            $table->boolean('has_food_safety_cert')->default(false);
            $table->date('food_safety_cert_expiry')->nullable();
            $table->boolean('has_dangerous_goods_cert')->default(false);
            $table->date('dangerous_goods_cert_expiry')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            
            // App and device
            $table->string('device_token')->nullable();
            $table->string('device_type')->nullable(); // ios, android
            $table->string('app_version')->nullable();
            $table->timestamp('last_app_activity')->nullable();
            
            // Preferences
            $table->json('preferred_zones')->nullable();
            $table->json('blocked_zones')->nullable();
            $table->integer('max_stops_per_route')->default(20);
            $table->integer('max_distance_km')->default(100);
            $table->json('special_skills')->nullable(); // ['heavy_lifting', 'fragile_items', 'customer_service']
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'availability_status']);
            $table->index(['current_zone']);
            $table->index(['shift_start', 'shift_end']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_drivers');
    }
};