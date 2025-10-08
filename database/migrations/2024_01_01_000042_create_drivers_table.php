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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('license_number')->unique();
            $table->string('vehicle_type');
            $table->string('vehicle_number');
            $table->enum('status', ['available', 'busy', 'offline'])->default('offline');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->integer('completed_deliveries')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->json('working_hours')->nullable();
            $table->json('zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('status');
            $table->index('is_active');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};