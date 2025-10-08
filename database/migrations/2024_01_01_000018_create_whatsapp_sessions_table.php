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
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique();
            $table->nullableMorphs('user'); // Linked user (buyer, vendor)
            $table->string('session_id', 100)->unique();
            $table->enum('status', ['active', 'expired', 'blocked'])->default('active');
            $table->enum('state', ['welcome', 'menu', 'ordering', 'tracking', 'support'])->default('welcome');
            $table->jsonb('context')->nullable(); // Current conversation context
            $table->jsonb('cart')->nullable(); // Shopping cart for WhatsApp orders
            $table->string('language', 10)->default('en');
            $table->integer('message_count')->default(0);
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->integer('verification_attempts')->default(0);
            $table->jsonb('preferences')->nullable(); // User preferences
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('phone_number');
            // Note: morphs() already creates index for user_type and user_id
            $table->index('session_id');
            $table->index('status');
            $table->index('state');
            $table->index('last_activity_at');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']); // Active sessions check
            $table->index(['phone_number', 'status']); // User's active session
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};