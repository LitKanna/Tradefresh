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
        Schema::create('pickup_time_slots', function (Blueprint $table) {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('slot_name'); // e.g., "Early Morning Premium", "Morning Standard"
            $table->enum('slot_type', ['premium', 'standard', 'off_peak']);
            $table->integer('duration_minutes')->default(30);
            $table->integer('max_bookings')->default(10); // Max bookings per slot across all bays
            $table->decimal('price_multiplier', 5, 2)->default(1.00); // 1.5 for premium, 0.8 for off-peak
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', ...] null = all days
            $table->json('blocked_dates')->nullable(); // Specific dates when slot is not available
            $table->boolean('allows_exact_time')->default(false); // Can user specify exact time within slot
            $table->integer('buffer_minutes')->default(5); // Buffer time between bookings
            $table->boolean('requires_approval')->default(false);
            $table->integer('advance_booking_hours')->default(1); // How many hours in advance can book
            $table->integer('max_advance_days')->default(30); // How many days in advance can book
            $table->boolean('is_active')->default(true);
            $table->integer('priority_order')->default(0);
            $table->timestamps();
            
            $table->index(['start_time', 'end_time']);
            $table->index('slot_type');
            $table->index('is_active');
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