<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing columns to business_users table
     */
    public function up(): void
    {
        Schema::table('business_users', function (Blueprint $table) {
            // Add missing columns after buyer_id
            $table->string('user_type')->default('buyer')->after('buyer_id');
            $table->string('email')->nullable()->after('user_type');
            $table->string('role')->nullable()->after('email');
            
            // Add is_primary column
            $table->boolean('is_primary')->default(false)->after('position');
            
            // Add joined_at column
            $table->timestamp('joined_at')->nullable()->after('is_primary');
            
            // Add permission columns that might be needed
            $table->json('permissions')->nullable()->after('role_id');
            $table->json('custom_permissions')->nullable()->after('permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type',
                'email', 
                'role',
                'is_primary',
                'joined_at',
                'permissions',
                'custom_permissions'
            ]);
        });
    }
};