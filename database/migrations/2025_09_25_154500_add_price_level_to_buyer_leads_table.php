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
        Schema::table('buyer_leads', function (Blueprint $table) {
            // Add Google price level column
            $table->integer('price_level')->nullable()->after('google_reviews_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyer_leads', function (Blueprint $table) {
            $table->dropColumn('price_level');
        });
    }
};
