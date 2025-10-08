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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['payment_summary', 'tax_report', 'spending_analysis', 'payment_history', 'account_activity', 'credit_utilization']);
            $table->enum('status', ['generating', 'completed', 'failed', 'archived']);
            $table->enum('format', ['pdf', 'excel', 'csv', 'json']);
            
            // Report parameters
            $table->date('period_start');
            $table->date('period_end');
            $table->json('filters')->nullable(); // Additional filters applied
            $table->json('parameters')->nullable(); // Report-specific parameters
            
            // Generated report info
            $table->string('report_name');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_hash')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Report data summary
            $table->json('summary_data')->nullable(); // Key metrics and totals
            $table->integer('total_records')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            
            // Access tracking
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            
            // Scheduling (for recurring reports)
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // monthly, quarterly, yearly
            $table->timestamp('next_generation_at')->nullable();
            $table->boolean('auto_email')->default(false);
            $table->string('email_recipients')->nullable();
            
            // Processing info
            $table->integer('processing_time_seconds')->nullable();
            $table->text('generation_notes')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};