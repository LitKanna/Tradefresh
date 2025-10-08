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
        // Fix vendor_categories table - remove vendor_id if it exists
        if (Schema::hasTable('vendor_categories')) {
            Schema::table('vendor_categories', function (Blueprint $table) {
                // Drop the unique constraint if it exists
                try {
                    $table->dropUnique(['vendor_id', 'category_id']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist, continue
                }
                
                // Drop vendor_id column if it exists
                if (Schema::hasColumn('vendor_categories', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                    $table->dropColumn('vendor_id');
                }
                
                // Drop category_id column if it exists  
                if (Schema::hasColumn('vendor_categories', 'category_id')) {
                    $table->dropForeign(['category_id']);
                    $table->dropColumn('category_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't restore incorrect columns
    }
};