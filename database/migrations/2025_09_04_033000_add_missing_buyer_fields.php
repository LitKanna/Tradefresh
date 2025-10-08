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
            // Add company_name if it doesn't exist
            if (!Schema::hasColumn('buyers', 'company_name')) {
                $table->string('company_name')->nullable()->after('business_name');
            }
            
            // Add billing address fields
            if (!Schema::hasColumn('buyers', 'billing_address')) {
                $table->string('billing_address')->nullable();
                $table->string('billing_suburb')->nullable();
                $table->string('billing_state', 10)->nullable();
                $table->string('billing_postcode', 10)->nullable();
            }
            
            // Add shipping address fields  
            if (!Schema::hasColumn('buyers', 'shipping_address')) {
                $table->string('shipping_address')->nullable();
                $table->string('shipping_suburb')->nullable();
                $table->string('shipping_state', 10)->nullable();
                $table->string('shipping_postcode', 10)->nullable();
            }
            
            // Add credit and payment fields
            if (!Schema::hasColumn('buyers', 'credit_limit')) {
                $table->decimal('credit_limit', 10, 2)->default(0.00);
                $table->decimal('credit_used', 10, 2)->default(0.00);
                $table->string('payment_terms', 50)->default('prepaid');
            }
            
            // Add verification status
            if (!Schema::hasColumn('buyers', 'verification_status')) {
                $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            }
            
            // Add joined_at timestamp
            if (!Schema::hasColumn('buyers', 'joined_at')) {
                $table->timestamp('joined_at')->nullable();
            }
            
            // Add contact name if missing (for fallback)
            if (!Schema::hasColumn('buyers', 'contact_name')) {
                $table->string('contact_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $columnsToRemove = [
                'company_name',
                'billing_address',
                'billing_suburb', 
                'billing_state',
                'billing_postcode',
                'shipping_address',
                'shipping_suburb',
                'shipping_state', 
                'shipping_postcode',
                'credit_limit',
                'credit_used',
                'payment_terms',
                'verification_status',
                'joined_at'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('buyers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};