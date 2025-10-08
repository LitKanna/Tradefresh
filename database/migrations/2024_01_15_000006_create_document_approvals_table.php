<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users');
            $table->string('approver_name');
            $table->string('approver_email');
            $table->string('approver_role')->nullable();
            
            // Approval status
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated', 'expired'])->default('pending');
            $table->integer('approval_level')->default(1); // For multi-level approvals
            $table->integer('sequence_order')->default(1); // Order in approval chain
            
            // Decision details
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('comments')->nullable();
            $table->json('approval_conditions')->nullable(); // Conditions for approval
            
            // Delegation
            $table->foreignId('delegated_to')->nullable()->constrained('users');
            $table->text('delegation_reason')->nullable();
            $table->timestamp('delegated_at')->nullable();
            
            // Workflow
            $table->boolean('is_required')->default(true);
            $table->boolean('can_delegate')->default(true);
            $table->json('notification_settings')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent')->nullable();
            
            // Audit
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['document_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['approval_level', 'sequence_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_approvals');
    }
};