<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make role_id nullable in business_users table
     */
    public function up(): void
    {
        Schema::table('business_users', function (Blueprint $table) {
            // Make role_id nullable
            $table->foreignId('role_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
        });
    }
};