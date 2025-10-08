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
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_lead_id')->constrained('buyer_leads')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->text('note');
            $table->enum('type', ['NOTE', 'CALL', 'EMAIL', 'MEETING', 'FOLLOW_UP']);
            $table->date('follow_up_date')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index(['buyer_lead_id', 'created_at']);
            $table->index(['follow_up_date', 'completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
    }
};