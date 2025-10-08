<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeatureVotesTable extends Migration
{
    public function up()
    {
        Schema::create('feature_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_request_id')->constrained('feature_requests');
            $table->foreignId('user_id')->constrained();
            $table->integer('vote')->default(1); // 1 for upvote, -1 for downvote
            $table->timestamps();
            $table->unique(['feature_request_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('feature_votes');
    }
}