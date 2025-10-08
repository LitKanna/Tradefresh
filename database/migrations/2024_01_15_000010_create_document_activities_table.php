<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->morphs('actor'); // user_id, buyer_id, vendor_id
            $table->string('actor_name');
            $table->string('action'); // created, updated, viewed, downloaded, shared, signed, approved, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional context about the action
            $table->json('changes')->nullable(); // What was changed (for update actions)
            
            // Context
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('geolocation')->nullable();
            $table->string('session_id')->nullable();
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['document_id', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_activities');
    }
};