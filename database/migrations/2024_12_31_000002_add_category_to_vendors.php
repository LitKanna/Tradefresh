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
        // Add category_id to vendors table
        if (Schema::hasTable('vendors') && !Schema::hasColumn('vendors', 'category_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('address')
                    ->constrained('vendor_categories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'category_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
    }
};