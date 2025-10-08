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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['draft', 'pending', 'sent', 'paid', 'partial', 'overdue', 'cancelled', 'refunded']);
            $table->enum('type', ['standard', 'recurring', 'proforma', 'credit_note']);
            
            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2);
            $table->string('currency', 3)->default('USD');
            
            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            
            // Payment terms
            $table->string('payment_terms')->nullable(); // Net 30, Due on Receipt, etc.
            $table->integer('terms_days')->nullable();
            $table->decimal('late_fee_amount', 10, 2)->nullable();
            $table->decimal('late_fee_percentage', 5, 2)->nullable();
            
            // Billing details
            $table->string('bill_to_name');
            $table->string('bill_to_company')->nullable();
            $table->text('bill_to_address');
            $table->string('bill_to_email');
            $table->string('bill_to_phone')->nullable();
            
            // Shipping details
            $table->string('ship_to_name')->nullable();
            $table->string('ship_to_company')->nullable();
            $table->text('ship_to_address')->nullable();
            $table->string('ship_to_phone')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('po_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('metadata')->nullable();
            
            // Recurring invoice fields
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // monthly, quarterly, yearly
            $table->date('recurring_start_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->integer('recurring_count')->nullable();
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['invoice_number']);
            $table->index(['due_date']);
            $table->index(['invoice_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};