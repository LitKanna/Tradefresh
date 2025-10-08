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
        Schema::table('quotes', function (Blueprint $table) {
            // Add buyer_id if it doesn't exist
            if (!Schema::hasColumn('quotes', 'buyer_id')) {
                $table->foreignId('buyer_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            }
            
            // Add requested_delivery_date if it doesn't exist
            if (!Schema::hasColumn('quotes', 'requested_delivery_date')) {
                $table->date('requested_delivery_date')->nullable()->after('delivery_date');
            }
            
            // Add urgency_level if it doesn't exist
            if (!Schema::hasColumn('quotes', 'urgency_level')) {
                $table->string('urgency_level')->default('standard')->after('status');
            }
            
            // Add special_requirements if it doesn't exist
            if (!Schema::hasColumn('quotes', 'special_requirements')) {
                $table->text('special_requirements')->nullable()->after('urgency_level');
            }
            
            // Add requested_at if it doesn't exist
            if (!Schema::hasColumn('quotes', 'requested_at')) {
                $table->timestamp('requested_at')->nullable();
            }
            
            // Add indexes
            $table->index(['buyer_id', 'status'], 'quotes_buyer_status_index');
            $table->index(['vendor_id', 'status'], 'quotes_vendor_status_index');
            $table->index('urgency_level', 'quotes_urgency_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('quotes_buyer_status_index');
            $table->dropIndex('quotes_vendor_status_index');
            $table->dropIndex('quotes_urgency_index');
            
            // Drop columns
            if (Schema::hasColumn('quotes', 'buyer_id')) {
                $table->dropForeign(['buyer_id']);
                $table->dropColumn('buyer_id');
            }
            
            if (Schema::hasColumn('quotes', 'requested_delivery_date')) {
                $table->dropColumn('requested_delivery_date');
            }
            
            if (Schema::hasColumn('quotes', 'urgency_level')) {
                $table->dropColumn('urgency_level');
            }
            
            if (Schema::hasColumn('quotes', 'special_requirements')) {
                $table->dropColumn('special_requirements');
            }
            
            if (Schema::hasColumn('quotes', 'requested_at')) {
                $table->dropColumn('requested_at');
            }
        });
    }
};