<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Achievement;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PointTransaction;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Models\UserAchievement;
use App\Notifications\AchievementUnlockedNotification;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;
    protected CourseModule $module;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected GamificationService $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gamificationService = app(GamificationService::class);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'name' => 'Gamified Student',
            'email' => 'gamified.student@example.com',
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Masterclass',
            'slug' => 'digital-marketing-masterclass',
            'short_description' => 'Master practical marketing skills.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1: Foundations',
            'sort_order' => 1,
        ]);

        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 1: Intro to Growth',
            'slug' => 'lesson-1-intro-to-growth',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 2: Audience Strategy',
            'slug' => 'lesson-2-audience-strategy',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    public function test_1_streak_starts_at_one_on_first_activity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00'));

        $this->gamificationService->recordLearningActivity($this->student);

        $streaks = $this->gamificationService->calculateStreaks($this->student);

        $this->assertEquals(1, $streaks['current']);
        $this->assertEquals(1, $streaks['longest']);
        $this->assertTrue($streaks['has_learned_today']);
    }

    public function test_2_consecutive_days_increment_streak(): void
    {
        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => '2026-09-10',
            'activity_type' => 'lesson_completed',
        ]);
        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => '2026-09-11',
            'activity_type' => 'lesson_completed',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-12 09:00:00'));
        $this->gamificationService->recordLearningActivity($this->student);

        $streaks = $this->gamificationService->calculateStreaks($this->student);

        $this->assertEquals(3, $streaks['current']);
        $this->assertEquals(3, $streaks['longest']);
    }

    public function test_3_same_calendar_day_activity_does_not_increment_streak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-12 08:00:00'));
        $this->gamificationService->recordLearningActivity($this->student);

        Carbon::setTestNow(Carbon::parse('2026-09-12 18:00:00'));
        $this->gamificationService->recordLearningActivity($this->student);

        $this->assertDatabaseCount('student_learning_days', 1);

        $streaks = $this->gamificationService->calculateStreaks($this->student);
        $this->assertEquals(1, $streaks['current']);
    }

    public function test_4_missing_a_day_breaks_current_streak(): void
    {
        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => '2026-09-08',
            'activity_type' => 'lesson_completed',
        ]);
        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => '2026-09-09',
            'activity_type' => 'lesson_completed',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00'));

        $streaks = $this->gamificationService->calculateStreaks($this->student);

        $this->assertEquals(0, $streaks['current']);
        $this->assertEquals(2, $streaks['longest']);
        $this->assertFalse($streaks['has_learned_today']);
    }

    public function test_5_yesterday_activity_keeps_streak_alive_today_before_learning(): void
    {
        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => '2026-09-11',
            'activity_type' => 'lesson_completed',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-12 09:00:00'));

        $streaks = $this->gamificationService->calculateStreaks($this->student);

        $this->assertEquals(1, $streaks['current']);
        $this->assertFalse($streaks['has_learned_today']);
    }

    public function test_6_longest_streak_is_preserved_historically(): void
    {
        foreach (['2026-08-01', '2026-08-02', '2026-08-03', '2026-08-04', '2026-08-05'] as $date) {
            StudentLearningDay::create([
                'user_id' => $this->student->id,
                'activity_date' => $date,
                'activity_type' => 'lesson_completed',
            ]);
        }

        Carbon::setTestNow(Carbon::parse('2026-09-12 10:00:00'));
        $this->gamificationService->recordLearningActivity($this->student);

        $streaks = $this->gamificationService->calculateStreaks($this->student);

        $this->assertEquals(1, $streaks['current']);
        $this->assertEquals(5, $streaks['longest']);
    }

    public function test_7_lesson_completion_awards_ten_points(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]))
            ->assertRedirect();

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->student->id,
            'event_type' => 'lesson_completed',
            'reference_type' => Lesson::class,
            'reference_id' => $this->lesson1->id,
            'points' => 10,
        ]);

        // 10 points for lesson + 25 points bonus for "First Step" milestone unlock = 35 points
        $this->assertEquals(35, $this->student->fresh()->pointsBalance());
    }

    public function test_8_duplicate_lesson_completion_does_not_award_points_again(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));

        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));

        $this->assertEquals(1, PointTransaction::where('user_id', $this->student->id)->where('event_type', 'lesson_completed')->count());
        $this->assertEquals(35, $this->student->fresh()->pointsBalance());
    }

    public function test_9_course_completion_awards_one_hundred_points(): void
    {
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson2]))
            ->assertRedirect();

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->student->id,
            'event_type' => 'course_completed',
            'reference_type' => Course::class,
            'reference_id' => $this->course->id,
            'points' => 100,
        ]);
    }

    public function test_10_duplicate_course_completion_does_not_award_points_again(): void
    {
        $this->gamificationService->awardCourseCompletion($this->student, $this->course);
        $this->gamificationService->awardCourseCompletion($this->student, $this->course);

        $this->assertEquals(1, PointTransaction::where('user_id', $this->student->id)->where('event_type', 'course_completed')->count());
        $this->assertEquals(100, $this->student->fresh()->pointsBalance());
    }

    public function test_11_first_step_badge_unlocked_on_first_lesson(): void
    {
        Notification::fake();

        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));

        $achievement = Achievement::where('slug', 'first-step')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);

        Notification::assertSentTo($this->student, AchievementUnlockedNotification::class);
    }

    public function test_12_learning_momentum_badge_unlocked_on_ten_lessons(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $l = Lesson::create([
                'course_module_id' => $this->module->id,
                'title' => "Momentum Lesson $i",
                'slug' => "momentum-lesson-$i-" . uniqid(),
                'status' => LessonStatus::PUBLISHED,
                'sort_order' => $i + 2,
            ]);
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $l->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'learning-momentum')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_13_consistent_learner_badge_unlocked_on_seven_day_streak(): void
    {
        for ($i = 6; $i >= 0; $i--) {
            StudentLearningDay::create([
                'user_id' => $this->student->id,
                'activity_date' => Carbon::parse('2026-09-12')->subDays($i)->toDateString(),
                'activity_type' => 'lesson_completed',
            ]);
        }

        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));
        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'consistent-learner')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_14_dedicated_learner_badge_unlocked_on_thirty_day_streak(): void
    {
        for ($i = 29; $i >= 0; $i--) {
            StudentLearningDay::create([
                'user_id' => $this->student->id,
                'activity_date' => Carbon::parse('2026-09-12')->subDays($i)->toDateString(),
                'activity_type' => 'lesson_completed',
            ]);
        }

        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));
        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'dedicated-learner')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_15_knowledge_builder_badge_unlocked_on_twenty_five_lessons(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $l = Lesson::create([
                'course_module_id' => $this->module->id,
                'title' => "Knowledge Lesson $i",
                'slug' => "knowledge-lesson-$i-" . uniqid(),
                'status' => LessonStatus::PUBLISHED,
                'sort_order' => $i + 2,
            ]);
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $l->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'knowledge-builder')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_16_course_finisher_badge_unlocked_on_first_course_completed(): void
    {
        Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->update(['status' => 'completed', 'completed_at' => now()]);

        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'course-finisher')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_17_marketing_learner_badge_unlocked_on_three_courses_completed(): void
    {
        Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->update(['status' => 'completed', 'completed_at' => now()]);

        for ($i = 2; $i <= 3; $i++) {
            $c = Course::create([
                'title' => "Bonus Course $i",
                'slug' => "bonus-course-$i",
                'short_description' => "Bonus Course $i description",
                'status' => CourseStatus::PUBLISHED,
                'price' => 0,
                'is_free' => true,
            ]);
            Enrollment::create([
                'user_id' => $this->student->id,
                'course_id' => $c->id,
                'status' => 'completed',
                'enrolled_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'marketing-learner')->first();
        $this->assertNotNull($achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_18_duplicate_achievement_evaluation_does_not_unlock_twice(): void
    {
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $this->gamificationService->evaluateAchievements($this->student);
        $this->gamificationService->evaluateAchievements($this->student);

        $achievement = Achievement::where('slug', 'first-step')->first();
        $count = UserAchievement::where('user_id', $this->student->id)
            ->where('achievement_id', $achievement->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_19_gamification_hub_is_accessible_by_authenticated_student(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.achievements.index'));

        $response->assertOk();
        $response->assertSee('Achievements &amp; Streaks', false);
        $response->assertSee('Current Streak');
        $response->assertSee('Longest Streak');
        $response->assertSee('Learning Points');
        $response->assertSee('Milestone Badges');
    }

    public function test_20_gamification_hub_redirects_unauthenticated_guest(): void
    {
        $response = $this->get(route('student.achievements.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_21_student_data_isolation(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        PointTransaction::create([
            'user_id' => $otherStudent->id,
            'event_type' => 'manual',
            'points' => 999,
            'description' => 'Confidential points',
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.achievements.index'));

        $response->assertOk();
        $response->assertDontSee('999 Points');
    }

    public function test_22_student_dashboard_displays_gamification_summary(): void
    {
        PointTransaction::create([
            'user_id' => $this->student->id,
            'event_type' => 'lesson_completed',
            'points' => 40,
            'description' => 'Completed lessons',
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('Day Streak');
        $response->assertSee('40 Points');
        $response->assertSee(route('student.achievements.index'));
    }

    public function test_23_student_sidebar_contains_achievements_link(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee(route('student.achievements.index'));
        $response->assertSee('Achievements');
    }

    public function test_24_notification_channel_stores_database_notification(): void
    {
        $achievement = Achievement::where('slug', 'first-step')->first();

        $this->student->notify(new AchievementUnlockedNotification($achievement));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->student->id,
            'type' => AchievementUnlockedNotification::class,
        ]);
    }

    public function test_25_points_balance_helper_method(): void
    {
        $this->assertEquals(0, $this->student->pointsBalance());

        PointTransaction::create([
            'user_id' => $this->student->id,
            'event_type' => 'lesson_completed',
            'points' => 10,
            'description' => 'Lesson 1',
        ]);
        PointTransaction::create([
            'user_id' => $this->student->id,
            'event_type' => 'bonus',
            'points' => 50,
            'description' => 'Bonus',
        ]);

        $this->assertEquals(60, $this->student->fresh()->pointsBalance());
    }
}
