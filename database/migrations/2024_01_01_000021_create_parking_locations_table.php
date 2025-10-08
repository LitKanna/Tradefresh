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
        Schema::create('parking_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('location_code', 20)->unique();
            $table->string('building', 100);
            $table->string('floor', 20)->nullable();
            $table->string('section', 50)->nullable();
            $table->string('spot_number', 20);
            $table->enum('spot_type', ['loading', 'standard', 'disabled', 'reserved', 'temporary'])->default('standard');
            $table->text('description')->nullable();
            $table->jsonb('coordinates')->nullable(); // GPS coordinates
            $table->jsonb('operating_hours')->nullable(); // Available hours
            $table->boolean('is_primary')->default(false); // Primary parking spot
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->jsonb('access_instructions')->nullable(); // How to access the spot
            $table->jsonb('nearby_landmarks')->nullable(); // Reference points
            $table->string('qr_code')->nullable(); // QR code for quick location
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');

            // Indexes
            $table->index('vendor_id');
            $table->index('location_code');
            $table->index('building');
            $table->index('spot_type');
            $table->index('is_active');
            $table->index(['vendor_id', 'is_primary', 'is_active']); // Vendor's primary spot
            $table->index(['building', 'floor', 'section']); // Location hierarchy
            $table->index(['is_active', 'valid_from', 'valid_until']); // Valid spots
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_locations');
    }
};