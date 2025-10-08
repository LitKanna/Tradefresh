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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->text('user_message');
            $table->text('ai_response');
            $table->json('extracted_data')->nullable();
            $table->string('conversation_state')->default('gathering_info'); // gathering_info, awaiting_quotes, presenting_quote, completed
            $table->foreignId('rfq_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
