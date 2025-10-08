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
        Schema::create('saved_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['wishlist', 'recurring', 'template', 'saved']);
            $table->boolean('is_public')->default(false);
            $table->string('share_token')->unique()->nullable();
            $table->json('recurrence_settings')->nullable(); // For recurring orders
            $table->date('next_order_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'type']);
            $table->index('share_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_carts');
    }
};