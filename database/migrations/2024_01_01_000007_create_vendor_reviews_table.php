<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('rating', 3, 2);
            $table->decimal('quality_rating', 3, 2)->nullable();
            $table->decimal('delivery_rating', 3, 2)->nullable();
            $table->decimal('service_rating', 3, 2)->nullable();
            $table->decimal('price_rating', 3, 2)->nullable();
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->boolean('verified_purchase')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_response_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();
            
            $table->index(['vendor_id', 'rating']);
            $table->index(['user_id', 'vendor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_reviews');
    }
};