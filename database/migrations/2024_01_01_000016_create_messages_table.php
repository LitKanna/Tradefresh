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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->morphs('sender'); // Polymorphic sender (buyer, vendor, admin)
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'location', 'product', 'order', 'system'])->default('text');
            $table->text('content');
            $table->jsonb('attachments')->nullable(); // Array of attachment URLs
            $table->jsonb('metadata')->nullable(); // Additional message data
            $table->jsonb('mentions')->nullable(); // Mentioned users
            $table->jsonb('reactions')->nullable(); // Message reactions
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->jsonb('read_by')->nullable(); // Array of users who read the message
            $table->jsonb('delivered_to')->nullable(); // Array of users message was delivered to
            $table->unsignedBigInteger('reply_to')->nullable(); // Reply to another message
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->string('deleted_by_type')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('reply_to')->references('id')->on('messages')->onDelete('set null');

            // Indexes
            $table->index('conversation_id');
            // Note: morphs() already creates index for sender_type and sender_id
            $table->index('type');
            $table->index('created_at');
            $table->index(['conversation_id', 'created_at']); // Messages in conversation by time
            $table->index(['conversation_id', 'is_deleted']); // Non-deleted messages
            // GIN indexes
            $table->index('read_by', null, 'gin');
            $table->index('mentions', null, 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};