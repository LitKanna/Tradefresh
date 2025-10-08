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
        // 1. Communication Channels
        Schema::create('communication_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // direct, group, support, forum, team
            $table->text('description')->nullable();
            $table->string('privacy')->default('private'); // private, public, restricted
            $table->unsignedBigInteger('created_by');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['type', 'privacy', 'is_active']);
        });

        // 2. Channel Participants
        Schema::create('channel_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member'); // admin, moderator, member, guest
            $table->json('permissions')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('communication_channels')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['channel_id', 'user_id']);
            $table->index(['user_id', 'left_at']);
        });

        // 3. Enhanced Messages System (extends existing)
        Schema::create('communication_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type')->default('user');
            $table->text('content');
            $table->string('message_type')->default('text'); // text, image, file, video, audio, system, template
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->json('reactions')->nullable(); // emoji reactions
            $table->string('source')->default('web'); // web, mobile, whatsapp, email, api
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->json('read_by')->nullable(); // array of user IDs and timestamps
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->json('formatting')->nullable(); // rich text formatting
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('communication_channels')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users');
            $table->foreign('reply_to_id')->references('id')->on('communication_messages')->onDelete('set null');
            
            $table->index(['channel_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['message_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        // 4. Live Chat Sessions
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('support_agent_id')->nullable();
            $table->string('type'); // buyer-vendor, buyer-support, vendor-support
            $table->string('status')->default('active'); // active, waiting, assigned, closed, transferred
            $table->string('priority')->default('normal');
            $table->text('subject')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->float('satisfaction_rating', 2, 1)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->foreign('buyer_id')->references('id')->on('buyers');
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('support_agent_id')->references('id')->on('admins');
            $table->index(['status', 'started_at']);
            $table->index(['type', 'status']);
        });

        // 5. Video Call Integration
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->unique();
            $table->unsignedBigInteger('initiated_by');
            $table->json('participants'); // array of participant details
            $table->string('type')->default('meeting'); // meeting, support, vendor-buyer
            $table->string('status')->default('scheduled'); // scheduled, active, ended, cancelled
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('meeting_password')->nullable();
            $table->json('recording_urls')->nullable();
            $table->json('settings')->nullable(); // recording, chat, etc.
            $table->timestamps();

            $table->foreign('initiated_by')->references('id')->on('users');
            $table->index(['status', 'scheduled_at']);
            $table->index(['initiated_by', 'status']);
        });

        // 6. Discussion Forums
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->unsignedBigInteger('created_by');
            $table->string('status')->default('published'); // draft, published, closed, archived
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->timestamp('last_reply_at')->nullable();
            $table->unsignedBigInteger('last_reply_by')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('forum_categories');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('last_reply_by')->references('id')->on('users');
            $table->index(['category_id', 'is_pinned', 'last_reply_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('topic_id');
            $table->text('content');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->boolean('is_solution')->default(false);
            $table->integer('votes_count')->default(0);
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->foreign('topic_id')->references('id')->on('forum_topics')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('reply_to_id')->references('id')->on('forum_replies')->onDelete('cascade');
            $table->index(['topic_id', 'created_at']);
        });

        // 7. Document Sharing
        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('visibility')->default('private'); // private, team, public, specific_users
            $table->json('permissions')->nullable(); // read, write, comment permissions
            $table->json('shared_with')->nullable(); // user IDs or team IDs
            $table->string('folder')->nullable();
            $table->json('tags')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('version', 10)->default('1.0');
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->index(['uploaded_by', 'visibility']);
            $table->index(['folder', 'created_at']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('version');
            $table->string('file_path');
            $table->unsignedBigInteger('uploaded_by');
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('shared_documents')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->index(['document_id', 'created_at']);
        });

        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->text('comment');
            $table->unsignedBigInteger('created_by');
            $table->json('position')->nullable(); // for PDF annotations
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('shared_documents')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['document_id', 'created_at']);
        });

        // 8. Internal Team Messaging
        Schema::create('team_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users');
            $table->index(['is_active', 'created_at']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member'); // admin, member, guest
            $table->json('permissions')->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('team_workspaces')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['workspace_id', 'user_id']);
        });

        // 9. Chatbots and Automated Messaging
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // support, sales, general
            $table->json('configuration');
            $table->boolean('is_active')->default(true);
            $table->json('triggers'); // conditions to activate bot
            $table->json('responses'); // predefined responses
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatbot_id');
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->json('context'); // conversation context
            $table->string('status')->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('escalated_to_human')->default(false);
            $table->timestamps();

            $table->foreign('chatbot_id')->references('id')->on('chatbots');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'status']);
        });

        // 10. Message Templates
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // greeting, follow_up, support, etc.
            $table->text('subject')->nullable();
            $table->text('content');
            $table->json('variables')->nullable(); // template variables
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['category', 'is_public']);
        });

        // 11. Quick Replies
        Schema::create('quick_replies', function (Blueprint $table) {
            $table->id();
            $table->string('trigger');
            $table->text('response');
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['trigger', 'is_active']);
        });

        // 12. Communication Analytics
        Schema::create('communication_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric_type');
            $table->string('channel_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('value');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['date', 'metric_type', 'channel_type', 'user_id']);
            $table->index(['date', 'metric_type']);
        });

        // 13. External Platform Integration
        Schema::create('external_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // email, slack, teams, whatsapp
            $table->string('integration_type'); // webhook, api, oauth
            $table->json('configuration');
            $table->json('credentials'); // encrypted credentials
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_status')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });

        Schema::create('external_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('external_id');
            $table->unsignedBigInteger('internal_message_id')->nullable();
            $table->string('direction'); // inbound, outbound
            $table->json('raw_data');
            $table->string('status'); // pending, processed, failed
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('integration_id')->references('id')->on('external_integrations');
            $table->foreign('internal_message_id')->references('id')->on('communication_messages');
            $table->unique(['integration_id', 'external_id']);
            $table->index(['status', 'received_at']);
        });

        // 14. Communication Preferences
        Schema::create('user_communication_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('channel'); // email, sms, push, whatsapp
            $table->string('event_type'); // message, mention, document_share
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['user_id', 'channel', 'event_type']);
        });

        // 15. Message Reactions and Interactions
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('emoji');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('communication_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['message_id', 'user_id', 'emoji']);
        });

        // 16. Communication History Archive
        Schema::create('communication_archives', function (Blueprint $table) {
            $table->id();
            $table->string('archive_type'); // conversation, channel, forum_topic
            $table->unsignedBigInteger('original_id');
            $table->json('archived_data');
            $table->unsignedBigInteger('archived_by');
            $table->timestamp('archived_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('archived_by')->references('id')->on('users');
            $table->index(['archive_type', 'original_id']);
            $table->index(['archived_at']);
        });

        // Add indexes for performance (skip full-text for SQLite compatibility)
        if (config('database.default') !== 'sqlite') {
            Schema::table('communication_messages', function (Blueprint $table) {
                // SQLite incompatible: // ->fullText(['content']); // Full-text search
            });

            Schema::table('forum_topics', function (Blueprint $table) {
                // SQLite incompatible: // ->fullText(['title', 'content']);
            });

            Schema::table('forum_replies', function (Blueprint $table) {
                // SQLite incompatible: // ->fullText(['content']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_archives');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('user_communication_preferences');
        Schema::dropIfExists('external_messages');
        Schema::dropIfExists('external_integrations');
        Schema::dropIfExists('communication_analytics');
        Schema::dropIfExists('quick_replies');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('chatbots');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('team_workspaces');
        Schema::dropIfExists('document_comments');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('shared_documents');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forum_categories');
        Schema::dropIfExists('video_calls');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('channel_participants');
        Schema::dropIfExists('communication_channels');
    }
};