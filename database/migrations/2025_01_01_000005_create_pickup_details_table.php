<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for pickup preferences and vehicle details.
     * Stores business-specific pickup preferences and vehicle registrations.
     */
    public function up(): void
    {
        Schema::create('pickup_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            
            // Vehicle Registration Details
            $table->string('vehicle_rego')->index(); // e.g., "ABC-123"
            $table->string('vehicle_state')->default('NSW'); // Registration state
            $table->enum('vehicle_type', [
                'car',
                'ute',
                'van',
                'small_truck',
                'medium_truck',
                'large_truck',
                'refrigerated_truck'
            ]);
            $table->string('vehicle_make')->nullable(); // e.g., "Toyota"
            $table->string('vehicle_model')->nullable(); // e.g., "HiAce"
            $table->string('vehicle_color')->nullable();
            $table->year('vehicle_year')->nullable();
            
            // Vehicle specifications
            $table->decimal('vehicle_height', 5, 2)->nullable(); // in meters
            $table->decimal('vehicle_length', 5, 2)->nullable(); // in meters
            $table->decimal('vehicle_weight', 8, 2)->nullable(); // in tonnes
            $table->decimal('load_capacity', 8, 2)->nullable(); // in tonnes
            $table->boolean('has_refrigeration')->default(false);
            $table->boolean('has_tailgate_lifter')->default(false);
            $table->boolean('has_forklift_access')->default(false);
            
            // Driver Information
            $table->string('primary_driver_name')->nullable();
            $table->string('primary_driver_phone')->nullable();
            $table->string('primary_driver_license')->nullable();
            $table->json('alternate_drivers')->nullable(); // Array of {name, phone, license}
            
            // Preferred Pickup Settings
            $table->foreignId('preferred_bay_id')->nullable()->constrained('pickup_bays')->nullOnDelete();
            $table->json('alternate_bay_ids')->nullable(); // Array of alternative bay IDs
            $table->foreignId('preferred_time_slot_id')->nullable()->constrained('pickup_time_slots')->nullOnDelete();
            $table->json('preferred_pickup_days')->nullable(); // ["monday", "wednesday", "friday"]
            
            // Pickup Method Preferences
            $table->enum('pickup_method', [
                'scheduled_bay', // Scheduled time at specific bay
                'at_stand', // Pickup directly at vendor stand
                'drive_through', // Quick drive-through pickup
                'warehouse', // Pickup from warehouse
                'flexible' // Any available method
            ])->default('scheduled_bay');
            
            $table->time('preferred_pickup_time')->nullable();
            $table->time('earliest_pickup_time')->nullable();
            $table->time('latest_pickup_time')->nullable();
            
            // Special Requirements
            $table->boolean('requires_forklift_assistance')->default(false);
            $table->boolean('requires_loading_assistance')->default(false);
            $table->boolean('requires_refrigeration')->default(false);
            $table->boolean('requires_pallets')->default(false);
            $table->text('special_instructions')->nullable();
            
            // Access and Security
            $table->string('access_card_number')->nullable();
            $table->date('access_card_expiry')->nullable();
            $table->string('security_pin')->nullable();
            $table->boolean('has_site_induction')->default(false);
            $table->date('induction_date')->nullable();
            $table->date('induction_expiry')->nullable();
            
            // Status and Verification
            $table->boolean('is_primary_vehicle')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('verification_notes')->nullable();
            
            // Insurance Details
            $table->string('insurance_company')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->boolean('insurance_covers_goods_in_transit')->default(false);
            
            // Usage Statistics
            $table->integer('total_pickups')->default(0);
            $table->timestamp('last_pickup_at')->nullable();
            $table->decimal('average_pickup_duration', 5, 2)->nullable(); // in minutes
            
            // Compliance
            $table->boolean('meets_safety_requirements')->default(false);
            $table->date('safety_check_date')->nullable();
            $table->date('next_safety_check_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['business_id', 'is_primary_vehicle']);
            $table->index(['business_id', 'is_active']);
            $table->index('vehicle_type');
            $table->index('pickup_method');
            $table->index('preferred_bay_id');
            $table->index('preferred_time_slot_id');
            $table->index(['is_active', 'is_verified']);
            $table->unique(['vehicle_rego', 'vehicle_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_details');
    }
};