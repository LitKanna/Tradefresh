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
        // Check if the tables and indexes exist before adding them
        $sm = Schema::getConnection()->getDoctrineSchemaManager();

        // Add indexes to quotes table if they don't exist
        if (Schema::hasTable('quotes')) {
            $indexes = $sm->listTableIndexes('quotes');
            Schema::table('quotes', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['quotes_buyer_id_index'])) {
                    $table->index('buyer_id');
                }
                if (! isset($indexes['quotes_vendor_id_index'])) {
                    $table->index('vendor_id');
                }
                if (! isset($indexes['quotes_status_created_at_index'])) {
                    $table->index(['status', 'created_at']);
                }
            });
        }

        // Add indexes to products table if they don't exist
        if (Schema::hasTable('products')) {
            $indexes = $sm->listTableIndexes('products');
            Schema::table('products', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['products_is_active_index'])) {
                    $table->index('is_active');
                }
                if (! isset($indexes['products_vendor_id_index'])) {
                    $table->index('vendor_id');
                }
                if (! isset($indexes['products_category_id_index'])) {
                    $table->index('category_id');
                }
            });
        }

        // Add indexes to orders table if they don't exist
        if (Schema::hasTable('orders')) {
            $indexes = $sm->listTableIndexes('orders');
            Schema::table('orders', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['orders_buyer_id_index'])) {
                    $table->index('buyer_id');
                }
                if (! isset($indexes['orders_vendor_id_index'])) {
                    $table->index('vendor_id');
                }
                if (! isset($indexes['orders_status_index'])) {
                    $table->index('status');
                }
                if (! isset($indexes['orders_status_created_at_index'])) {
                    $table->index(['status', 'created_at']);
                }
            });
        }

        // Add indexes to rfqs table if they don't exist
        if (Schema::hasTable('rfqs')) {
            $indexes = $sm->listTableIndexes('rfqs');
            Schema::table('rfqs', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['rfqs_buyer_id_index'])) {
                    $table->index('buyer_id');
                }
                if (! isset($indexes['rfqs_status_index'])) {
                    $table->index('status');
                }
                if (! isset($indexes['rfqs_status_created_at_index'])) {
                    $table->index(['status', 'created_at']);
                }
            });
        }

        // Add indexes to buyers table if they don't exist
        if (Schema::hasTable('buyers')) {
            $indexes = $sm->listTableIndexes('buyers');
            Schema::table('buyers', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['buyers_email_index'])) {
                    $table->index('email');
                }
                if (! isset($indexes['buyers_status_index'])) {
                    $table->index('status');
                }
            });
        }

        // Add indexes to vendors table if they don't exist
        if (Schema::hasTable('vendors')) {
            $indexes = $sm->listTableIndexes('vendors');
            Schema::table('vendors', function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['vendors_email_index'])) {
                    $table->index('email');
                }
                if (! isset($indexes['vendors_status_index'])) {
                    $table->index('status');
                }
                if (! isset($indexes['vendors_is_verified_index'])) {
                    $table->index('is_verified');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove quotes indexes
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['status', 'created_at']);
        });

        // Remove products indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['category_id']);
        });

        // Remove orders indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'created_at']);
        });

        // Remove rfqs indexes
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'created_at']);
        });

        // Remove buyers indexes
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
        });

        // Remove vendors indexes
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_verified']);
        });
    }
};
