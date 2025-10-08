<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('document_tags')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['document_id', 'tag_id']);
            
            // Indexes
            $table->index(['document_id']);
            $table->index(['tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_tag_assignments');
    }
};