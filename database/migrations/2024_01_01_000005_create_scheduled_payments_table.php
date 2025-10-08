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
        Schema::create('scheduled_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('frequency', ['once', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly']);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled', 'failed']);
            
            // Schedule details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_payment_date');
            $table->time('payment_time')->default('09:00:00');
            $table->integer('payment_day')->nullable(); // Day of month/week
            
            // Payment tracking
            $table->integer('total_payments')->nullable(); // null for unlimited
            $table->integer('completed_payments')->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            
            // Configuration
            $table->boolean('auto_retry')->default(true);
            $table->integer('retry_attempts')->default(3);
            $table->integer('retry_interval_hours')->default(24);
            $table->boolean('notify_on_payment')->default(true);
            $table->boolean('notify_on_failure')->default(true);
            
            // Description
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['next_payment_date', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_payments');
    }
};