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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['payment', 'refund', 'partial_refund', 'chargeback', 'adjustment', 'credit']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'reversed']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            
            // Payment details
            $table->string('payment_method_type')->nullable(); // credit_card, ach, paypal, terms, etc.
            $table->string('payment_reference')->nullable(); // External transaction ID
            $table->string('processor')->nullable(); // stripe, paypal, square, etc.
            $table->json('processor_response')->nullable();
            
            // Transaction details
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->nullable();
            
            // Dates
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Error handling
            $table->string('failure_reason')->nullable();
            $table->string('failure_code')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->string('reconciled_by')->nullable();
            $table->text('reconciliation_notes')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['transaction_id']);
            $table->index(['status']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};