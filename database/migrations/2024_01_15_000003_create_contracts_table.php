<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['purchase_agreement', 'service_contract', 'nda', 'master_agreement', 'amendment', 'other']);
            $table->enum('status', ['draft', 'pending_review', 'under_negotiation', 'pending_signatures', 'active', 'completed', 'terminated', 'expired'])->default('draft');
            
            // Parties
            $table->foreignId('buyer_id')->constrained('buyers');
            $table->foreignId('vendor_id')->constrained('vendors');
            
            // Contract details
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->boolean('auto_renewal')->default(false);
            $table->integer('renewal_period_months')->nullable();
            
            // Terms and conditions
            $table->json('payment_terms')->nullable();
            $table->json('delivery_terms')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->json('penalties_clauses')->nullable();
            $table->longText('terms_and_conditions')->nullable();
            
            // Signatures
            $table->boolean('requires_buyer_signature')->default(true);
            $table->boolean('requires_vendor_signature')->default(true);
            $table->boolean('buyer_signed')->default(false);
            $table->boolean('vendor_signed')->default(false);
            $table->timestamp('buyer_signed_at')->nullable();
            $table->timestamp('vendor_signed_at')->nullable();
            $table->json('buyer_signature_data')->nullable();
            $table->json('vendor_signature_data')->nullable();
            
            // Workflow
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->json('approval_workflow')->nullable();
            $table->json('notification_settings')->nullable();
            
            // Templates and automation
            $table->foreignId('template_id')->nullable()->constrained('contract_templates');
            $table->json('template_variables')->nullable();
            
            // Compliance and audit
            $table->json('compliance_requirements')->nullable();
            $table->boolean('requires_legal_review')->default(false);
            $table->timestamp('legal_review_completed_at')->nullable();
            $table->foreignId('legal_reviewer_id')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'end_date']);
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};