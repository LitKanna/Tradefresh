<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for pickup bookings.
     * Tracks actual pickup bookings and their status.
     */
    public function up(): void
    {
        Schema::create('pickup_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique(); // e.g., "PU-2025-001234"
            
            // Relationships
            $table->foreignId('business_id')->constrained('businesses')->onDelete('restrict');
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('restrict');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('pickup_detail_id')->constrained('pickup_details')->onDelete('restrict');
            $table->foreignId('bay_id')->nullable()->constrained('pickup_bays')->onDelete('set null');
            $table->foreignId('time_slot_id')->nullable()->constrained('pickup_time_slots')->onDelete('set null');
            
            // Booking details
            $table->date('pickup_date');
            $table->time('scheduled_time');
            $table->time('scheduled_end_time')->nullable();
            $table->integer('estimated_duration_minutes')->default(30);
            
            // Actual pickup tracking
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('loading_started_at')->nullable();
            $table->timestamp('loading_completed_at')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            
            // Vehicle and driver
            $table->string('vehicle_rego');
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->string('driver_license')->nullable();
            
            // Items to pickup
            $table->json('pickup_items')->nullable(); // Array of {product_id, quantity, unit, notes}
            $table->integer('total_items')->default(0);
            $table->decimal('total_weight', 10, 2)->nullable(); // in kg
            $table->decimal('total_volume', 10, 2)->nullable(); // in cubic meters
            $table->integer('pallet_count')->default(0);
            $table->integer('box_count')->default(0);
            
            // Special requirements
            $table->boolean('requires_refrigeration')->default(false);
            $table->boolean('requires_forklift')->default(false);
            $table->boolean('requires_loading_assistance')->default(false);
            $table->text('special_instructions')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_transit',
                'arrived',
                'loading',
                'completed',
                'no_show',
                'cancelled',
                'rescheduled'
            ])->default('pending');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('status_notes')->nullable();
            
            // Confirmation
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->string('confirmation_code')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('cancellation_fee', 10, 2)->default(0);
            
            // Rescheduling
            $table->boolean('is_rescheduled')->default(false);
            $table->foreignId('rescheduled_from')->nullable()->constrained('pickup_bookings')->nullOnDelete();
            $table->foreignId('rescheduled_to')->nullable()->constrained('pickup_bookings')->nullOnDelete();
            
            // Check-in process
            $table->string('check_in_code')->nullable();
            $table->timestamp('check_in_time')->nullable();
            $table->string('check_in_method')->nullable(); // 'qr_code', 'manual', 'automatic'
            $table->point('check_in_location')->nullable(); // GPS coordinates
            
            // Notifications
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->boolean('arrival_notification_sent')->default(false);
            $table->boolean('completion_notification_sent')->default(false);
            
            // Rating and feedback
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('feedback')->nullable();
            $table->timestamp('feedback_submitted_at')->nullable();
            
            // Charges
            $table->decimal('booking_fee', 10, 2)->default(0);
            $table->decimal('express_fee', 10, 2)->default(0);
            $table->decimal('waiting_time_charge', 10, 2)->default(0);
            $table->decimal('total_charges', 10, 2)->default(0);
            
            // Integration
            $table->string('external_reference')->nullable(); // Reference from external system
            $table->json('metadata')->nullable(); // Additional data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('booking_reference');
            $table->index(['business_id', 'pickup_date']);
            $table->index(['buyer_id', 'status']);
            $table->index(['pickup_date', 'scheduled_time']);
            $table->index(['bay_id', 'pickup_date']);
            $table->index('status');
            $table->index('order_id');
            $table->index(['pickup_date', 'status']);
            $table->index('check_in_code');
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