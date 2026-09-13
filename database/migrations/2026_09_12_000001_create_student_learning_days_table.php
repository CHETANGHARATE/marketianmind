<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_learning_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_type')->default('lesson_completed');
            $table->timestamps();

            $table->unique(['user_id', 'activity_date']);
            $table->index(['user_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_learning_days');
    }
};
