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
        Schema::create('vendor_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->enum('rating_type', ['vendor', 'product', 'delivery'])->default('vendor');
            $table->decimal('overall_rating', 3, 2); // 1.00 to 5.00
            $table->decimal('quality_rating', 3, 2)->nullable();
            $table->decimal('price_rating', 3, 2)->nullable();
            $table->decimal('service_rating', 3, 2)->nullable();
            $table->decimal('delivery_rating', 3, 2)->nullable();
            $table->decimal('communication_rating', 3, 2)->nullable();
            $table->text('review_title')->nullable();
            $table->text('review_text')->nullable();
            $table->jsonb('pros')->nullable(); // Array of positive points
            $table->jsonb('cons')->nullable(); // Array of negative points
            $table->jsonb('images')->nullable(); // Review images
            $table->boolean('is_verified_purchase')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->integer('unhelpful_count')->default(0);
            $table->jsonb('helpful_voters')->nullable(); // Users who found it helpful
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('approved');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index('vendor_id');
            $table->index('buyer_id');
            $table->index('order_id');
            $table->index('product_id');
            $table->index('rating_type');
            $table->index('overall_rating');
            $table->index('status');
            $table->index('is_published');
            $table->index('is_featured');
            $table->unique(['vendor_id', 'buyer_id', 'order_id']); // One review per order
            $table->index(['vendor_id', 'status', 'is_published']); // Vendor's published reviews
            $table->index(['product_id', 'status', 'is_published']); // Product reviews
            $table->index(['rating_type', 'overall_rating', 'created_at']); // Analytics
            $table->index(['is_featured', 'overall_rating', 'created_at']); // Featured reviews
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_ratings');
    }
};