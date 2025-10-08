<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->morphs('signer'); // buyer_id, vendor_id, user_id
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_title')->nullable();
            
            // Signature data
            $table->enum('signature_type', ['digital', 'electronic', 'wet']);
            $table->enum('status', ['pending', 'sent', 'viewed', 'signed', 'declined', 'expired'])->default('pending');
            $table->json('signature_data')->nullable(); // Base64 signature image or digital signature
            $table->string('signature_method')->nullable(); // DocuSign, Adobe Sign, manual, etc.
            $table->string('external_signature_id')->nullable(); // ID from external e-signature service
            
            // Verification
            $table->string('verification_code')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('geolocation')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Certificate and audit trail
            $table->text('certificate_data')->nullable();
            $table->json('audit_trail')->nullable();
            $table->text('decline_reason')->nullable();
            
            // Workflow
            $table->integer('signing_order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->json('notification_settings')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['document_id', 'status']);
            $table->index(['signer_type', 'signer_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('external_signature_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_signatures');
    }
};