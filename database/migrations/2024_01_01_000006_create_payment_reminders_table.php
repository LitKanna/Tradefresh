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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['due_soon', 'overdue', 'final_notice', 'custom']);
            $table->enum('status', ['scheduled', 'sent', 'cancelled', 'failed']);
            
            // Reminder details
            $table->string('subject');
            $table->text('message');
            $table->integer('days_before_due')->nullable(); // For due_soon reminders
            $table->integer('days_after_due')->nullable(); // For overdue reminders
            
            // Scheduling
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            
            // Delivery
            $table->string('sent_to_email')->nullable();
            $table->string('sent_to_phone')->nullable();
            $table->enum('delivery_method', ['email', 'sms', 'both']);
            $table->json('delivery_response')->nullable();
            
            // Tracking
            $table->string('tracking_id')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('failure_reason')->nullable();
            
            $table->timestamps();
            
            $table->index(['invoice_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};