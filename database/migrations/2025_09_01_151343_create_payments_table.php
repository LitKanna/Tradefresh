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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            
            // Payment Details
            $table->enum('type', ['payment', 'refund', 'partial_refund']);
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled']);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('AUD');
            
            // Stripe Information
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            
            // Payment Method Details
            $table->string('payment_method');
            $table->json('payment_method_details')->nullable();
            
            // Card Details (if applicable)
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('card_exp_month')->nullable();
            $table->string('card_exp_year')->nullable();
            
            // Bank Transfer Details (if applicable)
            $table->string('bank_name')->nullable();
            $table->string('bank_account_last4')->nullable();
            $table->string('bank_routing_number')->nullable();
            
            // Transaction Information
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('failure_code')->nullable();
            
            // Additional Data
            $table->json('metadata')->nullable();
            $table->json('stripe_response')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'status']);
            $table->index(['user_id']);
            $table->index(['stripe_payment_intent_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
