<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTroubleshootingGuidesTable extends Migration
{
    public function up()
    {
        Schema::create('troubleshooting_guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('problem_description');
            $table->json('symptoms')->nullable();
            $table->json('causes')->nullable();
            $table->json('solutions'); // step-by-step solutions
            $table->string('category');
            $table->json('tags')->nullable();
            $table->string('difficulty_level')->nullable();
            $table->integer('estimated_time')->nullable(); // in minutes
            $table->json('required_tools')->nullable();
            $table->json('warnings')->nullable();
            $table->integer('success_rate')->nullable(); // percentage
            $table->integer('uses_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->json('related_articles')->nullable();
            $table->json('diagnostic_steps')->nullable();
            $table->boolean('is_automated')->default(false);
            $table->json('automation_script')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
            $table->index(['category', 'status']);
            // SQLite incompatible: // ->fullText(['title', 'problem_description']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('troubleshooting_guides');
    }
}