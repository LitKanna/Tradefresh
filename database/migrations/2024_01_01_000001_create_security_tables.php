<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSecurityTables extends Migration
{
    public function up()
    {
        // MFA Settings Table
        Schema::create('mfa_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->enum('method', ['totp', 'sms', 'email', 'backup_codes'])->default('totp');
            $table->string('secret')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('backup_email')->nullable();
            $table->json('backup_codes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'enabled']);
        });

        // Roles and Permissions
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index('name');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('resource')->nullable();
            $table->string('action')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            
            $table->index(['name', 'category']);
            $table->index(['resource', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->json('conditions')->nullable();
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamp('expires_at')->nullable();
            $table->json('scope')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id']);
            $table->index('expires_at');
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('event_type');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('severity');
        });

        // Security Policies
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type');
            $table->json('rules');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['type', 'enabled']);
            $table->index('priority');
        });

        // Data Encryption Keys
        Schema::create('encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_id')->unique();
            $table->string('algorithm');
            $table->text('public_key')->nullable();
            $table->text('encrypted_private_key');
            $table->string('purpose');
            $table->enum('status', ['active', 'rotated', 'revoked'])->default('active');
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['key_id', 'status']);
            $table->index('purpose');
        });

        // GDPR Compliance
        Schema::create('data_processing_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('purpose');
            $table->text('description');
            $table->boolean('granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'purpose']);
            $table->index('granted');
        });

        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('request_type'); // export, deletion, rectification
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('reason')->nullable();
            $table->json('scope')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->string('download_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('request_type');
        });

        // Security Incidents
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_id')->unique();
            $table->string('type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['detected', 'investigating', 'contained', 'resolved', 'closed']);
            $table->text('description');
            $table->json('affected_resources')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('detected_at');
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->json('actions_taken')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['severity', 'status']);
            $table->index('incident_id');
            $table->index('detected_at');
        });

        // Vulnerability Scans
        Schema::create('vulnerability_scans', function (Blueprint $table) {
            $table->id();
            $table->string('scan_id')->unique();
            $table->string('scan_type');
            $table->string('target');
            $table->enum('status', ['scheduled', 'running', 'completed', 'failed'])->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('vulnerabilities_found')->default(0);
            $table->integer('critical_count')->default(0);
            $table->integer('high_count')->default(0);
            $table->integer('medium_count')->default(0);
            $table->integer('low_count')->default(0);
            $table->json('results')->nullable();
            $table->foreignId('initiated_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['scan_type', 'status']);
            $table->index('scan_id');
        });

        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('vulnerability_scans')->onDelete('cascade');
            $table->string('vulnerability_id');
            $table->string('type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('affected_component');
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->string('cve_id')->nullable();
            $table->decimal('cvss_score', 3, 1)->nullable();
            $table->enum('status', ['open', 'mitigating', 'resolved', 'accepted'])->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['severity', 'status']);
            $table->index('vulnerability_id');
        });

        // Compliance Reports
        Schema::create('compliance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id')->unique();
            $table->string('framework'); // GDPR, ISO27001, SOC2, etc
            $table->string('period');
            $table->enum('status', ['draft', 'review', 'approved', 'submitted'])->default('draft');
            $table->decimal('compliance_score', 5, 2)->nullable();
            $table->integer('controls_total')->default(0);
            $table->integer('controls_passed')->default(0);
            $table->integer('controls_failed')->default(0);
            $table->json('findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            
            $table->index(['framework', 'status']);
            $table->index('report_id');
        });

        // Secure Files
        Schema::create('secure_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_id')->unique();
            $table->string('original_name');
            $table->string('encrypted_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('hash');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('encryption_key_id');
            $table->json('access_control')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('file_id');
            $table->index('uploaded_by');
            $table->index('expires_at');
        });

        // Security Training
        Schema::create('security_training_modules', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->integer('duration_minutes');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->json('content');
            $table->json('quiz_questions')->nullable();
            $table->integer('passing_score')->default(70);
            $table->boolean('is_mandatory')->default(false);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['category', 'is_mandatory']);
            $table->index('module_code');
        });

        Schema::create('security_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained('security_training_modules')->onDelete('cascade');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed'])->default('not_started');
            $table->integer('progress_percentage')->default(0);
            $table->integer('quiz_score')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->string('certificate_id')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'module_id']);
            $table->index('status');
        });

        // Session Management
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_id')->nullable();
            $table->json('location')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_activity');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('session_id');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('security_training_progress');
        Schema::dropIfExists('security_training_modules');
        Schema::dropIfExists('secure_files');
        Schema::dropIfExists('compliance_reports');
        Schema::dropIfExists('vulnerabilities');
        Schema::dropIfExists('vulnerability_scans');
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('data_export_requests');
        Schema::dropIfExists('data_processing_consents');
        Schema::dropIfExists('encryption_keys');
        Schema::dropIfExists('security_policies');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('mfa_settings');
    }
}