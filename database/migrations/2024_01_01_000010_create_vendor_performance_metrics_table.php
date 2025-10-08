<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->integer('returned_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
            $table->decimal('defect_rate', 5, 2)->default(0);
            $table->decimal('customer_satisfaction_score', 3, 2)->default(0);
            $table->decimal('response_time_hours', 8, 2)->default(0);
            $table->integer('disputes_raised')->default(0);
            $table->integer('disputes_resolved')->default(0);
            $table->decimal('return_rate', 5, 2)->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('repeat_customers')->default(0);
            $table->timestamps();
            
            $table->unique(['vendor_id', 'metric_date']);
            $table->index('metric_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_performance_metrics');
    }
};