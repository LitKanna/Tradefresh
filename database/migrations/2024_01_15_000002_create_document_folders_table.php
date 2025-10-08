<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('color')->default('#3B82F6');
            $table->string('icon')->default('folder');
            
            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->string('path')->nullable(); // Full path for quick lookups
            $table->integer('level')->default(0);
            
            // Ownership and access
            $table->morphs('owner'); // buyer_id, vendor_id, admin_id
            $table->foreignId('created_by')->constrained('users');
            
            // Settings
            $table->enum('visibility', ['private', 'shared', 'public'])->default('private');
            $table->boolean('is_system_folder')->default(false);
            $table->json('permissions')->nullable();
            $table->json('auto_organize_rules')->nullable(); // Rules for automatic document organization
            
            $table->timestamps();
            
            // Indexes
            $table->index(['owner_type', 'owner_id']);
            $table->index(['parent_id', 'sort_order']);
            $table->index('is_system_folder');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_folders');
    }
};