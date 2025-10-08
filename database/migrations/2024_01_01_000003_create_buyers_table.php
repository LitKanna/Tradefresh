<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            
            // Business Information
            $table->string('abn')->nullable();
            $table->string('business_name');
            $table->enum('buyer_type', ['owner', 'co_owner', 'manager', 'buyer', 'salesman', 'accounts_member', 'authorized_rep']);
            $table->enum('business_type', ['company', 'partnership', 'sole_trader', 'trust']);
            $table->enum('purchase_category', ['fruits_vegetables', 'dairy_eggs', 'meat_seafood', 'bakery', 'beverages', 'dry_goods', 'frozen_foods', 'other']);
            
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
            
            // System Fields
            $table->rememberToken();
            $table->timestamps();

            // Indexes for performance
            $table->index('email');
            $table->index('abn');
            $table->index('status');
            $table->index('business_name');
            $table->index('buyer_type');
            $table->index('purchase_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyers');
    }
};