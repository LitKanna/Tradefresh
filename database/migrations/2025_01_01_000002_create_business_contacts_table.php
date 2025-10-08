<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for business contact methods.
     * Allows multiple phone numbers and contact methods per business.
     */
    public function up(): void
    {
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            
            // Contact type and value
            $table->enum('contact_type', [
                'phone_main',
                'phone_mobile',
                'phone_after_hours',
                'phone_warehouse',
                'phone_accounts',
                'fax',
                'email_main',
                'email_orders',
                'email_accounts',
                'whatsapp',
                'telegram'
            ]);
            $table->string('contact_value');
            $table->string('label')->nullable(); // Custom label like "John's Mobile"
            
            // Contact person details
            $table->string('contact_person')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('department')->nullable();
            
            // Availability
            $table->json('available_hours')->nullable(); // {"monday": {"start": "09:00", "end": "17:00"}, ...}
            $table->string('timezone')->default('Australia/Sydney');
            
            // Status and preferences
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('receives_notifications')->default(true);
            $table->boolean('receives_marketing')->default(false);
            
            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            
            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['business_id', 'contact_type']);
            $table->index(['business_id', 'is_primary']);
            $table->index('contact_type');
            $table->index('is_verified');
            $table->unique(['business_id', 'contact_type', 'contact_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_contacts');
    }
};