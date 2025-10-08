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
        Schema::table('messages', function (Blueprint $table) {
            // Make quote_id nullable for general messaging
            $table->unsignedBigInteger('quote_id')->nullable()->change();

            // Add read_at timestamp for better tracking
            $table->timestamp('read_at')->nullable()->after('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Revert quote_id to non-nullable
            $table->unsignedBigInteger('quote_id')->nullable(false)->change();

            // Drop read_at column
            $table->dropColumn('read_at');
        });
    }
};
