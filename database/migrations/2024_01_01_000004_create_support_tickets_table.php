<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('subject');
            $table->text('description');
            $table->string('category'); // technical, billing, account, general
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, in_progress, waiting_customer, resolved, closed
            $table->json('tags')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('satisfaction_rating')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->json('related_orders')->nullable();
            $table->json('internal_notes')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->string('channel')->default('web'); // web, email, chat, phone
            $table->timestamps();
            $table->index(['status', 'priority', 'assigned_to']);
            $table->index(['user_id', 'status']);
            // SQLite incompatible: // ->fullText(['subject', 'description']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('support_tickets');
    }
}