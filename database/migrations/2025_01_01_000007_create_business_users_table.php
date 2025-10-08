<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for business users junction table.
     * Links buyers (users) to businesses with specific roles.
     */
    public function up(): void
    {
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('buyers')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('business_user_roles')->onDelete('restrict');
            
            // User status within the business
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('status_reason')->nullable();
            
            // Invitation and approval
            $table->string('invitation_token')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->timestamp('invitation_accepted_at')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            
            // Access control
            $table->timestamp('access_granted_at')->nullable();
            $table->timestamp('access_revoked_at')->nullable();
            $table->foreignId('access_revoked_by')->nullable()->constrained('buyers')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            
            // Temporary access
            $table->timestamp('temporary_access_from')->nullable();
            $table->timestamp('temporary_access_until')->nullable();
            
            // Department and position
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('employee_id')->nullable();
            
            // Contact preferences for this business relationship
            $table->boolean('receives_order_notifications')->default(true);
            $table->boolean('receives_invoice_notifications')->default(false);
            $table->boolean('receives_delivery_notifications')->default(true);
            $table->boolean('receives_marketing')->default(false);
            
            // Custom permissions (override role defaults)
            $table->json('custom_permissions')->nullable(); // {"can_place_orders": true, "max_order_value": 5000}
            
            // Spending limits specific to this user-business relationship
            $table->decimal('personal_order_limit', 10, 2)->nullable();
            $table->decimal('personal_daily_limit', 10, 2)->nullable();
            $table->decimal('personal_monthly_limit', 10, 2)->nullable();
            
            // Activity tracking
            $table->integer('orders_placed')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            
            // Notes
            $table->text('internal_notes')->nullable(); // Notes visible to admins only
            $table->text('user_notes')->nullable(); // Notes from the user
            
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique constraint - one user can have only one role per business
            $table->unique(['business_id', 'buyer_id'], 'unique_business_buyer');
            
            // Indexes for performance
            $table->index(['business_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index('role_id');
            $table->index('status');
            $table->index('invitation_token');
            $table->index(['business_id', 'role_id']);
            $table->index('temporary_access_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_users');
    }
};