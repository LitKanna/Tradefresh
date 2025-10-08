<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOnboardingProgressTable extends Migration
{
    public function up()
    {
        Schema::create('user_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('step_id')->constrained('onboarding_steps');
            $table->string('status')->default('pending'); // pending, in_progress, completed, skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent')->nullable(); // in seconds
            $table->json('interaction_data')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'step_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
}