<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('document_type'); // contract, invoice, receipt, compliance, template, general
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])->default('draft');
            $table->json('metadata')->nullable();
            $table->string('hash')->unique(); // File integrity check
            
            // Ownership
            $table->morphs('owner'); // buyer_id, vendor_id, admin_id
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            
            // Organization
            $table->foreignId('folder_id')->nullable()->constrained('document_folders');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            
            // Access control
            $table->enum('visibility', ['private', 'shared', 'public'])->default('private');
            $table->boolean('is_confidential')->default(false);
            
            // Versioning
            $table->integer('version')->default(1);
            $table->foreignId('parent_document_id')->nullable()->constrained('documents');
            $table->boolean('is_current_version')->default(true);
            
            // Relations
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->foreignId('contract_id')->nullable()->constrained('contracts');
            $table->foreignId('template_id')->nullable()->constrained('document_templates');
            
            // Processing
            $table->boolean('requires_signature')->default(false);
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->json('signature_data')->nullable();
            
            // Approval workflow
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // OCR and indexing
            $table->longText('extracted_text')->nullable();
            $table->json('ocr_data')->nullable();
            $table->boolean('is_searchable')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['document_type', 'status']);
            $table->index(['owner_type', 'owner_id']);
            $table->index(['created_at', 'status']);
            $table->index(['requires_approval', 'status']);
            // SQLite incompatible: // ->fullText(['title', 'description', 'extracted_text']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};