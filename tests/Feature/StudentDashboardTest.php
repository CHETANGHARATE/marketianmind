<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $admin;
    protected Course $courseA;
    protected Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'name' => 'Maya Founder',
            'email' => 'maya@growth.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'name' => 'John Entrepreneur',
            'email' => 'john@startup.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->courseA = Course::create([
            'title' => 'Social Media Growth Engine',
            'slug' => 'social-media-growth-engine',
            'short_description' => 'Scale social channels organically.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->courseB = Course::create([
            'title' => 'Email Marketing Automation Track',
            'slug' => 'email-marketing-automation',
            'short_description' => 'Build high-converting funnels.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * TEST 1: Guest cannot access student dashboard.
     */
    public function test_guest_cannot_access_student_dashboard(): void
    {
        $this->get('/student/dashboard')->assertRedirect('/login');
    }

    /**
     * TEST 2: Authenticated student can access dashboard.
     */
    public function test_authenticated_student_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back, Maya Founder!');
        $response->assertSee('Learning Statistics');
        $response->assertSee('Enrolled Courses');
        $response->assertSee('In Progress');
        $response->assertSee('Completed');
        $response->assertSee('Overall Progress');
    }

    /**
     * TEST 3 & 4: Dashboard shows only authenticated student's enrolled courses and does not expose another student's courses.
     */
    public function test_dashboard_shows_only_authenticated_students_enrolled_courses(): void
    {
        // Maya enrolls in Course A
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // John enrolls in Course B
        Enrollment::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->courseB->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Social Media Growth Engine');
        $response->assertDontSee('Email Marketing Automation Track');
    }

    /**
     * TEST 5, 6, 7: Enrollment count, In-progress count, and Completed count are accurate.
     */
    public function test_learning_statistics_counts_are_accurate(): void
    {
        // Course A: 2 lessons
        $moduleA = CourseModule::create([
            'course_id' => $this->courseA->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);
        $lessonA1 = Lesson::create([
            'course_module_id' => $moduleA->id,
            'title' => 'Lesson A1',
            'slug' => 'lesson-a1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);
        $lessonA2 = Lesson::create([
            'course_module_id' => $moduleA->id,
            'title' => 'Lesson A2',
            'slug' => 'lesson-a2',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        // Course B: 1 lesson
        $moduleB = CourseModule::create([
            'course_id' => $this->courseB->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);
        $lessonB1 = Lesson::create([
            'course_module_id' => $moduleB->id,
            'title' => 'Lesson B1',
            'slug' => 'lesson-b1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        // Enroll Maya in both courses
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseB->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Maya completes 1 lesson in Course A (50% progress -> In Progress)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lessonA1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Maya completes the only lesson in Course B (100% progress -> Completed)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lessonB1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        // Total Enrolled: 2
        // In Progress: 1
        // Completed: 1
        // Overall: 2 of 3 lessons = 67%
        $response->assertSee('2'); // Enrolled courses
        $response->assertSee('1'); // In progress and completed
        $response->assertSee('67%'); // Overall progress
    }

    /**
     * TEST 8: Course progress percentage is displayed correctly.
     */
    public function test_course_progress_percentage_is_displayed_correctly(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->courseA->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);
        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);
        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 2',
            'slug' => 'lesson-2',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('50%');
        $response->assertSee('In Progress');
    }

    /**
     * TEST 9: Continue Learning links to the existing course learning route.
     */
    public function test_continue_learning_links_to_existing_learning_route(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->courseA->id,
            'title' => 'Core Module',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Introduction to Viral Hooks',
            'slug' => 'intro-viral-hooks',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Continue Learning');
        $response->assertSee('Introduction to Viral Hooks');
        $response->assertSee(route('student.courses.lessons.show', [$this->courseA, $lesson]));
    }

    /**
     * TEST 10: Completed course state displays 'Review Course' and 'Completed'.
     */
    public function test_completed_course_state_is_handled_correctly(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->courseA->id,
            'title' => 'Only Module',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Only Lesson',
            'slug' => 'only-lesson',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Completed');
        $response->assertSee('Review Course');
    }

    /**
     * TEST 11 & 12: Recent orders belong strictly to the authenticated student and are limited.
     */
    public function test_recent_orders_belong_only_to_authenticated_student_and_are_limited(): void
    {
        // Create 6 orders for Maya
        for ($i = 1; $i <= 6; $i++) {
            $order = Order::create([
                'user_id' => $this->student->id,
                'course_id' => $this->courseA->id,
                'order_number' => sprintf('MM-ORD-MAYA-%02d', $i),
                'amount' => 499900,
                'currency' => 'INR',
                'status' => OrderStatus::PAID,
            ]);
            $order->created_at = now()->subMinutes(60 - $i);
            $order->save();
        }

        // Create 1 order for John
        Order::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'MM-ORD-JOHN-SECRET',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Recent Purchases');
        $response->assertSee('MM-ORD-MAYA-06');
        $response->assertSee('MM-ORD-MAYA-05');
        $response->assertSee('MM-ORD-MAYA-04');
        $response->assertSee('MM-ORD-MAYA-03');
        // Oldest orders 01 and 02 should not be in the recent 4
        $response->assertDontSee('MM-ORD-MAYA-01');
        // Other student's order must never be shown
        $response->assertDontSee('MM-ORD-JOHN-SECRET');
    }

    /**
     * TEST 13: Empty course state displays friendly call to action.
     */
    public function test_empty_course_state_displays_correctly(): void
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee("You're ready to start learning");
        $response->assertSee("You haven't enrolled in any courses yet.");
        $response->assertSee(route('courses'));
    }

    /**
     * TEST 14: Empty order state displays friendly message.
     */
    public function test_empty_order_state_displays_correctly(): void
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No orders yet.');
    }

    /**
     * TEST 15: Profile shortcut points to existing profile route.
     */
    public function test_profile_shortcut_points_to_existing_profile_route(): void
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('student.profile'));
    }

    /**
     * TEST 16: Admin dashboard remains unaffected.
     */
    public function test_admin_dashboard_remains_unaffected(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }
}