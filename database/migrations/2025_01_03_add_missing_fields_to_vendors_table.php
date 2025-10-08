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
        Schema::table('vendors', function (Blueprint $table) {
            // Add username field if it doesn't exist
            if (!Schema::hasColumn('vendors', 'username')) {
                $table->string('username')->nullable()->after('email');
            }
            
            // Add vendor_type field if it doesn't exist
            if (!Schema::hasColumn('vendors', 'vendor_type')) {
                $table->string('vendor_type')->default('business')->after('business_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['username', 'vendor_type']);
        });
    }
};