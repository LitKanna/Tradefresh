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
        Schema::create('notification_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            
            // Team configuration
            $table->morphs('owner'); // buyer_id, vendor_id, etc.
            $table->json('settings')->default('{}'); // team notification settings
            $table->boolean('is_active')->default(true);
            
            // Team preferences inheritance
            $table->json('default_preferences')->nullable();
            $table->boolean('enforce_preferences')->default(false);
            $table->json('allowed_channels')->nullable(); // restrict channels
            $table->json('restricted_categories')->nullable(); // restrict categories
            
            // Team roles and permissions
            $table->json('roles')->nullable(); // custom roles definition
            $table->json('permissions')->nullable(); // permission matrix
            
            $table->timestamps();
            
            // Indexes
            $table->index(['owner_type', 'owner_id']);
            $table->index('is_active');
        });

        // Team members pivot table
        Schema::create('notification_team_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('team_id');
            $table->morphs('member'); // user who is a member
            $table->string('role')->default('member'); // admin, manager, member
            $table->json('permissions')->nullable(); // custom permissions
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('team_id')->references('id')->on('notification_teams')->onDelete('cascade');
            
            // Indexes
            $table->unique(['team_id', 'member_type', 'member_id']);
            $table->index(['member_type', 'member_id']);
        });

        // Team notification sharing
        Schema::create('notification_team_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->uuid('team_id');
            $table->morphs('shared_by'); // who shared it
            $table->json('shared_with')->nullable(); // specific members or roles
            $table->text('share_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('notification_id')->references('id')->on('enhanced_notifications')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('notification_teams')->onDelete('cascade');
            
            // Indexes
            $table->index(['notification_id', 'team_id']);
            $table->index(['shared_by_type', 'shared_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_team_shares');
        Schema::dropIfExists('notification_team_members');
        Schema::dropIfExists('notification_teams');
    }
};