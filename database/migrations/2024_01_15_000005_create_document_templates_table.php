<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('document_type');
            $table->string('category')->nullable();
            
            // Template content
            $table->longText('content'); // HTML or rich text content
            $table->json('variables')->nullable(); // Template variables and their types
            $table->json('fields')->nullable(); // Form fields for template
            $table->json('styles')->nullable(); // CSS styles
            $table->string('file_format')->default('pdf'); // pdf, docx, html
            
            // Configuration
            $table->boolean('requires_signature')->default(false);
            $table->json('signature_positions')->nullable(); // Coordinates for signature placement
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_workflow')->nullable();
            
            // Access and sharing
            $table->morphs('owner'); // buyer_id, vendor_id, admin_id
            $table->enum('visibility', ['private', 'organization', 'public'])->default('private');
            $table->boolean('is_system_template')->default(false);
            
            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Versioning
            $table->integer('version')->default(1);
            $table->foreignId('parent_template_id')->nullable()->constrained('document_templates');
            $table->boolean('is_active')->default(true);
            
            // Automation
            $table->json('auto_generation_rules')->nullable(); // Rules for automatic document generation
            $table->json('integration_settings')->nullable(); // Integration with external services
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['document_type', 'is_active']);
            $table->index(['owner_type', 'owner_id', 'visibility']);
            $table->index(['is_system_template', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_templates');
    }
};