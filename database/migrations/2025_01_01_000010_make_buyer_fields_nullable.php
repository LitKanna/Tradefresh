<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make certain buyer fields nullable for individual accounts
     */
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Make company_name nullable for individual buyers
            $table->string('company_name')->nullable()->change();
            
            // Make contact_name nullable since we have first_name and last_name
            $table->string('contact_name')->nullable()->change();
            
            // Make business_type nullable for individual buyers
            $table->string('business_type')->nullable()->change();
            
            // Make billing address fields nullable for initial registration
            $table->string('billing_address')->nullable()->change();
            $table->string('billing_suburb')->nullable()->change();
            $table->string('billing_state')->nullable()->change();
            $table->string('billing_postcode')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('company_name')->nullable(false)->change();
            $table->string('contact_name')->nullable(false)->change();
            $table->string('business_type')->nullable(false)->change();
            $table->string('billing_address')->nullable(false)->change();
            $table->string('billing_suburb')->nullable(false)->change();
            $table->string('billing_state')->nullable(false)->change();
            $table->string('billing_postcode')->nullable(false)->change();
        });
    }
};