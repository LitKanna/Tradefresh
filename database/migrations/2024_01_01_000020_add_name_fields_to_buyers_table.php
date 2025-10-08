<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Add first_name and last_name columns after contact_name
            $table->string('first_name', 100)->nullable()->after('contact_name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            
            // Add additional helpful columns
            $table->string('mobile', 20)->nullable()->after('phone');
            $table->decimal('monthly_budget', 10, 2)->nullable()->after('credit_used');
            $table->string('avatar')->nullable()->after('metadata');
            
            // Add delivery address fields (separate from billing/shipping)
            $table->string('delivery_address')->nullable()->after('shipping_country');
            $table->string('delivery_suburb')->nullable()->after('delivery_address');
            $table->string('delivery_state')->nullable()->after('delivery_suburb');
            $table->string('delivery_postcode')->nullable()->after('delivery_state');
            $table->string('default_delivery_address')->nullable()->after('delivery_postcode');
            
            // Add counts for dashboard
            $table->integer('cart_items_count')->default(0)->after('avatar');
            $table->integer('unread_notifications_count')->default(0)->after('cart_items_count');
            $table->integer('pending_orders_count')->default(0)->after('unread_notifications_count');
            
            // Add business_id for linking to businesses table if it exists
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
            // Add is_primary_contact flag
            $table->boolean('is_primary_contact')->default(false)->after('verification_status');
            
            // Make some existing fields nullable for flexibility
            $table->string('company_name')->nullable()->change();
            $table->string('billing_address')->nullable()->change();
            $table->string('billing_suburb')->nullable()->change();
            $table->string('billing_state')->nullable()->change();
            $table->string('billing_postcode')->nullable()->change();
            $table->enum('business_type', ['restaurant', 'cafe', 'grocery', 'retailer', 'distributor', 'other', 'individual'])->nullable()->change();
            $table->enum('buyer_type', ['regular', 'premium', 'wholesale', 'individual'])->default('individual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'mobile',
                'monthly_budget',
                'avatar',
                'delivery_address',
                'delivery_suburb',
                'delivery_state',
                'delivery_postcode',
                'default_delivery_address',
                'cart_items_count',
                'unread_notifications_count',
                'pending_orders_count',
                'business_id',
                'is_primary_contact'
            ]);
            
            // Revert nullable changes
            $table->string('company_name')->nullable(false)->change();
            $table->string('billing_address')->nullable(false)->change();
            $table->string('billing_suburb')->nullable(false)->change();
            $table->string('billing_state')->nullable(false)->change();
            $table->string('billing_postcode')->nullable(false)->change();
            $table->enum('business_type', ['restaurant', 'cafe', 'grocery', 'retailer', 'distributor', 'other'])->nullable(false)->change();
            $table->enum('buyer_type', ['regular', 'premium', 'wholesale'])->default('regular')->change();
        });
    }
};