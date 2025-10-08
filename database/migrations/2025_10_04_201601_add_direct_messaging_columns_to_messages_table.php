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
        Schema::table('messages', function (Blueprint $table) {
            // Make conversation_id nullable for direct messages
            $table->unsignedBigInteger('conversation_id')->nullable()->change();

            // Add direct messaging columns
            $table->enum('recipient_type', ['buyer', 'vendor', 'admin'])->nullable()->after('sender_id');
            $table->unsignedBigInteger('recipient_id')->nullable()->after('recipient_type');
            $table->text('message')->nullable()->after('content'); // Alias for content
            $table->boolean('is_read')->default(false)->after('read_by');
            $table->unsignedBigInteger('quote_id')->nullable()->after('conversation_id');

            // Add indexes
            $table->index(['recipient_type', 'recipient_id']);
            $table->index('quote_id');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['recipient_type', 'recipient_id']);
            $table->dropIndex(['quote_id']);
            $table->dropIndex(['is_read']);
            $table->dropColumn(['recipient_type', 'recipient_id', 'message', 'is_read', 'quote_id']);
        });
    }
};
