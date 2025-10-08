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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('abn', 11)->unique()->index();
            $table->string('entity_name');
            $table->string('entity_type_code', 50)->nullable();
            $table->string('entity_type_text')->nullable();
            $table->json('trading_names')->nullable();
            $table->string('business_type')->nullable(); // company/sole_trader/trust/partnership
            
            // ABN Status
            $table->enum('abn_status', ['active', 'cancelled'])->default('active');
            $table->date('abn_status_from_date')->nullable();
            
            // GST Registration
            $table->boolean('gst_registered')->default(false);
            $table->date('gst_registered_from')->nullable();
            $table->date('gst_registered_to')->nullable();
            
            // Business Address
            $table->string('address_state_code', 10)->nullable();
            $table->string('address_postcode', 10)->nullable();
            $table->text('address_full')->nullable();
            
            // Main business activity
            $table->string('main_business_activity_code')->nullable();
            $table->text('main_business_activity_description')->nullable();
            
            // ASIC Details (for companies)
            $table->string('acn', 9)->nullable()->index();
            $table->string('entity_status')->nullable();
            
            // Caching and verification
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('cached_until')->nullable();
            $table->boolean('verification_failed')->default(false);
            $table->text('verification_error')->nullable();
            
            // Additional metadata
            $table->json('raw_abr_response')->nullable();
            $table->string('data_source')->default('abr_api'); // abr_api, manual, fallback
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('abn_status');
            $table->index('gst_registered');
            $table->index('entity_type_code');
            $table->index('cached_until');
            $table->index(['abn', 'cached_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};