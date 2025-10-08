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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('buyer_id');
            $table->enum('status', ['draft', 'submitted', 'under_review', 'accepted', 'rejected', 'expired', 'withdrawn'])->default('draft');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 12, 2);
            $table->json('line_items'); // Detailed quote items with prices
            $table->json('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->text('vendor_message')->nullable();
            $table->date('validity_date');
            $table->date('proposed_delivery_date');
            $table->string('proposed_delivery_time')->nullable();
            $table->integer('payment_terms_days')->default(0);
            $table->string('payment_method')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_negotiable')->default(false);
            $table->integer('revision_number')->default(1);
            $table->unsignedBigInteger('parent_quote_id')->nullable(); // For revised quotes
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('rfq_id')->references('id')->on('rfqs')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
            $table->foreign('parent_quote_id')->references('id')->on('quotes')->onDelete('set null');

            // Indexes
            $table->index('quote_number');
            $table->index('rfq_id');
            $table->index('vendor_id');
            $table->index('buyer_id');
            $table->index('status');
            $table->index('validity_date');
            $table->index(['rfq_id', 'vendor_id']); // Unique quote per vendor per RFQ
            $table->index(['vendor_id', 'status']); // Vendor's quotes by status
            $table->index(['buyer_id', 'status']); // Buyer's received quotes
            $table->index(['status', 'validity_date']); // Active quotes check
            $table->index('final_amount'); // Price comparison
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};