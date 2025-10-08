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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 20)->unique();
            $table->unsignedBigInteger('buyer_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'awarded', 'cancelled', 'expired'])->default('draft');
            $table->enum('urgency', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('delivery_date');
            $table->string('delivery_time')->nullable(); // Preferred delivery time
            $table->text('delivery_address');
            $table->text('delivery_instructions')->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->json('items')->nullable(); // Array of requested items with quantities
            $table->json('preferred_vendors')->nullable(); // Specific vendor IDs to notify
            $table->json('requirements')->nullable(); // Special requirements
            $table->json('attachments')->nullable(); // File attachments
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(true); // Visible to all vendors or selected only
            $table->integer('max_quotes')->nullable(); // Maximum number of quotes to accept
            $table->integer('quote_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->unsignedBigInteger('awarded_vendor_id')->nullable();
            $table->unsignedBigInteger('awarded_quote_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('awarded_vendor_id')->references('id')->on('vendors')->onDelete('set null');

            // Indexes
            $table->index('rfq_number');
            $table->index('buyer_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('urgency');
            $table->index('delivery_date');
            $table->index('is_public');
            $table->index('closes_at');
            $table->index(['status', 'is_public', 'closes_at']); // Active public RFQs
            $table->index(['buyer_id', 'status']); // Buyer's RFQs by status
            $table->index(['status', 'urgency', 'delivery_date']); // Urgent RFQs
            // Additional indexes for JSON search (removed GIN indexes for SQLite compatibility)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};