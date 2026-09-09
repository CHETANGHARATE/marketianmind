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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_category_id')
                ->nullable()
                ->constrained('course_categories')
                ->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->text('short_description');
            $table->longText('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('instructor_name')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->boolean('is_free')->default(false);
            $table->string('status')->default('draft')->index();
            $table->boolean('featured')->default(false)->index();
            $table->string('estimated_duration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
