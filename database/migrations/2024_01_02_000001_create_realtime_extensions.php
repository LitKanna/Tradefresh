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
        // 1. Vendor Online Status Tracking
        Schema::create('vendor_online_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('socket_id', 100)->nullable(); // WebSocket connection ID
            $table->json('available_products')->nullable(); // Product IDs they can fulfill
            $table->json('active_conversations')->nullable(); // Currently engaged conversations
            $table->timestamps();

            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            
            // Indexes
            $table->index('vendor_id');
            $table->index('is_online');
            $table->index(['is_online', 'last_seen_at']);
            $table->index('socket_id');
        });

        // 2. Real-time WebSocket Events Log
        Schema::create('websocket_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50); // 'message_sent', 'quote_received', 'vendor_online', etc.
            $table->morphs('user'); // Who triggered the event (buyer/vendor/admin)
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->json('payload'); // Event data
            $table->json('recipients')->nullable(); // Who should receive this event
            $table->boolean('is_broadcast')->default(false); // Broadcast to all or specific users
            $table->timestamp('processed_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');

            // Indexes
            $table->index('event_type');
            $table->index('status');
            $table->index(['status', 'created_at']);
            $table->index('conversation_id');
            $table->index('is_broadcast');
        });

        // 3. Enhanced Quote Media Attachments
        Schema::create('quote_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->string('type', 20); // 'image', 'video', 'document'
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('storage_path');
            $table->string('public_url')->nullable();
            $table->json('metadata')->nullable(); // Image dimensions, video duration, etc.
            $table->string('thumbnail_url')->nullable(); // For images/videos
            $table->boolean('is_processed')->default(false); // For video encoding/image optimization
            $table->timestamps();

            // Foreign keys
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');

            // Indexes
            $table->index('quote_id');
            $table->index('type');
            $table->index(['quote_id', 'type']);
        });

        // 4. Real-time Product Availability
        Schema::create('product_availability_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('available_quantity');
            $table->decimal('current_price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->json('quality_details')->nullable(); // Freshness, grade, etc.
            $table->timestamp('updated_at');
            $table->timestamp('created_at');

            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Indexes
            $table->unique(['vendor_id', 'product_id']); // One record per vendor-product
            $table->index('is_available');
            $table->index(['product_id', 'is_available']);
            $table->index(['vendor_id', 'is_available']);
            $table->index('updated_at');
        });

        // 5. Smart Vendor Matching for RFQs
        Schema::create('rfq_vendor_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->json('matched_products'); // Which products they can supply
            $table->decimal('match_score', 3, 2); // 0.00 to 1.00 matching score
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->boolean('vendor_responded')->default(false);
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('rfq_id')->references('id')->on('rfqs')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');

            // Indexes
            $table->unique(['rfq_id', 'vendor_id']);
            $table->index('match_score');
            $table->index(['rfq_id', 'match_score']);
            $table->index(['vendor_id', 'is_notified']);
            $table->index(['vendor_responded', 'responded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_vendor_matches');
        Schema::dropIfExists('product_availability_updates');
        Schema::dropIfExists('quote_attachments');
        Schema::dropIfExists('websocket_events');
        Schema::dropIfExists('vendor_online_status');
    }
};