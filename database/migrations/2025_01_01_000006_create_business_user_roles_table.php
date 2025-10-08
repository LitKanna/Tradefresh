<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for business user roles.
     * Defines available roles for users within a business.
     */
    public function up(): void
    {
        Schema::create('business_user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->string('role_slug')->unique();
            $table->text('description')->nullable();
            
            // Permission flags
            $table->boolean('can_place_orders')->default(false);
            $table->boolean('can_view_orders')->default(false);
            $table->boolean('can_modify_orders')->default(false);
            $table->boolean('can_cancel_orders')->default(false);
            
            $table->boolean('can_view_invoices')->default(false);
            $table->boolean('can_pay_invoices')->default(false);
            $table->boolean('can_download_statements')->default(false);
            
            $table->boolean('can_manage_products')->default(false);
            $table->boolean('can_manage_pricing')->default(false);
            $table->boolean('can_view_reports')->default(false);
            
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_manage_vehicles')->default(false);
            $table->boolean('can_manage_pickup_preferences')->default(false);
            $table->boolean('can_manage_business_details')->default(false);
            
            $table->boolean('can_access_api')->default(false);
            $table->boolean('can_export_data')->default(false);
            
            // Financial permissions
            $table->decimal('max_order_value', 10, 2)->nullable(); // Maximum order value this role can place
            $table->decimal('daily_order_limit', 10, 2)->nullable(); // Daily spending limit
            $table->decimal('monthly_order_limit', 10, 2)->nullable(); // Monthly spending limit
            $table->boolean('requires_order_approval')->default(false); // Orders need approval from higher role
            
            // Role hierarchy
            $table->integer('hierarchy_level')->default(1); // 1 = lowest, higher number = more authority
            $table->boolean('is_system_role')->default(false); // Cannot be deleted if true
            $table->boolean('is_default')->default(false); // Assigned to new users by default
            
            // Display
            $table->integer('display_order')->default(0);
            $table->string('color_badge')->nullable(); // For UI display
            
            $table->timestamps();
            
            // Indexes
            $table->index('role_slug');
            $table->index('hierarchy_level');
            $table->index('is_system_role');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_user_roles');
    }
};