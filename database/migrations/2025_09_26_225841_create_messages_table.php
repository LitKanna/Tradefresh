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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type'); // 'buyer' or 'vendor'
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type'); // 'buyer' or 'vendor'
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['quote_id', 'created_at']);
            $table->index(['sender_id', 'sender_type']);
            $table->index(['recipient_id', 'recipient_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
