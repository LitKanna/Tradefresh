<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('buyer_favorite_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['buyer_id', 'product_id']);
            $table->index('buyer_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('buyer_favorite_products');
    }
};