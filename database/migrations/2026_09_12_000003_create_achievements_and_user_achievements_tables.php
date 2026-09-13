<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('icon');
            $table->string('badge_color')->default('indigo');
            $table->string('requirement_type'); // lessons_completed, courses_completed, streak_days
            $table->integer('requirement_value');
            $table->integer('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'earned_at']);
        });

        // Seed 7 standard milestone achievements
        $now = now();
        DB::table('achievements')->insert([
            [
                'slug' => 'first-step',
                'name' => 'First Step',
                'description' => 'Complete your first lesson.',
                'icon' => 'rocket',
                'badge_color' => 'indigo',
                'requirement_type' => 'lessons_completed',
                'requirement_value' => 1,
                'points' => 25,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'learning-momentum',
                'name' => 'Learning Momentum',
                'description' => 'Complete 10 lessons.',
                'icon' => 'bolt',
                'badge_color' => 'sky',
                'requirement_type' => 'lessons_completed',
                'requirement_value' => 10,
                'points' => 50,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'knowledge-builder',
                'name' => 'Knowledge Builder',
                'description' => 'Complete 25 lessons.',
                'icon' => 'academic-cap',
                'badge_color' => 'purple',
                'requirement_type' => 'lessons_completed',
                'requirement_value' => 25,
                'points' => 100,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'course-finisher',
                'name' => 'Course Finisher',
                'description' => 'Complete your first course.',
                'icon' => 'trophy',
                'badge_color' => 'emerald',
                'requirement_type' => 'courses_completed',
                'requirement_value' => 1,
                'points' => 100,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'marketing-learner',
                'name' => 'Marketing Learner',
                'description' => 'Complete 3 courses.',
                'icon' => 'star',
                'badge_color' => 'amber',
                'requirement_type' => 'courses_completed',
                'requirement_value' => 3,
                'points' => 200,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'consistent-learner',
                'name' => 'Consistent Learner',
                'description' => 'Reach a 7-day learning streak.',
                'icon' => 'fire',
                'badge_color' => 'rose',
                'requirement_type' => 'streak_days',
                'requirement_value' => 7,
                'points' => 100,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'dedicated-learner',
                'name' => 'Dedicated Learner',
                'description' => 'Reach a 30-day learning streak.',
                'icon' => 'sparkles',
                'badge_color' => 'yellow',
                'requirement_type' => 'streak_days',
                'requirement_value' => 30,
                'points' => 300,
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
