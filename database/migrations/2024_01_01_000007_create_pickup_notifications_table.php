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
        Schema::create('pickup_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('pickup_bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['booking_confirmation', 'reminder', 'bay_change', 'cancellation', 'check_in', 'completed']);
            $table->enum('channel', ['email', 'sms', 'push', 'in_app']);
            $table->string('recipient'); // email address or phone number
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('data')->nullable(); // Additional data for the notification
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['booking_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_notifications');
    }
};