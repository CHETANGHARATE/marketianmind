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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('course_title');
            $table->string('student_name');
            $table->string('instructor_name')->nullable();
            $table->timestamp('course_completion_date');
            $table->timestamp('issued_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Enforce at most ONE certificate per user per course
            $table->unique(['user_id', 'course_id']);
            $table->index('enrollment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};