<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingFieldsToBuyersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buyers', function (Blueprint $table) {
            // Onboarding tracking
            if (!Schema::hasColumn('buyers', 'onboarding_step')) {
                $table->string('onboarding_step')->nullable()->after('status');
            }
            if (!Schema::hasColumn('buyers', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            }
            if (!Schema::hasColumn('buyers', 'onboarding_skipped_at')) {
                $table->timestamp('onboarding_skipped_at')->nullable()->after('onboarding_completed_at');
            }
            
            // Business preferences
            if (!Schema::hasColumn('buyers', 'preferred_categories')) {
                $table->json('preferred_categories')->nullable()->after('business_type');
            }
            if (!Schema::hasColumn('buyers', 'order_frequency')) {
                $table->string('order_frequency')->nullable()->after('preferred_categories');
            }
            if (!Schema::hasColumn('buyers', 'average_order_value_range')) {
                $table->string('average_order_value_range')->nullable()->after('order_frequency');
            }
            if (!Schema::hasColumn('buyers', 'preferred_payment_method')) {
                $table->string('preferred_payment_method')->nullable()->after('average_order_value_range');
            }
            if (!Schema::hasColumn('buyers', 'delivery_preference')) {
                $table->string('delivery_preference')->nullable()->after('preferred_payment_method');
            }
            if (!Schema::hasColumn('buyers', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('delivery_preference');
            }
        });
        
        // Add indexes separately (SQLite doesn't support checking for indexes easily)
        try {
            Schema::table('buyers', function (Blueprint $table) {
                $table->index('onboarding_completed_at');
                $table->index('onboarding_step');
            });
        } catch (\Exception $e) {
            // Indexes might already exist
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex(['onboarding_completed_at']);
            $table->dropIndex(['onboarding_step']);
            
            $table->dropColumn([
                'onboarding_step',
                'onboarding_completed_at',
                'onboarding_skipped_at',
                'preferred_categories',
                'order_frequency',
                'average_order_value_range',
                'preferred_payment_method',
                'delivery_preference',
                'notification_preferences'
            ]);
        });
    }
}