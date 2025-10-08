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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // email, sms, push, database
            $table->string('category'); // order, payment, delivery, etc.
            $table->text('description')->nullable();
            
            // Template content
            $table->string('subject')->nullable(); // for email
            $table->longText('content'); // main template content
            $table->longText('html_content')->nullable(); // HTML version for email
            $table->longText('text_content')->nullable(); // Plain text version
            
            // Template variables
            $table->json('variables')->nullable(); // available template variables
            $table->json('default_values')->nullable(); // default values for variables
            $table->json('validation_rules')->nullable(); // validation for variables
            
            // Styling and layout
            $table->string('layout')->nullable(); // layout file to use
            $table->json('styles')->nullable(); // custom styles
            $table->string('icon')->nullable(); // notification icon
            $table->string('color')->nullable(); // notification color
            
            // Localization
            $table->string('locale')->default('en');
            $table->uuid('parent_template_id')->nullable(); // for translations
            
            // Usage and versioning
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // system templates can't be deleted
            $table->string('version')->default('1.0');
            $table->uuid('created_by')->nullable(); // admin who created it
            
            // Statistics
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'category']);
            $table->index(['is_active', 'is_system']);
            $table->index('parent_template_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};