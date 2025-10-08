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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable'); // Polymorphic relation to user
            $table->text('data'); // JSON data for the notification
            $table->timestamp('read_at')->nullable();
            $table->string('channel')->default('database'); // database, email, sms, whatsapp
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('category')->nullable(); // order, rfq, payment, system, etc.
            $table->string('action_url')->nullable(); // URL to action
            $table->string('action_text')->nullable(); // Button text
            $table->string('icon')->nullable();
            $table->boolean('is_actionable')->default(false);
            $table->timestamp('expires_at')->nullable(); // Auto-delete old notifications
            $table->timestamps();

            // Indexes
            // Note: morphs() already creates index for notifiable_type and notifiable_id
            $table->index('type');
            $table->index('read_at');
            $table->index('channel');
            $table->index('category');
            $table->index('priority');
            $table->index('created_at');
            $table->index(['category', 'priority', 'created_at']); // Notifications by category
            $table->index('expires_at'); // For cleanup jobs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};