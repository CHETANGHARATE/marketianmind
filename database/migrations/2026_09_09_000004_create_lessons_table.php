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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')
                ->constrained('course_modules')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->text('description')->nullable();
            $table->string('lesson_type')->default('video')->index();
            $table->string('video_url')->nullable();
            $table->longText('content')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('is_preview')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
