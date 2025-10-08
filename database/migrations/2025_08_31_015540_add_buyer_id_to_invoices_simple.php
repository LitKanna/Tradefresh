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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'buyer_id')) {
                $table->foreignId('buyer_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('invoices', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('buyer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'buyer_id')) {
                $table->dropColumn('buyer_id');
            }
            if (Schema::hasColumn('invoices', 'vendor_id')) {
                $table->dropColumn('vendor_id');
            }
        });
    }
};
