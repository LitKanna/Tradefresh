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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 20)->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('delivery_zone_id')->nullable();
            $table->enum('status', [
                'pending',
                'assigned',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed',
                'returned',
                'cancelled'
            ])->default('pending');
            $table->enum('delivery_type', ['standard', 'express', 'same_day', 'scheduled'])->default('standard');
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('vehicle_number')->nullable();
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->date('scheduled_date');
            $table->string('scheduled_time_slot')->nullable(); // e.g., "09:00-12:00"
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_minutes')->nullable();
            $table->jsonb('route')->nullable(); // Delivery route/waypoints
            $table->jsonb('tracking_history')->nullable(); // Status change history
            $table->jsonb('location_updates')->nullable(); // GPS tracking data
            $table->string('signature_url')->nullable(); // Delivery signature
            $table->string('photo_url')->nullable(); // Proof of delivery photo
            $table->text('delivery_notes')->nullable();
            $table->text('recipient_name')->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('restrict');
            $table->foreign('delivery_zone_id')->references('id')->on('delivery_zones')->onDelete('set null');

            // Indexes
            $table->index('tracking_number');
            $table->index('order_id');
            $table->index('vendor_id');
            $table->index('buyer_id');
            $table->index('delivery_zone_id');
            $table->index('status');
            $table->index('delivery_type');
            $table->index('scheduled_date');
            $table->index(['status', 'scheduled_date']); // Upcoming deliveries
            $table->index(['vendor_id', 'status', 'scheduled_date']); // Vendor's deliveries
            $table->index(['buyer_id', 'status']); // Buyer's deliveries
            $table->index(['status', 'delivery_attempts']); // Failed deliveries for retry
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};