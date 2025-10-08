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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit_card', 'debit_card', 'ach', 'paypal', 'terms']);
            $table->string('nickname')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Credit/Debit Card fields
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            $table->string('card_holder_name')->nullable();
            $table->text('card_token')->nullable(); // Encrypted token from payment processor
            
            // ACH fields
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable(); // checking, savings
            $table->string('account_last_four', 4)->nullable();
            $table->string('routing_number_last_four', 4)->nullable();
            $table->text('ach_token')->nullable(); // Encrypted token
            
            // PayPal fields
            $table->string('paypal_email')->nullable();
            $table->text('paypal_token')->nullable();
            
            // Payment terms fields
            $table->integer('terms_days')->nullable(); // Net 30, Net 60, etc.
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('available_credit', 15, 2)->nullable();
            $table->date('terms_approved_date')->nullable();
            $table->string('terms_approved_by')->nullable();
            
            // Billing address
            $table->string('billing_name')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_address2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_country')->default('US');
            $table->string('billing_phone')->nullable();
            
            // Verification and compliance
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_status')->nullable();
            $table->text('verification_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};