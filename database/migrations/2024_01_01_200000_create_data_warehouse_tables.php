<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for comprehensive data warehouse
     */
    public function up(): void
    {
        // Fact table for sales transactions (denormalized for fast queries)
        Schema::create('fact_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sales_date')->index();
            $table->string('docket_number')->index();
            $table->string('buyer_code')->index();
            $table->string('buyer_name');
            $table->string('product')->index();
            $table->string('pack_type');
            $table->string('product_class');
            $table->date('arrival_date')->nullable();
            $table->string('consignment_number')->nullable();
            $table->integer('quantity');
            $table->decimal('rate', 10, 2);
            $table->decimal('total', 12, 2);
            $table->string('original_docket')->nullable();
            $table->string('salesman')->index();
            $table->string('customer_type')->index();
            
            // Additional computed fields for analytics
            $table->decimal('unit_price', 10, 4)->virtualAs('rate / NULLIF(quantity, 0)');
            $table->integer('week_number')->virtualAs('WEEK(sales_date)');
            $table->integer('month_number')->virtualAs('MONTH(sales_date)');
            $table->integer('quarter')->virtualAs('QUARTER(sales_date)');
            $table->integer('year')->virtualAs('YEAR(sales_date)');
            
            $table->timestamps();
            
            // Composite indexes for common query patterns
            $table->index(['sales_date', 'product']);
            $table->index(['buyer_code', 'sales_date']);
            $table->index(['product', 'buyer_code', 'sales_date']);
            $table->index(['salesman', 'sales_date']);
        });

        // Dimension table for products
        Schema::create('dim_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('product_name')->index();
            $table->string('category')->index();
            $table->string('sub_category')->nullable();
            $table->string('unit_of_measure');
            $table->boolean('is_organic')->default(false);
            $table->boolean('is_seasonal')->default(false);
            $table->json('seasonal_months')->nullable();
            $table->decimal('avg_shelf_life_days', 5, 2)->nullable();
            $table->json('nutritional_info')->nullable();
            $table->timestamps();
            
            $table->index(['category', 'product_name']);
        });

        // Dimension table for vendors/suppliers
        Schema::create('dim_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code')->unique();
            $table->string('vendor_name')->index();
            $table->string('business_type');
            $table->string('primary_category')->nullable();
            $table->decimal('reliability_score', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->decimal('price_competitiveness_score', 5, 2)->default(0);
            $table->integer('years_in_business')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            
            $table->index('business_type');
            $table->index(['reliability_score', 'quality_score']);
        });

        // Aggregated daily price statistics
        Schema::create('agg_daily_prices', function (Blueprint $table) {
            $table->id();
            $table->date('price_date')->index();
            $table->string('product')->index();
            $table->string('pack_type');
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->decimal('avg_price', 10, 2);
            $table->decimal('median_price', 10, 2);
            $table->decimal('std_dev', 10, 4);
            $table->integer('transaction_count');
            $table->integer('total_quantity');
            $table->decimal('total_value', 12, 2);
            $table->timestamps();
            
            $table->unique(['price_date', 'product', 'pack_type']);
            $table->index(['product', 'price_date']);
        });

        // Customer behavior patterns
        Schema::create('customer_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_code')->index();
            $table->string('pattern_type'); // 'purchasing', 'seasonal', 'loyalty'
            $table->json('pattern_data');
            $table->decimal('confidence_score', 5, 2);
            $table->date('pattern_start_date');
            $table->date('pattern_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['buyer_code', 'pattern_type']);
            $table->index('is_active');
        });

        // Price discrimination detection
        Schema::create('price_discrimination_events', function (Blueprint $table) {
            $table->id();
            $table->date('event_date')->index();
            $table->string('product')->index();
            $table->string('discriminating_factor'); // 'customer_type', 'volume', 'salesman'
            $table->decimal('price_variance', 10, 2);
            $table->decimal('discrimination_index', 5, 2);
            $table->json('affected_buyers');
            $table->json('price_tiers');
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->timestamps();
            
            $table->index(['product', 'event_date']);
            $table->index('severity');
        });

        // Machine learning predictions
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('prediction_type')->index(); // 'price', 'demand', 'churn'
            $table->string('target_entity')->index(); // product, customer, vendor
            $table->date('prediction_date')->index();
            $table->integer('horizon_days');
            $table->json('predicted_values');
            $table->decimal('confidence_score', 5, 2);
            $table->string('model_version');
            $table->json('feature_importance')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['prediction_type', 'target_entity', 'prediction_date']);
            $table->index(['is_active', 'prediction_date']);
        });

        // Real-time alerts configuration
        Schema::create('alert_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers');
            $table->string('alert_type')->index(); // 'price_drop', 'new_product', 'vendor_issue'
            $table->json('conditions');
            $table->json('notification_channels'); // ['email', 'sms', 'push']
            $table->string('frequency'); // 'instant', 'hourly', 'daily'
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();
            
            $table->index(['buyer_id', 'is_active']);
            $table->index(['alert_type', 'is_active']);
        });

        // Alert history for tracking
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_id')->constrained('alert_configurations');
            $table->foreignId('buyer_id')->constrained('buyers');
            $table->string('alert_type');
            $table->json('alert_data');
            $table->string('status'); // 'sent', 'viewed', 'acted_upon'
            $table->timestamp('triggered_at');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('action_taken')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
        });

        // Vendor performance metrics (time-series)
        Schema::create('vendor_performance_series', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code')->index();
            $table->date('metric_date')->index();
            $table->decimal('delivery_reliability', 5, 2);
            $table->decimal('quality_consistency', 5, 2);
            $table->decimal('price_stability', 5, 2);
            $table->decimal('response_time_hours', 8, 2)->nullable();
            $table->integer('order_fulfillment_rate');
            $table->integer('dispute_count')->default(0);
            $table->decimal('customer_satisfaction', 5, 2)->nullable();
            $table->json('detailed_metrics')->nullable();
            $table->timestamps();
            
            $table->unique(['vendor_code', 'metric_date']);
            $table->index(['metric_date', 'delivery_reliability']);
        });

        // Market intelligence data
        Schema::create('market_intelligence', function (Blueprint $table) {
            $table->id();
            $table->date('intelligence_date')->index();
            $table->string('intelligence_type'); // 'trend', 'disruption', 'opportunity'
            $table->string('category')->nullable();
            $table->string('product')->nullable();
            $table->json('intelligence_data');
            $table->decimal('impact_score', 5, 2);
            $table->string('source'); // 'internal', 'external', 'computed'
            $table->decimal('confidence_level', 5, 2);
            $table->date('valid_until')->nullable();
            $table->timestamps();
            
            $table->index(['intelligence_type', 'intelligence_date']);
            $table->index(['category', 'intelligence_date']);
        });

        // Optimization recommendations
        Schema::create('optimization_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers');
            $table->string('recommendation_type'); // 'cost', 'vendor', 'timing', 'quantity'
            $table->string('priority'); // 'low', 'medium', 'high', 'critical'
            $table->json('recommendation_details');
            $table->decimal('potential_savings', 12, 2)->nullable();
            $table->decimal('implementation_effort', 5, 2); // 1-10 scale
            $table->decimal('confidence_score', 5, 2);
            $table->string('status')->default('pending'); // 'pending', 'reviewed', 'implemented', 'rejected'
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->json('implementation_results')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['recommendation_type', 'priority']);
            $table->index(['status', 'created_at']);
        });

        // Data quality metrics
        Schema::create('data_quality_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date')->index();
            $table->string('data_source');
            $table->integer('records_processed');
            $table->integer('records_rejected');
            $table->decimal('completeness_score', 5, 2);
            $table->decimal('accuracy_score', 5, 2);
            $table->decimal('consistency_score', 5, 2);
            $table->json('quality_issues')->nullable();
            $table->json('data_anomalies')->nullable();
            $table->timestamps();
            
            $table->index(['data_source', 'metric_date']);
        });

        // API usage tracking
        Schema::create('api_usage_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers');
            $table->string('endpoint')->index();
            $table->string('method');
            $table->integer('response_time_ms');
            $table->integer('response_size_bytes');
            $table->string('status_code');
            $table->json('request_params')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamps();
            
            $table->index(['buyer_id', 'requested_at']);
            $table->index(['endpoint', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_metrics');
        Schema::dropIfExists('data_quality_metrics');
        Schema::dropIfExists('optimization_recommendations');
        Schema::dropIfExists('market_intelligence');
        Schema::dropIfExists('vendor_performance_series');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_configurations');
        Schema::dropIfExists('ml_predictions');
        Schema::dropIfExists('price_discrimination_events');
        Schema::dropIfExists('customer_patterns');
        Schema::dropIfExists('agg_daily_prices');
        Schema::dropIfExists('dim_vendors');
        Schema::dropIfExists('dim_products');
        Schema::dropIfExists('fact_sales');
    }
};