<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingStepsTable extends Migration
{
    public function up()
    {
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('type')->default('tutorial'); // tutorial, tour, checklist, video
            $table->json('content'); // step content
            $table->integer('order')->default(0);
            $table->string('target_element')->nullable(); // CSS selector for tours
            $table->string('position')->nullable(); // top, bottom, left, right
            $table->json('triggers')->nullable(); // when to show
            $table->json('conditions')->nullable(); // conditions to show
            $table->string('category')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('next_steps')->nullable();
            $table->integer('estimated_time')->nullable(); // in seconds
            $table->timestamps();
            $table->index(['type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding_steps');
    }
}