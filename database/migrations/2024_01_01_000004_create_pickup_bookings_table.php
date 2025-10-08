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
        Schema::create('pickup_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('bay_id')->constrained('pickup_bays')->onDelete('restrict');
            $table->foreignId('time_slot_id')->nullable()->constrained('pickup_time_slots')->onDelete('set null');
            $table->date('pickup_date');
            $table->time('pickup_time'); // Exact time within the slot
            $table->time('end_time')->nullable(); // Calculated based on duration
            $table->integer('duration_minutes')->default(30);
            $table->enum('booking_type', ['one_time', 'recurring']);
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->string('vehicle_type')->nullable(); // truck, van, car, ute
            $table->string('vehicle_registration')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->text('special_requirements')->nullable();
            $table->json('items_to_pickup')->nullable(); // List of order items
            $table->string('qr_code')->nullable(); // Path to QR code image
            $table->string('confirmation_code', 10)->unique();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('booking_fee', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->json('notifications_sent')->nullable(); // Track which notifications have been sent
            $table->integer('rating')->nullable(); // 1-5 star rating
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->index(['pickup_date', 'pickup_time']);
            $table->index(['user_id', 'pickup_date']);
            $table->index(['bay_id', 'pickup_date']);
            $table->index('status');
            $table->index('booking_reference');
            $table->index('confirmation_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_bookings');
    }
};