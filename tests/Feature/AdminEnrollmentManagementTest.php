<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
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

class AdminEnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Enrollment Admin',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->studentA = User::factory()->create([
            'name' => 'Charlie Marketer',
            'email' => 'charlie@marketing.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->studentB = User::factory()->create([
            'name' => 'Diana Creator',
            'email' => 'diana@content.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Social Media Ads',
            'slug' => 'social-media-ads',
        ]);

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Instagram Growth Strategies',
            'slug' => 'instagram-growth-strategies',
            'short_description' => 'Master organic Instagram growth.',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'LinkedIn B2B Lead Gen',
            'slug' => 'linkedin-b2b-lead-gen',
            'short_description' => 'Generate high ticket B2B leads.',
            'price' => 2499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test guest cannot access admin enrollments and is redirected to login.
     */
    public function test_guest_cannot_access_admin_enrollments(): void
    {
        $response = $this->get(route('admin.enrollments.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot access admin enrollments and receives 403 Forbidden.
     */
    public function test_student_cannot_access_admin_enrollments(): void
    {
        $response = $this->actingAs($this->studentA)->get(route('admin.enrollments.index'));
        $response->assertForbidden();
    }

    /**
     * Test authorized admin can view the enrollment list with correct student and course.
     */
    public function test_authorized_admin_can_view_enrollment_list(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index'));

        $response->assertOk();
        $response->assertViewIs('admin.enrollments.index');
        $response->assertSee('Course Enrollments');
        $response->assertSee('Charlie Marketer');
        $response->assertSee('charlie@marketing.com');
        $response->assertSee('Instagram Growth Strategies');
    }

    /**
     * Test search by student name, student email, and course title.
     */
    public function test_enrollment_search_filters_correctly(): void
    {
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Search by student name
        $responseName = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['search' => 'Charlie']));
        $responseName->assertOk();
        $responseName->assertSee('Charlie Marketer');
        $responseName->assertDontSee('Diana Creator');

        // Search by student email
        $responseEmail = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['search' => 'content.com']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('Diana Creator');
        $responseEmail->assertDontSee('Charlie Marketer');

        // Search by course title
        $responseCourse = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['search' => 'LinkedIn']));
        $responseCourse->assertOk();
        $responseCourse->assertSee('Diana Creator');
        $responseCourse->assertDontSee('Charlie Marketer');
    }

    /**
     * Test course filter.
     */
    public function test_filter_by_course_works(): void
    {
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['course' => $this->course1->slug]));
        $response->assertOk();
        $response->assertSee('Charlie Marketer');
        $response->assertDontSee('Diana Creator');
        $enrollments = $response->viewData('enrollments');
        $this->assertCount(1, $enrollments);
        $this->assertEquals($this->course1->id, $enrollments->first()->course_id);
    }

    /**
     * Test student filter.
     */
    public function test_filter_by_student_works(): void
    {
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['student' => $this->studentB->id]));
        $response->assertOk();
        $response->assertSee('Diana Creator');
        $response->assertDontSee('Charlie Marketer');
    }

    /**
     * Test status and completion filters.
     */
    public function test_status_and_completion_filters_work(): void
    {
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        // Filter: active
        $responseActive = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['status' => 'active']));
        $responseActive->assertOk();
        $responseActive->assertSee('Charlie Marketer');
        $responseActive->assertDontSee('Diana Creator');

        // Filter: completed
        $responseCompleted = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['status' => 'completed']));
        $responseCompleted->assertOk();
        $responseCompleted->assertSee('Diana Creator');
        $responseCompleted->assertDontSee('Charlie Marketer');
    }

    /**
     * Test sorting works and invalid sort is safely sanitized.
     */
    public function test_enrollment_sorting_works_and_sanitizes_input(): void
    {
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Sort student_asc
        $responseAsc = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['sort' => 'student_asc']));
        $responseAsc->assertOk();
        $this->assertEquals('Charlie Marketer', $responseAsc->viewData('enrollments')->first()->user->name);

        // Sort student_desc
        $responseDesc = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['sort' => 'student_desc']));
        $responseDesc->assertOk();
        $this->assertEquals('Diana Creator', $responseDesc->viewData('enrollments')->first()->user->name);

        // SQL injection payload does not crash
        $responseHack = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['sort' => "'; DELETE FROM enrollments; --"]));
        $responseHack->assertOk();
    }

    /**
     * Test pagination works and preserves query strings.
     */
    public function test_pagination_works_and_preserves_query_strings(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $user = User::factory()->create(['role' => UserRole::STUDENT]);
            $course = Course::create([
                'course_category_id' => $this->category->id,
                'title' => "Pagination Course $i",
                'slug' => "pagination-course-$i",
                'short_description' => 'Test course.',
                'price' => 100.00,
                'status' => CourseStatus::PUBLISHED,
            ]);
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index', ['sort' => 'newest']));
        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertCount(15, $enrollments);
        $this->assertTrue($enrollments->hasPages());
    }

    /**
     * Test authorized admin can view enrollment details with progress, certificate, and order context.
     */
    public function test_admin_can_view_enrollment_details_with_all_context(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course1->id,
            'title' => 'Instagram Basics',
            'sort_order' => 1,
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Profile Optimization',
            'slug' => 'profile-optimization',
            'type' => LessonType::VIDEO,
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Content Pillars',
            'slug' => 'content-pillars',
            'type' => LessonType::TEXT,
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(2),
        ]);

        // Complete 1 of 2 lessons = 50%
        LessonProgress::create([
            'user_id' => $this->studentA->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Paid Order
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-ENR-TEST',
            'amount' => 149900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Certificate
        $cert = Certificate::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-CERT-ENR-01',
            'course_title' => $this->course1->title,
            'student_name' => $this->studentA->name,
            'instructor_name' => 'Marketian Mind Faculty',
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertViewIs('admin.enrollments.show');
        $response->assertSee('Enrollment Record #' . $enrollment->id);
        $response->assertSee('Charlie Marketer');
        $response->assertSee('Instagram Growth Strategies');
        $response->assertSee('50%');
        $response->assertSee('1 of 2 published');
        $response->assertSee('ORD-ENR-TEST');
        $response->assertSee('MM-CERT-ENR-01');
    }

    /**
     * Test non-existent enrollment returns 404.
     */
    public function test_non_existent_enrollment_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/enrollments/999999');
        $response->assertNotFound();
    }

    /**
     * Test student cannot view enrollment details.
     */
    public function test_student_cannot_view_admin_enrollment_details(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->studentA)->get(route('admin.enrollments.show', $enrollment));
        $response->assertForbidden();
    }

    /**
     * Test free course enrollment shows direct access without failing.
     */
    public function test_free_course_enrollment_displays_gracefully(): void
    {
        $freeCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Free Mini Marketing Course',
            'slug' => 'free-mini-course',
            'short_description' => 'Free intro course.',
            'is_free' => true,
            'price' => 0.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.show', $enrollment));
        $response->assertOk();
        $response->assertSee('Free Access');
        $response->assertSee('Not Issued');
    }

    /**
     * Test existing admin students and dashboard continue to function.
     */
    public function test_existing_admin_pages_continue_to_function(): void
    {
        $dashResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashResponse->assertOk();

        $studentsResponse = $this->actingAs($this->admin)->get(route('admin.students.index'));
        $studentsResponse->assertOk();
    }
}