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
        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quote_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->decimal('quantity', 10, 2);
                $table->string('unit')->default('piece');
                $table->decimal('requested_unit_price', 10, 2)->nullable();
                $table->decimal('quoted_unit_price', 10, 2)->nullable();
                $table->decimal('requested_total_price', 10, 2)->nullable();
                $table->decimal('quoted_total_price', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->text('vendor_notes')->nullable();
                $table->string('status')->default('pending'); // pending, quoted, accepted, rejected
                $table->timestamps();
                
                $table->index(['quote_id', 'product_id']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};