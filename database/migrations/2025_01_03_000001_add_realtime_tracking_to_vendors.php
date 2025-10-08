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
        Schema::table('vendors', function (Blueprint $table) {
            // Real-time tracking fields
            $table->boolean('is_online')->default(false)->after('verification_status');
            $table->timestamp('last_activity_at')->nullable()->after('is_online');
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_activity_at');
            $table->string('current_session_id', 100)->nullable()->after('last_heartbeat_at');
            $table->integer('active_sessions_count')->default(0)->after('current_session_id');
            
            // Performance indexes
            $table->index('is_online');
            $table->index('last_activity_at');
            $table->index('last_heartbeat_at');
            $table->index(['is_online', 'status']); // Composite index for active online vendors
            $table->index(['last_heartbeat_at', 'is_online']); // For timeout detection
        });
        
        // Create vendor activity tracking table for detailed analytics
        Schema::create('vendor_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['login', 'logout', 'timeout', 'heartbeat', 'activity']);
            $table->string('session_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional event data
            $table->timestamp('created_at');
            
            $table->index(['vendor_id', 'created_at']);
            $table->index('event_type');
            $table->index('session_id');
        });
        
        // Create real-time metrics cache table
        Schema::create('realtime_vendor_metrics', function (Blueprint $table) {
            $table->id();
            $table->integer('total_online')->default(0);
            $table->integer('total_active')->default(0);
            $table->json('category_breakdown')->nullable(); // Online count per category
            $table->json('location_breakdown')->nullable(); // Online count per state
            $table->timestamp('last_calculated_at');
            $table->timestamps();
            
            $table->index('last_calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['is_online', 'status']);
            $table->dropIndex(['last_heartbeat_at', 'is_online']);
            $table->dropIndex('is_online');
            $table->dropIndex('last_activity_at');
            $table->dropIndex('last_heartbeat_at');
            
            $table->dropColumn([
                'is_online',
                'last_activity_at',
                'last_heartbeat_at',
                'current_session_id',
                'active_sessions_count'
            ]);
        });
        
        Schema::dropIfExists('vendor_activity_logs');
        Schema::dropIfExists('realtime_vendor_metrics');
    }
};