<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected CourseCategory $category;
    protected Course $course;
    protected Course $otherCourse;
    protected CourseModule $module1;
    protected CourseModule $module2;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected Lesson $lesson3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Primary course for learning experience testing
        $this->course = Course::create([
            'category_id' => $this->category->id,
            'title' => 'Complete Social Media Growth',
            'slug' => 'complete-social-media-growth',
            'short_description' => 'Master organic social media strategies.',
            'description' => 'Detailed full curriculum social media course.',
            'level' => 'Beginner',
            'duration' => '3 Hours',
            'is_free' => true,
            'price' => 0.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Module 1 with 2 lessons
        $this->module1 = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1: Foundations',
            'sort_order' => 1,
        ]);

        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module1->id,
            'title' => 'Lesson 1: Platform Overview',
            'slug' => 'lesson-1-platform-overview',
            'lesson_type' => LessonType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Learn the basics of organic reach.',
            'duration' => '10 mins',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module1->id,
            'title' => 'Lesson 2: Content Strategy',
            'slug' => 'lesson-2-content-strategy',
            'lesson_type' => LessonType::TEXT,
            'content' => 'Framework for building viral organic social content.',
            'description' => 'Actionable text breakdown.',
            'duration' => '15 mins',
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
        ]);

        // Module 2 with 1 lesson
        $this->module2 = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 2: Execution',
            'sort_order' => 2,
        ]);

        $this->lesson3 = Lesson::create([
            'course_module_id' => $this->module2->id,
            'title' => 'Lesson 3: Analytics & Scale',
            'slug' => 'lesson-3-analytics-and-scale',
            'lesson_type' => LessonType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Reading metrics and tracking growth.',
            'duration' => '20 mins',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        // Separate course to test isolation and cross-access restrictions
        $this->otherCourse = Course::create([
            'category_id' => $this->category->id,
            'title' => 'Paid Executive Marketing',
            'slug' => 'paid-executive-marketing',
            'short_description' => 'High-ticket executive marketing training.',
            'is_free' => false,
            'price' => 9999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test 1: Guest cannot access learning area and is redirected to login.
     */
    public function test_guest_cannot_access_learning_area(): void
    {
        $responseCourse = $this->get(route('student.courses.show', $this->course));
        $responseCourse->assertRedirect(route('login'));

        $responseLesson = $this->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));
        $responseLesson->assertRedirect(route('login'));
    }

    /**
     * Test 2: Non-enrolled student cannot access learning area (returns 403).
     */
    public function test_non_enrolled_student_cannot_access_learning_area(): void
    {
        $responseCourse = $this->actingAs($this->student)->get(route('student.courses.show', $this->course));
        $responseCourse->assertStatus(403);

        $responseLesson = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));
        $responseLesson->assertStatus(403);
    }

    /**
     * Test 3: Enrolled student can access their course lessons.
     */
    public function test_enrolled_student_can_access_their_course_lessons(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        $response->assertSee('Complete Social Media Growth');
        $response->assertSee('Lesson 1: Platform Overview');
        $response->assertSee('Module 1: Foundations');
        $response->assertSee('Back to My Courses');
        $response->assertSee('Mark as Complete');
    }

    /**
     * Test 4: Student cannot access another student's protected course.
     */
    public function test_student_cannot_access_another_course_without_enrollment(): void
    {
        // otherStudent is enrolled in otherCourse
        Enrollment::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->otherCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // student attempts to access otherCourse
        $response = $this->actingAs($this->student)->get(route('student.courses.show', $this->otherCourse));
        $response->assertStatus(403);
    }

    /**
     * Test 5: Student cannot access unauthorized lesson by URL manipulation (returns 404).
     */
    public function test_student_cannot_access_unauthorized_lesson_by_url_manipulation(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Create lesson belonging to otherCourse
        $otherModule = CourseModule::create([
            'course_id' => $this->otherCourse->id,
            'title' => 'Alien Module',
            'sort_order' => 1,
        ]);
        $foreignLesson = Lesson::create([
            'course_module_id' => $otherModule->id,
            'title' => 'Foreign Lesson',
            'slug' => 'foreign-lesson',
            'lesson_type' => LessonType::TEXT,
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        // Try accessing foreignLesson through course URL
        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $foreignLesson]));
        $response->assertStatus(404);
    }

    /**
     * Test 6: Current lesson is correctly identified with active indicators.
     */
    public function test_current_lesson_is_correctly_identified(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson2]));

        $response->assertStatus(200);
        // Current lesson highlighted in sidebar
        $response->assertSee('Lesson 2: Content Strategy');
        $response->assertSee('bg-indigo-50 border-l-4 border-indigo-600', false);
    }

    /**
     * Test 7: Course progress is displayed correctly (e.g. 33% or 67%).
     */
    public function test_course_progress_is_displayed_correctly(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete 1 of 3 lessons (33%)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson2]));

        $response->assertStatus(200);
        $response->assertSee('33% Complete');
        $response->assertSee('1 of 3 lessons');
    }

    /**
     * Test 8: Completed lessons are identified correctly with checkmark.
     */
    public function test_completed_lessons_are_identified_correctly_with_checkmark(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson2]));

        $response->assertStatus(200);
        $response->assertSee('bg-emerald-500 text-white', false);
        $response->assertSee('✓');
    }

    /**
     * Test 9: Incomplete lessons are identified correctly with neutral indicators.
     */
    public function test_incomplete_lessons_are_identified_correctly(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        $response->assertSee('Lesson 3: Analytics &amp; Scale', false);
        $response->assertSee('border-slate-300', false);
    }

    /**
     * Test 10: Mark lesson complete works and advances to next lesson.
     */
    public function test_mark_lesson_complete_works(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));

        $response->assertRedirect(route('student.courses.lessons.show', [$this->course, $this->lesson2]));
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
        ]);
    }

    /**
     * Test 11: Duplicate completion does not create duplicate progress records.
     */
    public function test_duplicate_completion_does_not_create_duplicate_records(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete once
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));
        $this->assertEquals(1, LessonProgress::where('user_id', $this->student->id)->where('lesson_id', $this->lesson1->id)->count());

        // Complete again
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));
        $this->assertEquals(1, LessonProgress::where('user_id', $this->student->id)->where('lesson_id', $this->lesson1->id)->count());
    }

    /**
     * Test 12: Unauthorized student cannot mark another course lesson complete.
     */
    public function test_unauthorized_student_cannot_mark_another_course_lesson_complete(): void
    {
        $response = $this->actingAs($this->otherStudent)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));
        $response->assertStatus(403);
    }

    /**
     * Test 13: Previous lesson navigation works.
     */
    public function test_previous_lesson_navigation_works(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson2]));

        $response->assertStatus(200);
        $response->assertSee(route('student.courses.lessons.show', [$this->course, $this->lesson1]));
        $response->assertSee('&larr; Previous Lesson', false);
    }

    /**
     * Test 14: Next lesson navigation works.
     */
    public function test_next_lesson_navigation_works(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        $response->assertSee(route('student.courses.lessons.show', [$this->course, $this->lesson2]));
        $response->assertSee('Next Lesson &rarr;', false);
    }

    /**
     * Test 15: First lesson handles Previous correctly (disabled / non-clickable).
     */
    public function test_first_lesson_handles_previous_correctly(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        // Displays disabled cursor-not-allowed indicator, not a link
        $response->assertSee('cursor-not-allowed');
    }

    /**
     * Test 16: Last lesson handles Next correctly (displays course finish message).
     */
    public function test_last_lesson_handles_next_correctly(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson3]));

        $response->assertStatus(200);
        $response->assertSee('Course Finished 🎉');
    }

    /**
     * Test 17: Continue Learning resolves to appropriate lesson.
     */
    public function test_continue_learning_resolves_to_first_incomplete_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Accessing course show route should redirect to lesson 2 (the next incomplete lesson)
        $response = $this->actingAs($this->student)->get(route('student.courses.show', $this->course));
        $response->assertRedirect(route('student.courses.lessons.show', [$this->course, $this->lesson2]));
    }

    /**
     * Test 18: Course completed state works when all lessons done (no certificates).
     */
    public function test_course_completed_state_displays_when_all_lessons_completed(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete all 3 lessons
        foreach ([$this->lesson1, $this->lesson2, $this->lesson3] as $lesson) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson3]));

        $response->assertStatus(200);
        $response->assertSee('100% Complete');
        $response->assertSee('Course Completed! 🎉');
        $response->assertSee('Review Course');
        $response->assertSee('Back to My Courses');
        // Strictly verify NO certificate terms/buttons are present in Phase 6.6
        $response->assertDontSee('Download Certificate');
        $response->assertDontSee('View Certificate');
    }

    /**
     * Test 19: Mobile/desktop layout renders cleanly without errors.
     */
    public function test_mobile_and_desktop_layout_renders_cleanly(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        $response->assertSee('mobile-curriculum-toggle');
        $response->assertSee('mobile-curriculum-drawer');
        $response->assertSee('Course Curriculum');
    }

    /**
     * Test 20: Existing enrollment functionality remains unaffected.
     */
    public function test_existing_enrollment_functionality_remains_unaffected(): void
    {
        // Enrolling in free course still works
        $response = $this->actingAs($this->otherStudent)->post(route('student.courses.enroll', $this->course));
        $response->assertRedirect(route('student.courses.show', $this->course));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test 21: Existing payment functionality remains unaffected.
     */
    public function test_existing_payment_functionality_remains_unaffected(): void
    {
        // Purchasing a paid course initiates checkout order
        $response = $this->actingAs($this->student)->post(route('student.courses.purchase', $this->otherCourse));
        $order = Order::where('user_id', $this->student->id)->where('course_id', $this->otherCourse->id)->first();

        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $response->assertRedirect(route('student.courses.checkout', $order));

        $checkoutResponse = $this->actingAs($this->student)->get(route('student.courses.checkout', $order));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Paid Executive Marketing');
    }

    /**
     * Test 22: Existing dashboard functionality remains unaffected.
     */
    public function test_existing_dashboard_functionality_remains_unaffected(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Complete Social Media Growth');
        $response->assertSee('Student Portal');
    }
}