<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('vendor_id')->constrained('users');
            $table->foreignId('request_id')->nullable()->constrained('quote_requests');
            $table->string('product_name');
            $table->text('product_description')->nullable();
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('delivery_date')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'viewed', 'under_review', 'accepted', 'rejected', 'expired', 'withdrawn']);
            $table->timestamp('expires_at');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotes');
    }
};