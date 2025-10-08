<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->json('accessibility_settings')->nullable();
            $table->boolean('high_contrast_mode')->default(false);
            $table->boolean('screen_reader_mode')->default(false);
            $table->string('font_size')->default('medium'); // small, medium, large, extra-large
            $table->boolean('keyboard_navigation')->default(false);
            $table->boolean('reduced_motion')->default(false);
            $table->json('notification_preferences')->nullable();
            $table->json('support_preferences')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
}