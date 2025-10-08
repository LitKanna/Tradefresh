<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins');
            $table->string('title');
            $table->text('message');
            $table->string('target_audience'); // all, vendors, buyers, specific_segment
            $table->json('audience_criteria')->nullable();
            $table->json('channels'); // ['email', 'sms', 'push', 'database']
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->json('statistics')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('admin_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_messages');
    }
};