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
        Schema::create('notification_preferences_enhanced', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable'); // buyer_id, vendor_id, etc.
            
            // Channel preferences
            $table->json('email_preferences')->default('{}'); // type -> enabled/disabled
            $table->json('sms_preferences')->default('{}');
            $table->json('push_preferences')->default('{}');
            $table->json('database_preferences')->default('{}');
            
            // Category preferences
            $table->json('category_preferences')->default('{}'); // category -> channels
            $table->json('priority_preferences')->default('{}'); // priority -> channels
            
            // Timing preferences
            $table->json('delivery_schedule')->default('{}'); // when to receive notifications
            $table->boolean('do_not_disturb')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->json('quiet_days')->nullable(); // days of week
            
            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('push_token')->nullable(); // FCM/APNS token
            $table->string('timezone')->default('Australia/Sydney');
            
            // Advanced preferences
            $table->integer('digest_frequency')->default(0); // 0=immediate, 1=daily, 7=weekly
            $table->time('digest_time')->default('09:00');
            $table->boolean('email_digest_enabled')->default(false);
            $table->json('custom_rules')->nullable(); // custom notification rules
            
            // Team preferences
            $table->uuid('team_id')->nullable();
            $table->boolean('inherit_team_preferences')->default(false);
            $table->json('team_role_preferences')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['notifiable_type', 'notifiable_id']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences_enhanced');
    }
};