<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users');
            $table->morphs('shared_with'); // user_id, vendor_id, buyer_id, team_id
            $table->string('shared_with_email')->nullable();
            
            // Access permissions
            $table->enum('permission_level', ['view', 'comment', 'edit', 'admin'])->default('view');
            $table->json('specific_permissions')->nullable(); // Detailed permissions
            $table->boolean('can_download')->default(true);
            $table->boolean('can_print')->default(true);
            $table->boolean('can_share')->default(false);
            
            // Access control
            $table->string('access_token')->unique()->nullable(); // For external access
            $table->boolean('requires_password')->default(false);
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Tracking
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('access_log')->nullable(); // Track access attempts
            
            // Notifications
            $table->boolean('notify_on_view')->default(false);
            $table->boolean('notify_on_download')->default(false);
            $table->boolean('notify_on_expiry')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['document_id', 'is_active']);
            $table->index(['shared_with_type', 'shared_with_id']);
            $table->index(['access_token']);
            $table->index(['expires_at', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_shares');
    }
};