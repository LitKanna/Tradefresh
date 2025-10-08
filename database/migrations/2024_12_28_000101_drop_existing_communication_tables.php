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
        // Drop existing communication tables if they exist
        $tables = [
            'communication_archives',
            'message_reactions',
            'user_communication_preferences',
            'external_messages',
            'external_integrations',
            'communication_analytics',
            'quick_replies',
            'message_templates',
            'chatbot_conversations',
            'chatbots',
            'team_members',
            'team_workspaces',
            'document_comments',
            'document_versions',
            'shared_documents',
            'forum_replies',
            'forum_topics',
            'forum_categories',
            'video_calls',
            'chat_sessions',
            'communication_messages',
            'channel_participants',
            'communication_channels'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a cleanup migration - no rollback needed
    }
};