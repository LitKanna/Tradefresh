<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buyer leads discovered through BulkHunter
        Schema::create('buyer_leads', function (Blueprint $table) {
            $table->id();
            $table->string('abn', 11)->nullable()->unique();
            $table->string('business_name');
            $table->string('trading_name')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('business_type')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();

            // Location
            $table->text('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('state', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact Info
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Business Intelligence
            $table->enum('size_classification', ['WHALE', 'BIG', 'MEDIUM', 'SMALL'])->nullable();
            $table->integer('weekly_volume_estimate')->nullable();
            $table->decimal('monthly_spend_estimate', 10, 2)->nullable();
            $table->integer('employee_count')->nullable();
            $table->integer('years_in_business')->nullable();

            // Google Maps Data
            $table->string('google_place_id')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->integer('google_reviews_count')->nullable();
            $table->integer('google_price_level')->nullable();
            $table->json('opening_hours')->nullable();
            $table->boolean('is_currently_open')->nullable();

            // Supplier Intelligence
            $table->string('current_supplier')->nullable();
            $table->boolean('using_competitor_platform')->default(false);
            $table->boolean('unhappy_with_supplier')->default(false);
            $table->text('supplier_pain_points')->nullable();

            // Scoring
            $table->integer('size_score')->nullable();
            $table->integer('opportunity_score')->nullable();
            $table->integer('credit_score')->nullable();
            $table->decimal('final_score', 5, 2)->nullable();

            // Status
            $table->enum('status', ['NEW', 'ENRICHED', 'QUALIFIED', 'CONTACTED', 'NEGOTIATING', 'CONVERTED', 'LOST'])->default('NEW');
            $table->foreignId('assigned_vendor_id')->nullable()->constrained('vendors');

            // Metadata
            $table->string('source', 50)->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('last_enriched_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('postcode');
            $table->index('category');
            $table->index('status');
            $table->index('final_score');
            $table->index('abn');
        });

        // Contact persons at businesses
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('buyer_leads')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->boolean('is_decision_maker')->default(false);
            $table->integer('confidence_score')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->index('lead_id');
            $table->index('email');
        });

        // Outreach tracking
        Schema::create('outreach_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('buyer_leads')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('lead_contacts');
            $table->enum('channel', ['EMAIL', 'SMS', 'CALL', 'LINKEDIN', 'WHATSAPP']);
            $table->string('campaign_type', 50)->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('response')->nullable();
            $table->decimal('sentiment_score', 3, 2)->nullable();
            $table->boolean('converted')->default(false);
            $table->timestamps();

            $table->index('lead_id');
            $table->index('sent_at');
            $table->index('channel');
        });

        // Lead notes and activities
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('buyer_leads')->cascadeOnDelete();
            $table->string('activity_type', 50);
            $table->text('description')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->string('next_action')->nullable();
            $table->date('next_action_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('vendors');
            $table->timestamps();

            $table->index('lead_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('outreach_campaigns');
        Schema::dropIfExists('lead_contacts');
        Schema::dropIfExists('buyer_leads');
    }
};
