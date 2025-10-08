<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeatureRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->string('status')->default('pending'); // pending, under_review, planned, in_progress, completed, rejected
            $table->string('priority')->nullable(); // low, medium, high, critical
            $table->integer('votes_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('attachments')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('release_version')->nullable();
            $table->json('related_features')->nullable();
            $table->integer('estimated_effort')->nullable(); // in hours
            $table->json('impact_analysis')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index(['user_id', 'status']);
            // SQLite incompatible: // ->fullText(['title', 'description']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('feature_requests');
    }
}