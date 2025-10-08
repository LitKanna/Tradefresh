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
        // Modify the existing sessions table if it exists, or create it
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('user_type', 20)->nullable()->index(); // 'buyer', 'vendor', 'admin'
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
                
                $table->index(['user_id', 'user_type']);
            });
        } else {
            // Add user_type column if it doesn't exist
            if (!Schema::hasColumn('sessions', 'user_type')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->string('user_type', 20)->nullable()->index()->after('user_id');
                    $table->index(['user_id', 'user_type']);
                });
            }
        }
        
        // Create buyer_login_history table for tracking login/logout events
        Schema::create('buyer_login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->enum('event_type', ['login', 'logout', 'timeout', 'forced_logout']);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('location', 255)->nullable(); // City, Country from IP
            $table->boolean('remember_token_used')->default(false);
            $table->json('additional_data')->nullable(); // For any extra information
            $table->timestamp('event_time')->useCurrent();
            $table->timestamps();
            
            $table->index(['buyer_id', 'event_type']);
            $table->index('session_id');
            $table->index('event_time');
        });
        
        // Create active_buyer_sessions table for managing multiple device sessions
        Schema::create('active_buyer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('device_id')->nullable(); // Unique device identifier
            $table->string('device_name')->nullable(); // e.g., "Chrome on Windows"
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['buyer_id', 'is_active']);
            $table->index('expires_at');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_buyer_sessions');
        Schema::dropIfExists('buyer_login_history');
        
        if (Schema::hasColumn('sessions', 'user_type')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'user_type']);
                $table->dropColumn('user_type');
            });
        }
    }
};