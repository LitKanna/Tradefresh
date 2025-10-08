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
        Schema::create('data_processing_results', function (Blueprint $table) {
            $table->id();
            $table->string('processing_id')->unique();
            $table->timestamp('processed_at');
            $table->integer('total_transactions');
            $table->decimal('total_value', 15, 2);
            $table->json('intelligence_summary');
            $table->json('vendor_intelligence')->nullable();
            $table->json('product_performance')->nullable();
            $table->json('customer_patterns')->nullable();
            $table->json('price_discrimination')->nullable();
            $table->json('salesman_performance')->nullable();
            $table->json('market_timing')->nullable();
            $table->json('cost_optimization')->nullable();
            $table->timestamps();
            
            $table->index('processing_id');
            $table->index('processed_at');
        });
        
        // Create vendor intelligence table
        Schema::create('vendor_intelligence', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name');
            $table->decimal('total_spend', 15, 2);
            $table->integer('transaction_count');
            $table->integer('unique_products');
            $table->decimal('avg_transaction_size', 10, 2);
            $table->decimal('price_consistency_score', 5, 2);
            $table->decimal('reliability_score', 5, 2);
            $table->string('pricing_fairness');
            $table->decimal('overpayment_amount', 10, 2)->default(0);
            $table->json('best_products')->nullable();
            $table->json('worst_products')->nullable();
            $table->string('recommendation');
            $table->timestamps();
            
            $table->index('vendor_name');
            $table->index('pricing_fairness');
        });
        
        // Create product performance table
        Schema::create('product_performance', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->decimal('total_revenue', 15, 2);
            $table->decimal('total_quantity', 10, 2);
            $table->decimal('avg_rate', 10, 2);
            $table->decimal('min_rate', 10, 2);
            $table->decimal('max_rate', 10, 2);
            $table->decimal('price_spread', 10, 2);
            $table->decimal('price_variation', 10, 2);
            $table->integer('transaction_count');
            $table->integer('unique_vendors');
            $table->string('best_vendor')->nullable();
            $table->string('worst_vendor')->nullable();
            $table->string('price_discrimination_level');
            $table->string('performance_rating');
            $table->timestamps();
            
            $table->index('product_name');
            $table->index('performance_rating');
        });
        
        // Create customer intelligence table  
        Schema::create('customer_intelligence', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_type')->nullable();
            $table->decimal('total_spend', 15, 2);
            $table->integer('order_count');
            $table->decimal('avg_order_value', 10, 2);
            $table->json('favorite_products')->nullable();
            $table->date('last_order_date')->nullable();
            $table->integer('customer_lifetime_days')->default(0);
            $table->string('status'); // ACTIVE, REGULAR, AT_RISK, DORMANT
            $table->string('value_segment'); // HIGH, MEDIUM, LOW
            $table->timestamps();
            
            $table->index('customer_name');
            $table->index('status');
            $table->index('value_segment');
        });
        
        // Create price discrimination analysis table
        Schema::create('price_discrimination_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->decimal('avg_rate', 10, 2);
            $table->decimal('min_rate', 10, 2);
            $table->decimal('max_rate', 10, 2);
            $table->decimal('price_range', 10, 2);
            $table->decimal('coefficient_of_variation', 10, 2);
            $table->string('discrimination_level'); // HIGH, MEDIUM, LOW
            $table->string('best_price_buyer')->nullable();
            $table->string('worst_price_buyer')->nullable();
            $table->decimal('price_discrimination_ratio', 5, 2);
            $table->decimal('total_overpayment', 15, 2);
            $table->json('affected_buyers')->nullable();
            $table->text('recommendation');
            $table->timestamps();
            
            $table->index('product');
            $table->index('discrimination_level');
        });
        
        // Create salesman performance table
        Schema::create('salesman_performance', function (Blueprint $table) {
            $table->id();
            $table->string('salesman_name');
            $table->decimal('total_sales', 15, 2);
            $table->integer('transaction_count');
            $table->decimal('avg_deal_size', 10, 2);
            $table->integer('unique_customers');
            $table->integer('unique_products');
            $table->decimal('pricing_fairness_score', 5, 2);
            $table->decimal('avg_discount_given', 5, 2);
            $table->decimal('customer_retention_rate', 5, 2);
            $table->string('performance_rating');
            $table->json('top_customers')->nullable();
            $table->json('top_products')->nullable();
            $table->timestamps();
            
            $table->index('salesman_name');
            $table->index('performance_rating');
        });
        
        // Create market timing intelligence table
        Schema::create('market_timing_intelligence', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('day_of_week');
            $table->decimal('total_sales', 15, 2);
            $table->integer('transaction_count');
            $table->decimal('avg_rate', 10, 2);
            $table->integer('unique_products');
            $table->integer('unique_buyers');
            $table->json('product_specific_timing')->nullable();
            $table->json('procurement_schedule')->nullable();
            $table->timestamps();
            
            $table->index('date');
            $table->index('day_of_week');
        });
        
        // Create cost optimization opportunities table
        Schema::create('cost_optimization_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('buyer');
            $table->string('product');
            $table->decimal('current_avg_rate', 10, 2);
            $table->decimal('market_best_rate', 10, 2);
            $table->decimal('market_avg_rate', 10, 2);
            $table->decimal('overpayment_percentage', 5, 2);
            $table->decimal('total_quantity_purchased', 10, 2);
            $table->decimal('potential_savings', 15, 2);
            $table->string('recommendation');
            $table->string('suggested_vendor')->nullable();
            $table->string('priority'); // HIGH, MEDIUM, LOW
            $table->timestamps();
            
            $table->index(['buyer', 'product']);
            $table->index('priority');
            $table->index('potential_savings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_optimization_opportunities');
        Schema::dropIfExists('market_timing_intelligence');
        Schema::dropIfExists('salesman_performance');
        Schema::dropIfExists('price_discrimination_analysis');
        Schema::dropIfExists('customer_intelligence');
        Schema::dropIfExists('product_performance');
        Schema::dropIfExists('vendor_intelligence');
        Schema::dropIfExists('data_processing_results');
    }
};