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
        Schema::create('cart_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('buyers')->onDelete('cascade');
            $table->foreignId('shared_with')->nullable()->constrained('buyers')->onDelete('cascade');
            $table->string('share_token')->unique();
            $table->string('share_email')->nullable();
            $table->enum('permission', ['view', 'edit', 'approve']);
            $table->text('message')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired']);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['cart_id', 'shared_with']);
            $table->index('share_token');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_shares');
    }
};