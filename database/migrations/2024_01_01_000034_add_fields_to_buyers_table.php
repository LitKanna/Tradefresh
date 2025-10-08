<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('buyers', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('buyers', 'default_delivery_address')) {
                $table->text('default_delivery_address')->nullable()->after('shipping_country');
            }
            
            if (!Schema::hasColumn('buyers', 'monthly_budget')) {
                $table->decimal('monthly_budget', 10, 2)->default(0)->after('credit_used');
            }
            
            if (!Schema::hasColumn('buyers', 'avatar')) {
                $table->string('avatar')->nullable()->after('metadata');
            }
            
            // Virtual columns for counts (these will be calculated dynamically)
            // We don't actually add these as database columns
        });
    }

    public function down()
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'default_delivery_address',
                'monthly_budget',
                'avatar'
            ]);
        });
    }
};