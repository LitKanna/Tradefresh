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
        Schema::create('account_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'finalized', 'sent', 'archived']);
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly', 'custom']);
            
            // Period
            $table->date('period_start');
            $table->date('period_end');
            $table->date('statement_date');
            $table->date('due_date')->nullable();
            
            // Balances
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('total_charges', 15, 2)->default(0);
            $table->decimal('total_payments', 15, 2)->default(0);
            $table->decimal('total_credits', 15, 2)->default(0);
            $table->decimal('total_adjustments', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            
            // Aging
            $table->decimal('current_amount', 15, 2)->default(0); // 0-30 days
            $table->decimal('days_30_amount', 15, 2)->default(0); // 31-60 days
            $table->decimal('days_60_amount', 15, 2)->default(0); // 61-90 days
            $table->decimal('days_90_plus_amount', 15, 2)->default(0); // 90+ days
            
            // Totals
            $table->integer('total_invoices')->default(0);
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('total_interest', 10, 2)->default(0);
            
            // Credit limit info
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('available_credit', 15, 2)->nullable();
            $table->decimal('credit_used', 15, 2)->nullable();
            
            // Delivery
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_to_email')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_hash')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'statement_date']);
            $table->index(['statement_number']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_statements');
    }
};