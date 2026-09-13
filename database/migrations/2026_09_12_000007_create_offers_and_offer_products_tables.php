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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('discount_type', 20)->default('percentage'); // 'percentage' or 'fixed'
            $table->decimal('discount_value', 10, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_coupons')->default(false);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->string('badge_text', 100)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('offer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->string('product_type', 50); // 'course' or 'bundle'
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->unique(['offer_id', 'product_type', 'product_id']);
            $table->index(['product_type', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_products');
        Schema::dropIfExists('offers');
    }
};
