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
        Schema::table('buyer_leads', function (Blueprint $table) {
            // Only add columns that don't exist yet
            // Buyer Intelligence columns
            $table->string('buyer_persona')->nullable()->after('supplier_pain_points');
            $table->string('decision_maker')->nullable()->after('buyer_persona');
            $table->string('buying_cycle')->nullable()->after('decision_maker');
            $table->string('payment_terms')->nullable()->after('buying_cycle');
            $table->string('delivery_requirements')->nullable()->after('payment_terms');
            $table->text('special_requirements')->nullable()->after('delivery_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyer_leads', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_persona',
                'decision_maker',
                'buying_cycle',
                'payment_terms',
                'delivery_requirements',
                'special_requirements'
            ]);
        });
    }
};