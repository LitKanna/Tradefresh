<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->unique();
            $table->morphs('notifiable');
            $table->string('type');
            $table->string('channel');
            $table->json('data');
            $table->string('status')->default('pending'); // pending, sent, failed, read
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
            $table->index('type');
            $table->index('channel');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_history');
    }
};