<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $courseA;
    protected Course $courseB;
    protected Lesson $lessonA1;
    protected Lesson $lessonA2;
    protected Lesson $lessonB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->courseA = Course::create([
            'title' => 'Social Media Strategy for Founders',
            'slug' => 'social-media-strategy',
            'short_description' => 'Build an audience.',
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $moduleA = CourseModule::create([
            'course_id' => $this->courseA->id,
            'title' => 'Module 1: Audience Research',
            'sort_order' => 1,
        ]);

        $this->lessonA1 = Lesson::create([
            'course_module_id' => $moduleA->id,
            'title' => 'Finding Your Niche Persona',
            'slug' => 'finding-niche-persona',
            'lesson_type' => LessonType::VIDEO,
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->lessonA2 = Lesson::create([
            'course_module_id' => $moduleA->id,
            'title' => 'Content Calendar Planning',
            'slug' => 'content-calendar-planning',
            'lesson_type' => LessonType::TEXT,
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->courseB = Course::create([
            'title' => 'Local SEO Essentials',
            'slug' => 'local-seo-essentials',
            'short_description' => 'Dominate Google Maps.',
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $moduleB = CourseModule::create([
            'course_id' => $this->courseB->id,
            'title' => 'Module 1: Google Business Profile',
            'sort_order' => 1,
        ]);

        $this->lessonB1 = Lesson::create([
            'course_module_id' => $moduleB->id,
            'title' => 'Optimizing Google Maps Listing',
            'slug' => 'optimizing-google-maps',
            'lesson_type' => LessonType::VIDEO,
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_learning_analytics(): void
    {
        $response = $this->get(route('student.progress'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_student_can_view_analytics_dashboard_with_empty_state(): void
    {
        $newStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        $response = $this->actingAs($newStudent)->get(route('student.progress'));

        $response->assertOk();
        $response->assertSee('Learning Analytics');
        $response->assertSee('Overall Progress');
        $response->assertSee('0%');
        $response->assertSee('Your learning journey begins here');
        $response->assertSee('Explore Courses');
    }

    public function test_analytics_dashboard_accurately_computes_course_overview_metrics(): void
    {
        // Enroll in Course A and Course B
        $enrollmentA = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'enrolled_at' => now(),
        ]);

        $enrollmentB = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseB->id,
            'enrolled_at' => now(),
        ]);

        // Complete 1 lesson in Course A (1 of 2 -> 50%, In Progress)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessonA1->id,
            'completed' => true,
            'completed_at' => now()->subHour(),
            'last_watched_at' => now()->subHour(),
        ]);

        // Complete all lessons in Course B (1 of 1 -> 100%, Completed)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessonB1->id,
            'completed' => true,
            'completed_at' => now()->subMinutes(30),
            'last_watched_at' => now()->subMinutes(30),
        ]);

        $enrollmentB->markAsCompleted();

        // Issue Certificate for Course B
        $certificate = Certificate::issueFor($this->student, $this->courseB, $enrollmentB);

        $response = $this->actingAs($this->student)->get(route('student.progress'));

        $response->assertOk();

        // Assert overview numbers:
        // Total: 2 courses
        // In Progress: 1
        // Completed: 1
        // Certificates: 1
        // Total lessons: 3
        // Completed lessons: 2
        // Overall: 67%
        $response->assertSee('67%');
        $response->assertSee('2 of 3 total lessons finished');
        $response->assertSee('1 In Progress');
        $response->assertSee('1 Done');
        $response->assertSee('1'); // Certificates
    }

    public function test_course_level_analytics_accurately_displays_remaining_lessons_and_completion_state(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessonA1->id,
            'completed' => true,
            'completed_at' => now(),
            'last_watched_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.progress'));

        $response->assertOk();
        $response->assertSee($this->courseA->title);
        $response->assertSee('1 of 2 lessons completed');
        $response->assertSee('1 lesson remaining');
        $response->assertSee('50%');
        $response->assertSee('In Progress');
        $response->assertSee('Continue Learning');
    }

    public function test_recent_learning_activity_feed_displays_latest_accessed_lessons(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessonA1->id,
            'completed' => true,
            'completed_at' => now()->subMinutes(10),
            'last_watched_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.progress'));

        $response->assertOk();
        $response->assertSee('Recent Learning Activity');
        $response->assertSee('Finding Your Niche Persona');
        $response->assertSee('Social Media Strategy for Founders');
        $response->assertSee('Resume');
    }

    public function test_strict_idor_isolation_between_students(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        // Student 1 enrolled in Course A and completed lesson A1
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'enrolled_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessonA1->id,
            'completed' => true,
            'last_watched_at' => now(),
        ]);

        // Student 2 enrolled ONLY in Course B
        Enrollment::create([
            'user_id' => $otherStudent->id,
            'course_id' => $this->courseB->id,
            'enrolled_at' => now(),
        ]);

        // Accessing as Student 2
        $response = $this->actingAs($otherStudent)->get(route('student.progress'));

        $response->assertOk();
        $response->assertSee($this->courseB->title);
        $response->assertDontSee($this->courseA->title);
        $response->assertDontSee('Finding Your Niche Persona');
        $response->assertSee('0 of 1 lessons completed');
        $response->assertSee('1 lesson remaining');
    }

    public function test_unpublished_courses_are_excluded_from_analytics(): void
    {
        $unpublishedCourse = Course::create([
            'title' => 'Secret Draft Course',
            'slug' => 'secret-draft-course',
            'short_description' => 'Draft content.',
            'status' => CourseStatus::DRAFT,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $unpublishedCourse->id,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.progress'));

        $response->assertOk();
        $response->assertDontSee('Secret Draft Course');
    }

    public function test_next_continue_course_prioritizes_incomplete_enrolled_courses(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.progress'));

        $response->assertOk();
        $response->assertSee('Social Media Strategy for Founders');
        $response->assertSee('Start Learning');
    }
}