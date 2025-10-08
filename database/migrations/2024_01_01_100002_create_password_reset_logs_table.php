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
        Schema::create('password_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45); // IPv4 or IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('action'); // 'token_generated', 'password_reset_completed', 'token_expired', etc.
            $table->json('additional_data')->nullable(); // For storing extra security context
            $table->timestamp('created_at');
            
            // Indexes for performance and security queries
            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_logs');
    }
};