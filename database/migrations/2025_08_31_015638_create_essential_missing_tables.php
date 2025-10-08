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
        // Create products table if not exists
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->decimal('cost', 10, 2)->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->string('unit')->default('unit');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create categories table if not exists
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create suppliers table if not exists  
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Add missing columns to existing tables
        if (Schema::hasTable('buyers')) {
            if (!Schema::hasColumn('buyers', 'company_name')) {
                Schema::table('buyers', function (Blueprint $table) {
                    $table->string('company_name')->nullable()->after('name');
                });
            }
            if (!Schema::hasColumn('buyers', 'status')) {
                Schema::table('buyers', function (Blueprint $table) {
                    $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->after('email');
                });
            }
        }
        
        if (Schema::hasTable('orders')) {
            if (!Schema::hasColumn('orders', 'buyer_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('buyer_id')->nullable()->after('user_id');
                });
            }
            if (!Schema::hasColumn('orders', 'vendor_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('vendor_id')->nullable()->after('buyer_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('products');
    }
};
