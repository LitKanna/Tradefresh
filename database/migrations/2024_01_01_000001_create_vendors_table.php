<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            
            // Business Information
            $table->string('abn');
            $table->string('business_name');
            $table->enum('business_type', ['company', 'partnership', 'sole_trader', 'trust']);
            $table->enum('vendor_category', ['fruits_vegetables', 'dairy_eggs', 'meat_seafood', 'bakery', 'beverages', 'dry_goods', 'frozen_foods', 'other']);
            
            // Contact Information
            $table->string('contact_name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Address Information
            $table->string('address');
            $table->string('suburb');
            $table->string('state');
            $table->string('postcode');
            
            // Status
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('verification_status', ['unverified', 'verified'])->default('unverified');
            
            // System Fields
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('email');
            $table->index('abn');
            $table->index('status');
            $table->index('verification_status');
            $table->index('business_name');
            $table->index('vendor_category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendors');
    }
};