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
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('buyers', 'first_name')) {
                $table->string('first_name')->after('contact_name')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'last_name')) {
                $table->string('last_name')->after('first_name')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'mobile')) {
                $table->string('mobile')->after('phone')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'business_id')) {
                $table->unsignedBigInteger('business_id')->after('id')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'login_count')) {
                $table->integer('login_count')->default(0);
            }
            if (!Schema::hasColumn('buyers', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToRemove = [
                'first_name', 'last_name', 'mobile', 'business_id',
                'last_login_at', 'last_login_ip', 'login_count', 'last_activity_at'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('buyers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
