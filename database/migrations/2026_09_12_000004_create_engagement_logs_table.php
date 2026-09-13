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
        Schema::create('engagement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('channel', 32)->default('database');
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'course_id', 'type'], 'engagement_user_course_type_unique');
            $table->index(['user_id', 'type', 'sent_at'], 'engagement_user_type_sent_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagement_logs');
    }
};
