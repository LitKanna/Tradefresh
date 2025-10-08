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
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('account_number', 20)->unique()->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('active');
            $table->decimal('credit_limit', 12, 2);
            $table->decimal('used_credit', 12, 2)->default(0);
            $table->decimal('available_credit', 12, 2);
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->integer('payment_terms')->default(7); // Payment terms in days
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('late_fee_percentage', 5, 2)->default(2); // Late payment fee
            $table->decimal('interest_rate', 5, 2)->default(0); // Late payment interest
            $table->date('approval_date')->nullable();
            $table->date('last_review_date')->nullable();
            $table->date('next_review_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin who approved
            $table->text('approval_notes')->nullable();
            $table->jsonb('credit_history')->nullable(); // Track credit limit changes
            $table->jsonb('payment_history')->nullable(); // Payment behavior tracking
            $table->jsonb('metadata')->nullable();
            $table->integer('overdue_invoices_count')->default(0);
            $table->decimal('total_purchases', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->decimal('last_payment_amount', 12, 2)->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index('account_number');
            $table->index('buyer_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->unique('buyer_id'); // One credit account per buyer (platform-wide credit)
            $table->index(['status', 'next_review_date']); // Accounts due for review
            $table->index(['status', 'outstanding_balance']); // Active accounts with balance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_accounts');
    }
};