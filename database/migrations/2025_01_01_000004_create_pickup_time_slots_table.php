<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for pickup time slot management.
     * Manages available time slots for pickups at Sydney Markets.
     */
    public function up(): void
    {
        Schema::create('pickup_time_slots', function (Blueprint $table) {
            $table->id();
            
            // Time slot definition
            $table->string('slot_name'); // e.g., "Early Morning", "Peak Hours"
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(30); // Default slot duration
            
            // Days of operation
            $table->boolean('monday')->default(true);
            $table->boolean('tuesday')->default(true);
            $table->boolean('wednesday')->default(true);
            $table->boolean('thursday')->default(true);
            $table->boolean('friday')->default(true);
            $table->boolean('saturday')->default(true);
            $table->boolean('sunday')->default(false);
            
            // Special dates
            $table->boolean('available_public_holidays')->default(false);
            $table->json('blocked_dates')->nullable(); // Array of dates when slot is not available
            
            // Capacity management
            $table->integer('max_bookings')->nullable(); // Max bookings per slot
            $table->integer('max_per_business')->default(1); // Max bookings per business per slot
            
            // Pricing (if applicable)
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('peak_price', 10, 2)->nullable();
            $table->boolean('is_peak')->default(false);
            
            // Priority and restrictions
            $table->enum('priority_level', ['standard', 'express', 'vip'])->default('standard');
            $table->json('restricted_to_buyer_types')->nullable(); // ['premium', 'wholesale']
            $table->integer('min_order_value')->nullable(); // Minimum order value to book this slot
            
            // Bay associations
            $table->json('available_bays')->nullable(); // Array of bay_ids available during this slot
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            
            // Booking rules
            $table->integer('advance_booking_hours')->default(2); // How many hours in advance can book
            $table->integer('cancellation_hours')->default(1); // How many hours before can cancel
            
            // Display
            $table->integer('display_order')->default(0);
            $table->string('color_code')->nullable(); // For UI display
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['start_time', 'end_time']);
            $table->index('is_active');
            $table->index('priority_level');
            $table->index(['is_active', 'display_order']);
            $table->index('is_peak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_time_slots');
    }
};