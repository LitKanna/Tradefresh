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
        // Security audit logs table
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->boolean('compliance_relevant')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('risk_level');
        });
        
        // Compliance checks table
        Schema::create('compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->json('checks');
            $table->string('overall_status');
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamps();
            
            $table->index('overall_status');
        });
        
        // Access controls table
        Schema::create('access_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('resource');
            $table->string('action');
            $table->boolean('allowed');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'allowed']);
            $table->index('resource');
        });
        
        // Analytics views tracking
        Schema::create('analytics_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('view_type');
            $table->json('filters')->nullable();
            $table->json('data_accessed')->nullable();
            $table->integer('response_time')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('view_type');
        });
        
        // Report schedules table
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->time('time');
            $table->json('recipients');
            $table->string('format')->default('pdf');
            $table->json('filters')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('next_run')->nullable();
            $table->timestamps();
            
            $table->index(['active', 'next_run']);
        });
        
        // Saved reports table
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('type');
            $table->json('configuration');
            $table->json('data')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
        });
        
        // Failed login attempts tracking
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
            
            $table->index(['email', 'created_at']);
            $table->index('ip_address');
        });
        
        // Query performance log
        Schema::create('query_log', function (Blueprint $table) {
            $table->id();
            $table->text('query');
            $table->float('execution_time');
            $table->string('query_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('execution_time');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('query_log');
        Schema::dropIfExists('failed_login_attempts');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('analytics_views');
        Schema::dropIfExists('access_controls');
        Schema::dropIfExists('compliance_checks');
        Schema::dropIfExists('security_audit_logs');
    }
};
