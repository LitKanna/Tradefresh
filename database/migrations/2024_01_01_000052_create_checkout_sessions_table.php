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
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, expired
            $table->string('step')->default('shipping'); // shipping, billing, payment, review, confirmation
            
            // Shipping Information
            $table->json('shipping_address')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->date('preferred_delivery_date')->nullable();
            $table->string('delivery_time_slot')->nullable();
            $table->text('delivery_instructions')->nullable();
            
            // Billing Information
            $table->json('billing_address')->nullable();
            $table->boolean('same_as_shipping')->default(true);
            $table->string('company_name')->nullable();
            $table->string('abn')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->string('tax_id')->nullable();
            
            // Payment Information
            $table->string('payment_method')->nullable(); // credit_card, bank_transfer, credit_account, cod
            $table->string('payment_terms')->nullable(); // net30, net60, prepaid, cod
            $table->json('payment_details')->nullable();
            $table->string('purchase_order_number')->nullable();
            
            // Order Summary
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            
            // Additional Fields
            $table->boolean('agreed_to_terms')->default(false);
            $table->boolean('subscribe_to_newsletter')->default(false);
            $table->text('order_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};