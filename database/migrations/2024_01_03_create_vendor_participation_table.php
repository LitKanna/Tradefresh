<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_participation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users');
            $table->foreignId('request_id')->constrained('quote_requests');
            $table->enum('status', ['invited', 'viewed', 'interested', 'quoted', 'declined', 'won', 'lost']);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('interested_at')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason')->nullable();
            $table->integer('quotes_submitted')->default(0);
            $table->decimal('best_price', 10, 2)->nullable();
            $table->boolean('is_winner')->default(false);
            $table->json('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['vendor_id', 'request_id']);
            $table->index(['vendor_id', 'status']);
            $table->index(['request_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_participation');
    }
};