<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('channel'); // email, sms, push, database
            $table->string('type'); // order_confirmation, rfq_update, etc.
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable(); // channel-specific settings
            $table->timestamps();
            
            $table->unique(['notifiable_type', 'notifiable_id', 'channel', 'type']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_preferences');
    }
};