<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unsubscribe_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->morphs('notifiable');
            $table->string('channel')->nullable(); // null means all channels
            $table->string('type')->nullable(); // null means all types
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('token');
        });
    }

    public function down()
    {
        Schema::dropIfExists('unsubscribe_tokens');
    }
};