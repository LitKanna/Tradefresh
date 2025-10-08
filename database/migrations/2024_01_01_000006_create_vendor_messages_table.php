<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['user', 'vendor']);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('vendor_messages')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->enum('status', ['open', 'closed', 'pending'])->default('open');
            $table->timestamps();
            
            $table->index(['vendor_id', 'user_id']);
            $table->index(['sender_id', 'sender_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_messages');
    }
};