<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['purchase_agreement', 'service_contract', 'nda', 'master_agreement', 'amendment', 'other']);
            $table->string('category')->nullable();
            
            // Template content
            $table->longText('content'); // HTML or rich text content with placeholders
            $table->json('variables')->nullable(); // Template variables and their types
            $table->json('clauses')->nullable(); // Standard clauses that can be included
            $table->json('terms_template')->nullable(); // Template for terms and conditions
            
            // Configuration
            $table->json('default_values')->nullable(); // Default values for variables
            $table->json('validation_rules')->nullable(); // Validation rules for variables
            $table->boolean('requires_legal_review')->default(false);
            $table->json('approval_workflow')->nullable();
            
            // Access and sharing
            $table->morphs('owner'); // buyer_id, vendor_id, admin_id
            $table->enum('visibility', ['private', 'organization', 'public'])->default('private');
            $table->boolean('is_system_template')->default(false);
            
            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Versioning
            $table->string('version')->default('1.0');
            $table->foreignId('parent_template_id')->nullable()->constrained('contract_templates');
            $table->boolean('is_active')->default(true);
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['owner_type', 'owner_id', 'visibility']);
            $table->index(['is_system_template', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_templates');
    }
};