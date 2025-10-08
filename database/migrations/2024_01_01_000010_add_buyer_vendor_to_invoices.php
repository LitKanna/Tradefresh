<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add buyer_id and vendor_id to invoices table
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add buyer_id and vendor_id columns after user_id
            $table->foreignId('buyer_id')->nullable()->after('user_id');
            $table->foreignId('vendor_id')->nullable()->after('buyer_id');
            
            // Add indexes for performance
            $table->index('buyer_id');
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['buyer_id', 'vendor_id']);
        });
    }
};