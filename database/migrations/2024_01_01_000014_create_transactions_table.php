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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 20)->unique();
            $table->morphs('transactable'); // Polymorphic (order, payment, refund, etc.)
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->enum('type', [
                'order_placed',
                'payment_received',
                'payment_sent',
                'refund_issued',
                'refund_received',
                'commission_charged',
                'commission_paid',
                'credit_used',
                'credit_added',
                'adjustment',
                'fee',
                'withdrawal',
                'deposit'
            ]);
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2)->nullable();
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->string('currency', 3)->default('AUD');
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->jsonb('metadata')->nullable();
            $table->string('created_by_type')->nullable(); // buyer, vendor, admin, system
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index('transaction_number');
            // Note: morphs() already creates index for transactable_type and transactable_id
            $table->index('buyer_id');
            $table->index('vendor_id');
            $table->index('admin_id');
            $table->index('type');
            $table->index('entry_type');
            $table->index('created_at');
            $table->index(['buyer_id', 'type', 'created_at']); // Buyer transaction history
            $table->index(['vendor_id', 'type', 'created_at']); // Vendor transaction history
            $table->index(['type', 'entry_type', 'created_at']); // Financial reports
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};