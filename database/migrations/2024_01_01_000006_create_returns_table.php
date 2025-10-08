<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', [
                'requested', 'approved', 'rejected', 'shipped', 
                'received', 'processing', 'completed', 'cancelled'
            ])->default('requested');
            $table->enum('type', ['return', 'exchange', 'refund']);
            $table->enum('reason', [
                'defective', 'wrong_item', 'not_as_described', 
                'damaged', 'not_needed', 'other'
            ]);
            $table->text('reason_details');
            $table->json('items'); // Items being returned with quantities
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_method')->nullable();
            $table->enum('refund_status', ['pending', 'processed', 'failed'])->nullable();
            $table->string('return_shipping_method')->nullable();
            $table->string('return_tracking_number')->nullable();
            $table->json('images')->nullable(); // Photos of damaged/defective items
            $table->text('vendor_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('returns');
    }
};