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
        Schema::create('recurring_pickup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bay_id')->nullable()->constrained('pickup_bays')->onDelete('set null');
            $table->foreignId('time_slot_id')->nullable()->constrained('pickup_time_slots')->onDelete('set null');
            $table->string('schedule_name');
            $table->enum('frequency', ['daily', 'weekly', 'bi_weekly', 'monthly']);
            $table->json('days_of_week')->nullable(); // For weekly: ['monday', 'wednesday', 'friday']
            $table->integer('day_of_month')->nullable(); // For monthly: 1-31
            $table->time('preferred_time');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->text('special_requirements')->nullable();
            $table->boolean('auto_confirm')->default(false);
            $table->boolean('send_reminders')->default(true);
            $table->integer('reminder_hours')->default(1); // Hours before pickup
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->timestamp('last_booking_created')->nullable();
            $table->timestamp('next_booking_date')->nullable();
            $table->integer('total_bookings_created')->default(0);
            $table->json('skip_dates')->nullable(); // Dates to skip
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('next_booking_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_pickup_schedules');
    }
};