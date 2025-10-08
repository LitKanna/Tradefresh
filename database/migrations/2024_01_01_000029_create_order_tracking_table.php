<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index('tracking_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_tracking');
    }
};