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
        Schema::table('buyers', function (Blueprint $table) {
            // Add activity tracking columns if they don't exist
            if (!Schema::hasColumn('buyers', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('buyers', 'last_ip_address')) {
                $table->string('last_ip_address', 45)->nullable()->after('last_activity_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'last_ip_address']);
        });
    }
};