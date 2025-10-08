<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('buyer_id')->constrained('users');
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->json('specifications')->nullable();
            $table->integer('quantity_needed');
            $table->string('unit')->default('unit');
            $table->date('needed_by')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->string('delivery_location')->nullable();
            $table->text('special_requirements')->nullable();
            $table->json('invited_vendors')->nullable();
            $table->boolean('is_public')->default(true);
            $table->enum('status', ['draft', 'open', 'closed', 'awarded', 'cancelled']);
            $table->timestamp('closes_at')->nullable();
            $table->integer('quotes_received')->default(0);
            $table->foreignId('winning_quote_id')->nullable()->constrained('quotes');
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index('closes_at');
            $table->index('is_public');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_requests');
    }
};