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
        // Add billing fields to quotes table
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'payment_method_preference')) {
                $table->string('payment_method_preference')->nullable()->after('payment_terms');
                $table->boolean('billing_validated')->default(false)->after('payment_method_preference');
                $table->json('billing_metadata')->nullable()->after('billing_validated');
                $table->timestamp('billing_initiated_at')->nullable()->after('billing_metadata');
                $table->string('stripe_payment_intent_id')->nullable()->after('billing_initiated_at');
                $table->index('stripe_payment_intent_id');
            }
        });
        
        // Add billing fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'quote_id')) {
                $table->unsignedBigInteger('quote_id')->nullable()->after('id');
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('orders', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('payment_method');
                $table->string('stripe_invoice_id')->nullable()->after('stripe_payment_intent_id');
                $table->string('stripe_charge_id')->nullable()->after('stripe_invoice_id');
                $table->timestamp('paid_at')->nullable()->after('payment_due_date');
                $table->decimal('platform_fee', 10, 2)->default(0)->after('total_amount');
                $table->json('billing_address')->nullable()->after('delivery_address_id');
                $table->string('purchase_order_number')->nullable()->after('invoice_number');
                
                $table->index('stripe_payment_intent_id');
                $table->index('stripe_invoice_id');
                $table->index('paid_at');
            }
        });
        
        // Add billing fields to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'quote_id')) {
                $table->unsignedBigInteger('quote_id')->nullable()->after('invoice_id');
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'processing_fee')) {
                $table->decimal('processing_fee', 10, 2)->default(0)->after('amount');
                $table->decimal('net_amount', 10, 2)->nullable()->after('processing_fee');
                $table->string('payment_processor')->default('stripe')->after('payment_method');
                $table->json('processor_response')->nullable()->after('metadata');
                $table->string('risk_level')->nullable()->after('processor_response');
                $table->integer('risk_score')->nullable()->after('risk_level');
                $table->json('fraud_indicators')->nullable()->after('risk_score');
                $table->boolean('requires_manual_review')->default(false)->after('fraud_indicators');
                $table->timestamp('reviewed_at')->nullable()->after('requires_manual_review');
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
                
                $table->index('risk_level');
                $table->index('requires_manual_review');
            }
        });
        
        // Add credit fields to buyers table
        Schema::table('buyers', function (Blueprint $table) {
            if (!Schema::hasColumn('buyers', 'credit_approved')) {
                $table->boolean('credit_approved')->default(false)->after('status');
                $table->decimal('credit_limit', 10, 2)->nullable()->after('credit_approved');
                $table->integer('credit_terms_days')->nullable()->after('credit_limit');
                $table->date('credit_approved_date')->nullable()->after('credit_terms_days');
                $table->string('credit_status')->default('pending')->after('credit_approved_date');
                $table->json('credit_history')->nullable()->after('credit_status');
                $table->boolean('bank_account_verified')->default(false)->after('credit_history');
                $table->timestamp('bank_account_verified_at')->nullable()->after('bank_account_verified');
                
                $table->index('credit_approved');
                $table->index('credit_status');
            }
        });
        
        // Create addresses table if not exists
        if (!Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->morphs('addressable');
                $table->string('type')->default('delivery'); // delivery, billing, etc.
                $table->string('line1');
                $table->string('line2')->nullable();
                $table->string('suburb');
                $table->string('state');
                $table->string('postcode', 10);
                $table->string('country')->default('AU');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_validated')->default(false);
                $table->json('validation_data')->nullable();
                $table->timestamps();
                
                $table->index(['addressable_type', 'addressable_id']);
                $table->index('type');
                $table->index('is_default');
                $table->index('postcode');
            });
        }
        
        // Create payment_methods table if not exists
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('buyer_id');
                $table->string('type'); // card, bank_account, etc.
                $table->string('stripe_payment_method_id')->nullable();
                $table->string('brand')->nullable(); // visa, mastercard, etc.
                $table->string('last4')->nullable();
                $table->string('exp_month', 2)->nullable();
                $table->string('exp_year', 4)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bsb', 6)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
                $table->index('stripe_payment_method_id');
                $table->index('type');
                $table->index('is_default');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method_preference',
                'billing_validated',
                'billing_metadata',
                'billing_initiated_at',
                'stripe_payment_intent_id'
            ]);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn([
                'quote_id',
                'stripe_payment_intent_id',
                'stripe_invoice_id',
                'stripe_charge_id',
                'paid_at',
                'platform_fee',
                'billing_address',
                'purchase_order_number'
            ]);
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn([
                'quote_id',
                'processing_fee',
                'net_amount',
                'payment_processor',
                'processor_response',
                'risk_level',
                'risk_score',
                'fraud_indicators',
                'requires_manual_review',
                'reviewed_at',
                'reviewed_by'
            ]);
        });
        
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn([
                'credit_approved',
                'credit_limit',
                'credit_terms_days',
                'credit_approved_date',
                'credit_status',
                'credit_history',
                'bank_account_verified',
                'bank_account_verified_at'
            ]);
        });
        
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('payment_methods');
    }
};
