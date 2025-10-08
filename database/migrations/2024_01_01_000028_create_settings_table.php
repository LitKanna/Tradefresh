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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json, array
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can be exposed to frontend
            $table->boolean('is_encrypted')->default(false); // Should be encrypted
            $table->jsonb('validation_rules')->nullable(); // Laravel validation rules
            $table->jsonb('options')->nullable(); // For select/radio options
            $table->jsonb('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint
            $table->unique(['group', 'key']);

            // Indexes
            $table->index('group');
            $table->index('key');
            $table->index('is_public');
            $table->index(['group', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};