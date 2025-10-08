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
        Schema::create('enhanced_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            
            // Enhanced fields
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('category')->default('general'); // order, payment, delivery, system, etc.
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('channels')->nullable(); // email, sms, push, database
            $table->json('metadata')->nullable(); // additional data for rendering
            
            // Status tracking
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Action tracking
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->boolean('is_actionable')->default(false);
            $table->boolean('requires_acknowledgment')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            
            // Team/sharing features
            $table->uuid('team_id')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->json('shared_with')->nullable(); // user IDs or roles
            
            // Template and automation
            $table->string('template_id')->nullable();
            $table->string('automation_rule_id')->nullable();
            $table->boolean('is_automated')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['type', 'category']);
            $table->index(['status', 'priority']);
            $table->index(['created_at', 'read_at']);
            $table->index('scheduled_at');
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_notifications');
    }
};