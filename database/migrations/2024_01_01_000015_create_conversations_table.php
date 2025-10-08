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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique(); // UUID for conversation
            $table->enum('type', ['direct', 'order', 'rfq', 'support', 'broadcast'])->default('direct');
            $table->nullableMorphs('context'); // Related entity (order, rfq, etc.)
            $table->string('subject')->nullable();
            $table->enum('status', ['active', 'archived', 'closed'])->default('active');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->jsonb('participants'); // Array of participant objects with type and id
            $table->integer('message_count')->default(0);
            $table->integer('unread_count')->default(0);
            $table->jsonb('participant_unread_counts')->nullable(); // Unread count per participant
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('last_message_by')->nullable();
            $table->string('last_message_by_type')->nullable();
            $table->text('last_message_preview')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->jsonb('tags')->nullable(); // Conversation tags
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('uuid');
            $table->index('type');
            // Note: nullableMorphs() already creates index for context_type and context_id
            $table->index('status');
            $table->index('priority');
            $table->index('last_message_at');
            $table->index(['status', 'last_message_at']); // Active conversations sorted by recent
            $table->index(['type', 'status', 'priority']); // Support conversations by priority
            // GIN indexes for JSONB
            $table->index('participants', null, 'gin');
            $table->index('tags', null, 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};