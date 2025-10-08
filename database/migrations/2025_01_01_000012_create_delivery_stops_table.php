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
        Schema::create('delivery_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('delivery_routes')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pickup_booking_id')->nullable()->constrained('pickup_bookings')->onDelete('set null');
            $table->string('stop_reference')->unique();
            
            // Stop details
            $table->integer('stop_sequence'); // Order in the route
            $table->enum('stop_type', ['pickup', 'delivery', 'return'])->default('delivery');
            $table->enum('priority', ['urgent', 'high', 'normal', 'low'])->default('normal');
            $table->enum('status', [
                'pending',
                'en_route',
                'arrived',
                'in_progress',
                'completed',
                'failed',
                'rescheduled',
                'cancelled'
            ])->default('pending');
            
            // Customer information
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_email')->nullable();
            $table->string('company_name')->nullable();
            
            // Address details
            $table->text('delivery_address');
            $table->string('suburb');
            $table->string('postcode');
            $table->string('state');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address_notes')->nullable();
            $table->string('gate_code')->nullable();
            
            // Time windows
            $table->time('time_window_start')->nullable();
            $table->time('time_window_end')->nullable();
            $table->timestamp('estimated_arrival_time')->nullable();
            $table->timestamp('actual_arrival_time')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->integer('service_time_minutes')->default(10);
            $table->integer('actual_service_time_minutes')->nullable();
            
            // Package details
            $table->integer('package_count')->default(1);
            $table->decimal('total_weight_kg', 10, 2)->nullable();
            $table->decimal('total_volume_m3', 10, 2)->nullable();
            $table->json('package_dimensions')->nullable(); // [{length, width, height}]
            $table->boolean('requires_signature')->default(false);
            $table->boolean('requires_photo')->default(false);
            $table->boolean('requires_id_check')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->boolean('is_perishable')->default(false);
            $table->boolean('requires_refrigeration')->default(false);
            
            // Delivery completion
            $table->timestamp('completed_at')->nullable();
            $table->string('signature_url')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('recipient_id_number')->nullable();
            $table->string('delivered_to')->nullable(); // Person who received
            $table->string('delivery_location')->nullable(); // Front door, reception, etc.
            
            // Failed delivery
            $table->string('failure_reason')->nullable();
            $table->text('failure_notes')->nullable();
            $table->boolean('can_reschedule')->default(true);
            $table->integer('attempt_number')->default(1);
            $table->timestamp('next_attempt_date')->nullable();
            
            // Notifications
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->string('tracking_url')->nullable();
            
            // Distance and cost
            $table->decimal('distance_from_previous_km', 10, 2)->nullable();
            $table->integer('travel_time_minutes')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->decimal('cod_amount', 10, 2)->nullable(); // Cash on delivery
            $table->boolean('cod_collected')->default(false);
            
            // Customer feedback
            $table->integer('rating')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->boolean('complaint_raised')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['route_id', 'stop_sequence']);
            $table->index(['status']);
            $table->index(['order_id']);
            $table->index(['pickup_booking_id']);
            $table->index(['latitude', 'longitude']);
            $table->index(['suburb', 'postcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_stops');
    }
};