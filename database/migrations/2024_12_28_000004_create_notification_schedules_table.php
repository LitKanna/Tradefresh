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
        Schema::create('notification_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Schedule configuration
            $table->string('trigger_type'); // time_based, event_based, condition_based
            $table->json('trigger_config'); // configuration for the trigger
            $table->string('frequency'); // once, daily, weekly, monthly, custom
            $table->json('frequency_config')->nullable(); // detailed frequency config
            
            // Template and content
            $table->uuid('template_id')->nullable();
            $table->json('template_variables')->nullable();
            $table->json('channels'); // which channels to use
            
            // Targeting
            $table->string('audience_type'); // all, buyers, vendors, custom, team
            $table->json('audience_filter')->nullable(); // filters for custom audience
            $table->uuid('team_id')->nullable(); // for team-specific schedules
            
            // Timing
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->time('send_time')->nullable(); // time of day to send
            $table->string('timezone')->default('Australia/Sydney');
            $table->json('blackout_dates')->nullable(); // dates to skip
            
            // Status and control
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->boolean('is_recurring')->default(false);
            $table->integer('max_occurrences')->nullable(); // limit number of sends
            $table->integer('current_occurrences')->default(0);
            
            // Conditions and rules
            $table->json('conditions')->nullable(); // conditions that must be met
            $table->json('rules')->nullable(); // additional rules
            $table->integer('priority')->default(5); // 1-10 priority
            
            // Tracking
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('last_run_result')->nullable();
            $table->integer('total_sent')->default(0);
            $table->integer('total_failed')->default(0);
            
            // Creator
            $table->uuid('created_by'); // admin who created it
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'next_run_at']);
            $table->index(['trigger_type', 'audience_type']);
            $table->index('team_id');
            $table->index('template_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_schedules');
    }
};