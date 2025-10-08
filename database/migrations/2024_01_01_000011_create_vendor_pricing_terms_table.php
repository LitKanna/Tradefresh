<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_pricing_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('term_type'); // 'discount', 'credit_terms', 'volume_pricing'
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->integer('payment_terms_days')->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->integer('minimum_order_quantity')->nullable();
            $table->json('volume_tiers')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->enum('status', ['active', 'pending', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index(['vendor_id', 'user_id']);
            $table->index(['is_active', 'valid_from', 'valid_until']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_pricing_terms');
    }
};