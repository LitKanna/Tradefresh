<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add authentication fields if they don't exist
            if (!Schema::hasColumn('vendors', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('vendors', 'password')) {
                $table->string('password')->after('username');
            }
            
            if (!Schema::hasColumn('vendors', 'abn')) {
                $table->string('abn', 11)->nullable()->after('tax_id');
            }
            
            if (!Schema::hasColumn('vendors', 'business_name')) {
                $table->string('business_name')->nullable()->after('company_name');
            }
            
            if (!Schema::hasColumn('vendors', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('vendors', 'vendor_type')) {
                $table->enum('vendor_type', ['individual', 'business', 'enterprise'])->default('business')->after('business_type');
            }
            
            if (!Schema::hasColumn('vendors', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('vendors', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            
            if (!Schema::hasColumn('vendors', 'suburb')) {
                $table->string('suburb')->nullable()->after('city');
            }
            
            // Add indexes for performance
            if (!Schema::hasIndex('vendors', 'vendors_abn_index')) {
                $table->index('abn');
            }
            
            if (!Schema::hasIndex('vendors', 'vendors_username_index')) {
                $table->index('username');
            }
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['abn']);
            $table->dropIndex(['username']);
            
            // Drop columns
            $table->dropColumn([
                'username',
                'password',
                'abn',
                'business_name',
                'contact_name',
                'vendor_type',
                'email_verified_at',
                'remember_token',
                'suburb'
            ]);
        });
    }
};