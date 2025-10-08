<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active');
            $table->enum('frequency', ['daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_order_date');
            $table->date('last_order_date')->nullable();
            $table->integer('total_orders')->default(0);
            $table->integer('max_orders')->nullable();
            $table->decimal('recurring_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('items'); // Stores product IDs and quantities
            $table->text('shipping_address');
            $table->text('billing_address');
            $table->string('payment_method');
            $table->json('metadata')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index('next_order_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};