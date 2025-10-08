<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaqsTable extends Migration
{
    public function up()
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->foreignId('category_id')->constrained('faq_categories');
            $table->integer('order')->default(0);
            $table->integer('views')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('related_articles')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'is_active']);
            // SQLite incompatible: $table->fullText(['question', 'answer']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('faqs');
    }
}