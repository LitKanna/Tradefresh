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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('buyer_id')->constrained('buyers');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->timestamp('issue_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('gst_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('reason');
            $table->enum('status', ['draft', 'issued', 'applied', 'void'])->default('issued');
            $table->string('original_invoice_number')->nullable();
            $table->json('buyer_details')->nullable();
            $table->json('vendor_details')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_via')->nullable();
            $table->foreignId('applied_to_invoice_id')->nullable()->constrained('invoices');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};