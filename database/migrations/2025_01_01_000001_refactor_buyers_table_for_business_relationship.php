<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for refactoring buyers table to properly link with businesses.
     * This migration enhances the existing buyers table to properly separate individual users from businesses.
     */
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Add business relationship
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->onDelete('cascade');
            
            // Add user-specific fields
            $table->string('first_name')->nullable()->after('contact_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('mobile_phone')->nullable()->after('phone');
            $table->string('position')->nullable()->after('mobile_phone');
            $table->string('department')->nullable()->after('position');
            
            // Add buyer-specific permissions
            $table->boolean('can_place_orders')->default(true)->after('buyer_type');
            $table->boolean('can_view_invoices')->default(false)->after('can_place_orders');
            $table->boolean('can_manage_users')->default(false)->after('can_view_invoices');
            $table->boolean('is_primary_contact')->default(false)->after('can_manage_users');
            
            // Add authentication fields
            $table->string('two_factor_secret')->nullable()->after('password');
            $table->string('two_factor_recovery_codes', 1000)->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            // Add activity tracking
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('login_count')->default(0);
            $table->timestamp('password_changed_at')->nullable();
            
            // Add audit fields
            $table->foreignId('created_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Add indexes for performance
            $table->index('business_id');
            $table->index('is_primary_contact');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'is_primary_contact']);
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['business_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['verified_by']);
            
            // Drop indexes
            $table->dropIndex(['business_id']);
            $table->dropIndex(['is_primary_contact']);
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['business_id', 'is_primary_contact']);
            $table->dropIndex(['last_login_at']);
            
            // Drop columns
            $table->dropColumn([
                'business_id',
                'first_name',
                'last_name',
                'mobile_phone',
                'position',
                'department',
                'can_place_orders',
                'can_view_invoices',
                'can_manage_users',
                'is_primary_contact',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'last_login_at',
                'last_login_ip',
                'login_count',
                'password_changed_at',
                'created_by',
                'updated_by',
                'verified_by',
                'verified_at',
                'verification_notes'
            ]);
        });
    }
};