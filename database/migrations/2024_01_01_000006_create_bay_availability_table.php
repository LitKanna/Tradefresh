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
        Schema::create('bay_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bay_id')->constrained('pickup_bays')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked', 'blocked', 'maintenance'])->default('available');
            $table->foreignId('booking_id')->nullable()->constrained('pickup_bookings')->onDelete('set null');
            $table->string('blocked_reason')->nullable();
            $table->timestamps();
            
            $table->unique(['bay_id', 'date', 'start_time']);
            $table->index(['bay_id', 'date', 'status']);
            $table->index(['date', 'status']);
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bay_availability');
    }
};