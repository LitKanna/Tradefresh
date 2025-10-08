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
        Schema::create('payment_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');
            $table->enum('type', ['billing_error', 'unauthorized_charge', 'duplicate_charge', 'quality_issue', 'service_issue', 'refund_request', 'other']);
            $table->enum('status', ['submitted', 'under_review', 'investigating', 'resolved', 'rejected', 'escalated', 'closed']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            
            // Dispute details
            $table->string('subject');
            $table->text('description');
            $table->decimal('disputed_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('incident_date')->nullable();
            
            // Resolution
            $table->enum('resolution_type', ['full_refund', 'partial_refund', 'credit_applied', 'adjustment', 'no_action', 'other'])->nullable();
            $table->decimal('resolution_amount', 15, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            
            // Tracking
            $table->timestamp('submitted_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->date('expected_resolution_date')->nullable();
            $table->integer('response_time_hours')->nullable();
            
            // Internal tracking
            $table->string('assigned_to')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('evidence')->nullable(); // File attachments, screenshots, etc.
            $table->string('reference_number')->nullable();
            
            // Customer communication
            $table->timestamp('last_contacted_at')->nullable();
            $table->integer('contact_attempts')->default(0);
            $table->string('preferred_contact_method')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['dispute_number']);
            $table->index(['status', 'priority']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_disputes');
    }
};