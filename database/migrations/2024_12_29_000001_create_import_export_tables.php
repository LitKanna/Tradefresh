<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Export History
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('type');
            $table->string('format');
            $table->string('file_path');
            $table->integer('records_count')->default(0);
            $table->json('options')->nullable();
            $table->json('filters')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('exported_at');
            $table->timestamps();
            
            $table->index(['buyer_id', 'type']);
            $table->index('exported_at');
        });

        // Import History
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('import_id')->unique();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('status');
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->json('errors')->nullable();
            $table->json('options')->nullable();
            $table->json('mapping')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
            
            $table->index(['buyer_id', 'type']);
            $table->index('import_id');
            $table->index('imported_at');
        });

        // Import Templates
        Schema::create('import_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->json('mapping');
            $table->json('options')->nullable();
            $table->json('transformations')->nullable();
            $table->json('validations')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['buyer_id', 'type']);
            $table->index('is_public');
        });

        // Export Templates
        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('format');
            $table->text('description')->nullable();
            $table->json('configuration');
            $table->json('fields')->nullable();
            $table->json('filters')->nullable();
            $table->json('transformations')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['buyer_id', 'type']);
            $table->index('is_public');
        });

        // Scheduled Exports
        Schema::create('scheduled_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('format');
            $table->string('frequency'); // daily, weekly, monthly, quarterly
            $table->json('options')->nullable();
            $table->json('filters')->nullable();
            $table->json('recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->json('last_run_result')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'is_active']);
            $table->index('next_run_at');
        });

        // Data Transformation Rules
        Schema::create('data_transformation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // import or export
            $table->string('data_type'); // products, orders, etc.
            $table->json('rules');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            $table->index(['buyer_id', 'type', 'data_type']);
        });

        // Field Mappings
        Schema::create('field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type'); // csv, excel, api, etc.
            $table->string('target_type'); // products, orders, etc.
            $table->json('mappings');
            $table->boolean('auto_detect')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['buyer_id', 'source_type', 'target_type']);
        });

        // Import/Export Audit Trail
        Schema::create('import_export_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditable');
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('action'); // import, export, rollback, etc.
            $table->string('type'); // products, orders, etc.
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });

        // Bulk Upload Queue
        Schema::create('bulk_upload_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->string('status'); // pending, processing, completed, failed
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->json('options')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index('created_at');
        });

        // Export Queue for large exports
        Schema::create('export_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->string('type');
            $table->string('format');
            $table->string('status'); // pending, processing, completed, failed
            $table->json('options')->nullable();
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('progress')->default(0);
            $table->integer('total_records')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_queues');
        Schema::dropIfExists('bulk_upload_queues');
        Schema::dropIfExists('import_export_audit_trails');
        Schema::dropIfExists('field_mappings');
        Schema::dropIfExists('data_transformation_rules');
        Schema::dropIfExists('scheduled_exports');
        Schema::dropIfExists('export_templates');
        Schema::dropIfExists('import_templates');
        Schema::dropIfExists('import_histories');
        Schema::dropIfExists('export_histories');
    }
};