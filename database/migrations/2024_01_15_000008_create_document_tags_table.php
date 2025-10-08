<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->default('#6B7280');
            $table->string('icon')->nullable();
            
            // Organization
            $table->string('category')->nullable();
            $table->morphs('owner'); // buyer_id, vendor_id, admin_id
            $table->boolean('is_system_tag')->default(false);
            $table->boolean('is_public')->default(false);
            
            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index(['owner_type', 'owner_id']);
            $table->index(['is_system_tag', 'is_public']);
            $table->index('usage_count');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_tags');
    }
};