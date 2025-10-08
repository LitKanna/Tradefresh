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
        Schema::create('buyer_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_ordered')->default(0);
            $table->decimal('last_price', 10, 2)->nullable();
            $table->timestamp('last_ordered_at')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();
            
            $table->unique(['buyer_id', 'product_id']);
            $table->index(['buyer_id', 'is_favorite']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_product');
    }
};
