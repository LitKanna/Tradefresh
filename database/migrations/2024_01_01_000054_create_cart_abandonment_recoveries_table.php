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
        Schema::create('cart_abandonment_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->string('recovery_token')->unique();
            $table->string('status')->default('pending'); // pending, sent, clicked, recovered, expired
            $table->integer('email_attempts')->default(0);
            $table->timestamp('first_email_sent_at')->nullable();
            $table->timestamp('second_email_sent_at')->nullable();
            $table->timestamp('third_email_sent_at')->nullable();
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->decimal('cart_value', 10, 2);
            $table->decimal('recovery_discount_percentage', 5, 2)->default(0);
            $table->string('recovery_coupon_code')->nullable();
            $table->json('cart_snapshot')->nullable(); // Store cart items at abandonment time
            $table->string('abandonment_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('recovery_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_abandonment_recoveries');
    }
};