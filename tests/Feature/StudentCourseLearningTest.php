<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseLearningTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected Course $freeCourse;
    protected Course $paidCourse;
    protected CourseModule $module1;
    protected CourseModule $module2;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected Lesson $draftLesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        // Free published course
        $this->freeCourse = Course::create([
            'title' => 'Digital Marketing Fundamentals',
            'slug' => 'digital-marketing-fundamentals',
            'short_description' => 'A practical marketing course for founders.',
            'description' => 'Full comprehensive marketing overview.',
            'level' => 'Beginner',
            'duration' => '4 Hours',
            'is_free' => true,
            'price' => 0.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Paid course
        $this->paidCourse = Course::create([
            'title' => 'Advanced Growth Hacking',
            'slug' => 'advanced-growth-hacking',
            'short_description' => 'Scale your revenue fast.',
            'is_free' => false,
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Modules for free course
        $this->module1 = CourseModule::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Introduction to Digital Growth',
            'sort_order' => 1,
        ]);

        $this->module2 = CourseModule::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Customer Acquisition Tactics',
            'sort_order' => 2,
        ]);

        // Lessons
        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module1->id,
            'title' => 'Welcome & Roadmap',
            'slug' => 'welcome-and-roadmap',
            'lesson_type' => LessonType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Introduction video.',
            'duration' => '10 mins',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module2->id,
            'title' => 'Understanding Conversion Funnels',
            'slug' => 'understanding-conversion-funnels',
            'lesson_type' => LessonType::TEXT,
            'content' => '<p>Detailed article on conversion funnels and buyer psychology.</p>',
            'description' => 'Text breakdown of funnels.',
            'duration' => '15 mins',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->draftLesson = Lesson::create([
            'course_module_id' => $this->module2->id,
            'title' => 'Draft Unpublished Lesson',
            'slug' => 'draft-unpublished-lesson',
            'lesson_type' => LessonType::TEXT,
            'content' => 'Draft content.',
            'sort_order' => 2,
            'status' => LessonStatus::DRAFT,
        ]);
    }

    public function test_public_courses_catalog_renders_published_courses(): void
    {
        $response = $this->get(route('courses'));

        $response->assertStatus(200);
        $response->assertSee('Digital Marketing Fundamentals');
        $response->assertSee('Advanced Growth Hacking');
    }

    public function test_public_course_detail_shows_curriculum_and_enrollment_cta(): void
    {
        // Guest view
        $response = $this->get(route('courses.show', $this->freeCourse));

        $response->assertStatus(200);
        $response->assertSee('Digital Marketing Fundamentals');
        $response->assertSee('Introduction to Digital Growth');
        $response->assertSee('Welcome &amp; Roadmap', false);
        $response->assertSee('Free');
        $response->assertSee('Login to Enroll Free');

        // Authenticated student view
        $studentResponse = $this->actingAs($this->student)->get(route('courses.show', $this->freeCourse));
        $studentResponse->assertStatus(200);
        $studentResponse->assertSee('Enroll for Free');
    }

    public function test_public_course_detail_for_paid_course_shows_price_and_coming_soon(): void
    {
        $response = $this->get(route('courses.show', $this->paidCourse));

        $response->assertStatus(200);
        $response->assertSee('Advanced Growth Hacking');
        $response->assertSee('4,999');
        $response->assertSee('Purchase functionality coming soon');
    }

    public function test_guest_is_redirected_to_login_when_enrolling_in_free_course(): void
    {
        $response = $this->post(route('student.courses.enroll', $this->freeCourse));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('enrollments', [
            'course_id' => $this->freeCourse->id,
        ]);
    }

    public function test_student_can_enroll_in_free_course(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->freeCourse));

        $response->assertRedirect(route('student.courses.show', $this->freeCourse));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    public function test_enrolling_twice_does_not_create_duplicate_enrollment(): void
    {
        // First enrollment
        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->freeCourse));

        $this->assertCount(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->freeCourse->id)->get());

        // Second enrollment attempt
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->freeCourse));

        $response->assertRedirect(route('student.courses.show', $this->freeCourse));
        $this->assertCount(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->freeCourse->id)->get());
    }

    public function test_cannot_enroll_in_paid_course_via_free_enrollment_endpoint(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->paidCourse));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
        ]);
    }

    public function test_student_courses_dashboard_lists_enrolled_courses(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.index'));

        $response->assertStatus(200);
        $response->assertSee('Digital Marketing Fundamentals');
        $response->assertSee('0% Complete');
    }

    public function test_non_enrolled_student_is_forbidden_from_learning_player(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.courses.show', $this->freeCourse));

        $response->assertStatus(403);
    }

    public function test_enrolled_student_accessing_course_is_redirected_to_next_incomplete_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.show', $this->freeCourse));

        // Redirects to lesson 1
        $response->assertRedirect(route('student.courses.lessons.show', [
            'course' => $this->freeCourse,
            'lesson' => $this->lesson1,
        ]));
    }

    public function test_enrolled_student_can_view_lesson_in_learning_player(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [
                'course' => $this->freeCourse,
                'lesson' => $this->lesson1,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Welcome &amp; Roadmap', false);
        $response->assertSee('Introduction to Digital Growth');
        $response->assertSee('Mark as Complete');
        $response->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ');
    }

    public function test_accessing_lesson_from_different_course_returns_404(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Create lesson on another course
        $otherModule = CourseModule::create([
            'course_id' => $this->paidCourse->id,
            'title' => 'Other Module',
            'sort_order' => 1,
        ]);
        $otherLesson = Lesson::create([
            'course_module_id' => $otherModule->id,
            'title' => 'Other Lesson',
            'slug' => 'other-lesson',
            'lesson_type' => LessonType::TEXT,
            'content' => 'Content',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [
                'course' => $this->freeCourse,
                'lesson' => $otherLesson,
            ]));

        $response->assertStatus(404);
    }

    public function test_accessing_draft_lesson_returns_404(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [
                'course' => $this->freeCourse,
                'lesson' => $this->draftLesson,
            ]));

        $response->assertStatus(404);
    }

    public function test_student_can_mark_lesson_as_complete_and_auto_complete_course(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson 1 -> redirects to next lesson (lesson 2)
        $response1 = $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [
                'course' => $this->freeCourse,
                'lesson' => $this->lesson1,
            ]));

        $response1->assertRedirect(route('student.courses.lessons.show', [
            'course' => $this->freeCourse,
            'lesson' => $this->lesson2,
        ]));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
        ]);

        // Course should be 50% completed (1 out of 2 published lessons)
        $this->assertEquals(50, $this->freeCourse->progressFor($this->student)['percentage']);
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->fresh()->status);

        // Complete lesson 2 -> last lesson completed, redirects to lesson 2 with congrats
        $response2 = $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [
                'course' => $this->freeCourse,
                'lesson' => $this->lesson2,
            ]));

        $response2->assertRedirect(route('student.courses.lessons.show', [
            'course' => $this->freeCourse,
            'lesson' => $this->lesson2,
        ]));

        $this->assertEquals(100, $this->freeCourse->progressFor($this->student)['percentage']);

        // Enrollment should now be COMPLETED with completed_at set
        $freshEnrollment = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::COMPLETED, $freshEnrollment->status);
        $this->assertNotNull($freshEnrollment->completed_at);
    }
}