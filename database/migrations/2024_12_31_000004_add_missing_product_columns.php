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
        // Add missing columns to existing products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'cost')) {
                    $table->decimal('cost', 15, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('products', 'min_quantity')) {
                    $table->integer('min_quantity')->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('products', 'unit')) {
                    $table->string('unit')->default('piece')->after('min_quantity');
                }
                if (!Schema::hasColumn('products', 'weight')) {
                    $table->decimal('weight', 10, 3)->nullable();
                }
                if (!Schema::hasColumn('products', 'weight_unit')) {
                    $table->string('weight_unit')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columnsToRemove = ['cost', 'min_quantity', 'unit', 'weight', 'weight_unit'];
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};