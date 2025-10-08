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
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('vendor_id');
            $table->decimal('old_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->decimal('price_change', 12, 2); // Amount changed
            $table->decimal('percentage_change', 8, 2); // Percentage changed
            $table->enum('change_type', ['increase', 'decrease', 'initial'])->default('initial');
            $table->string('reason')->nullable(); // Reason for price change
            $table->date('effective_date');
            $table->unsignedBigInteger('changed_by')->nullable(); // User who changed the price
            $table->string('changed_by_type')->nullable(); // vendor, admin
            $table->jsonb('market_factors')->nullable(); // Market conditions affecting price
            $table->jsonb('competitor_prices')->nullable(); // Competitor price comparison
            $table->decimal('cost_at_time', 12, 2)->nullable(); // Product cost at this time
            $table->decimal('margin_percentage', 8, 2)->nullable(); // Profit margin
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');

            // Indexes
            $table->index('product_id');
            $table->index('vendor_id');
            $table->index('effective_date');
            $table->index('change_type');
            $table->index(['product_id', 'effective_date']); // Product price timeline
            $table->index(['vendor_id', 'effective_date']); // Vendor price changes
            $table->index(['change_type', 'percentage_change']); // Significant price changes
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};