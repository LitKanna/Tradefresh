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
            $table->string('payment_number', 20)->unique();
            $table->morphs('payable'); // Polymorphic relation (order, invoice, etc.)
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('vendor_id');
            $table->enum('type', ['payment', 'refund', 'partial_refund', 'credit', 'adjustment'])->default('payment');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('method', [
                'credit_card',
                'debit_card',
                'bank_transfer',
                'credit_account',
                'cash',
                'cheque',
                'paypal',
                'stripe',
                'afterpay'
            ]);
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 10, 2)->default(0); // Transaction fees
            $table->decimal('net_amount', 12, 2); // Amount after fees
            $table->string('currency', 3)->default('AUD');
            $table->string('reference_number')->nullable(); // External reference
            $table->string('transaction_id')->nullable(); // Payment gateway transaction ID
            $table->string('gateway')->nullable(); // Payment gateway used
            $table->jsonb('gateway_response')->nullable(); // Full gateway response
            $table->jsonb('card_details')->nullable(); // Masked card details
            $table->jsonb('bank_details')->nullable(); // Bank transfer details
            $table->jsonb('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');

            // Indexes
            $table->index('payment_number');
            // Note: morphs() already creates index for payable_type and payable_id
            $table->index('buyer_id');
            $table->index('vendor_id');
            $table->index('type');
            $table->index('status');
            $table->index('method');
            $table->index('transaction_id');
            $table->index('created_at');
            $table->index(['buyer_id', 'status']); // Buyer's payments
            $table->index(['vendor_id', 'status']); // Vendor's received payments
            $table->index(['status', 'method', 'created_at']); // Payment reports
            $table->index(['status', 'next_retry_at']); // Failed payments to retry
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