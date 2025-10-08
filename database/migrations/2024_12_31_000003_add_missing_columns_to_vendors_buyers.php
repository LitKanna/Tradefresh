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
        // Add missing columns to vendors table
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (!Schema::hasColumn('vendors', 'rating')) {
                    $table->decimal('rating', 3, 2)->nullable()->after('status');
                }
                if (!Schema::hasColumn('vendors', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('rating');
                }
            });
        }

        // Add missing columns to buyers table
        if (Schema::hasTable('buyers')) {
            Schema::table('buyers', function (Blueprint $table) {
                if (!Schema::hasColumn('buyers', 'tax_id')) {
                    $table->string('tax_id')->nullable()->after('address');
                }
                if (!Schema::hasColumn('buyers', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('tax_id');
                }
                if (!Schema::hasColumn('buyers', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('is_verified');
                }
            });
        }

        // Add user_id to orders if missing
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('order_number')
                    ->constrained()->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (Schema::hasColumn('vendors', 'rating')) {
                    $table->dropColumn('rating');
                }
                if (Schema::hasColumn('vendors', 'is_verified')) {
                    $table->dropColumn('is_verified');
                }
            });
        }

        if (Schema::hasTable('buyers')) {
            Schema::table('buyers', function (Blueprint $table) {
                if (Schema::hasColumn('buyers', 'tax_id')) {
                    $table->dropColumn('tax_id');
                }
                if (Schema::hasColumn('buyers', 'is_verified')) {
                    $table->dropColumn('is_verified');
                }
                if (Schema::hasColumn('buyers', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};