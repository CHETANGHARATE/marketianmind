<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCourseAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Marketing Fundamentals',
            'slug' => 'marketing-fundamentals',
        ]);
    }

    private function createCourse(array $attributes = []): Course
    {
        $title = $attributes['title'] ?? 'Course ' . Str::random(6);
        return Course::create(array_merge([
            'course_category_id' => $this->category->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'short_description' => 'Test course description',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ], $attributes));
    }

    // -------------------------------------------------------------
    // Authorization & Access Control
    // -------------------------------------------------------------

    public function test_guest_is_redirected_to_login_when_accessing_course_analytics_index(): void
    {
        $response = $this->get(route('admin.analytics.courses'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_receives_forbidden_when_accessing_course_analytics_index(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.analytics.courses'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_course_analytics_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertViewIs('admin.analytics.courses.index');
        $response->assertSee('Course Analytics');
        $response->assertSee('Catalog Revenue');
    }

    public function test_guest_is_redirected_to_login_when_accessing_course_analytics_show(): void
    {
        $course = $this->createCourse();
        $response = $this->get(route('admin.analytics.courses.show', $course));
        $response->assertRedirect(route('login'));
    }

    public function test_student_receives_forbidden_when_accessing_course_analytics_show(): void
    {
        $course = $this->createCourse();
        $response = $this->actingAs($this->student)->get(route('admin.analytics.courses.show', $course));
        $response->assertForbidden();
    }

    public function test_admin_can_view_course_analytics_show(): void
    {
        $course = $this->createCourse([
            'title' => 'Advanced Funnel Optimization',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses.show', $course));
        $response->assertOk();
        $response->assertViewIs('admin.analytics.courses.show');
        $response->assertSee('Advanced Funnel Optimization');
        $response->assertSee('Learner Progress Distribution');
    }

    // -------------------------------------------------------------
    // Metric Precision & Revenue Integrity
    // -------------------------------------------------------------

    public function test_analytics_correctly_calculates_authoritative_paid_revenue(): void
    {
        $course = $this->createCourse([
            'title' => 'Growth Hacking Masterclass',
            'is_free' => false,
            'price' => 4999.00,
        ]);

        // Order 1: Paid (₹4999 = 499900 paise)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Order 2: Another Paid Order (₹4999 = 499900 paise)
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        Order::create([
            'user_id' => $student2->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1002',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Order 3: PENDING (₹4999) - MUST NOT BE COUNTED IN REVENUE
        Order::create([
            'user_id' => $student2->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1003',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        // Order 4: FAILED (₹4999) - MUST NOT BE COUNTED IN REVENUE
        Order::create([
            'user_id' => $student2->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1004',
            'amount' => 499900,
            'status' => OrderStatus::FAILED,
        ]);

        // Expected revenue: 4999 + 4999 = 9998
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertSee('₹9,998.00');

        $showResponse = $this->actingAs($this->admin)->get(route('admin.analytics.courses.show', $course));
        $showResponse->assertOk();
        $showResponse->assertSee('₹9,998.00');
    }

    public function test_free_courses_produce_zero_revenue_even_if_heavily_enrolled(): void
    {
        $freeCourse = $this->createCourse([
            'title' => 'Free Marketing Basics',
            'is_free' => true,
            'price' => 0.00,
        ]);

        // Enroll 5 students
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create(['role' => UserRole::STUDENT]);
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $freeCourse->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertSee('₹0.00');

        $showResponse = $this->actingAs($this->admin)->get(route('admin.analytics.courses.show', $freeCourse));
        $showResponse->assertOk();
        $showResponse->assertSee('₹0.00');
    }

    public function test_revenue_is_immune_to_multiple_failed_or_repeated_payment_attempts(): void
    {
        $course = $this->createCourse([
            'is_free' => false,
            'price' => 2500.00,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-TEST-2500',
            'amount' => 250000,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Simulate 2 failed attempts and 1 successful attempt on the same order
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'amount' => 250000,
            'status' => PaymentStatus::FAILED,
            'razorpay_payment_id' => 'pay_fail_1',
            'razorpay_order_id' => 'order_rp_1',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'amount' => 250000,
            'status' => PaymentStatus::FAILED,
            'razorpay_payment_id' => 'pay_fail_2',
            'razorpay_order_id' => 'order_rp_1',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'amount' => 250000,
            'status' => PaymentStatus::CAPTURED,
            'razorpay_payment_id' => 'pay_success_1',
            'razorpay_order_id' => 'order_rp_1',
            'paid_at' => now(),
        ]);

        // Total revenue must strictly be ₹2500.00, not ₹7500.00 or duplicated
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertSee('₹2,500.00');
    }

    public function test_historical_purchased_price_is_preserved_if_course_price_changes_later(): void
    {
        $course = $this->createCourse([
            'is_free' => false,
            'price' => 1000.00,
        ]);

        // Student purchased at ₹1000
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-HIST-1000',
            'amount' => 100000, // ₹1,000 in paise
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Course price is later updated by admin to ₹5000
        $course->update(['price' => 5000.00]);

        // Analytics must reflect actual historical order amount ₹1,000.00, not ₹5,000.00
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertSee('₹1,000.00');
    }

    // -------------------------------------------------------------
    // Enrollment & Completion Accuracy
    // -------------------------------------------------------------

    public function test_enrollment_counts_and_completion_rates_are_accurate(): void
    {
        $course = $this->createCourse(['title' => 'Metrics Test Course']);

        // 3 Active, 2 Completed = 5 Total (40% completion rate)
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            $user = User::factory()->create();
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::COMPLETED,
                'enrolled_at' => now()->subDays(5),
                'completed_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses'));
        $response->assertOk();
        $response->assertSee('40%'); // 2 / 5 = 40%

        $showResponse = $this->actingAs($this->admin)->get(route('admin.analytics.courses.show', $course));
        $showResponse->assertOk();
        $showResponse->assertSee('40%');
    }

    public function test_progress_distribution_and_lesson_stats_render_properly(): void
    {
        $course = $this->createCourse(['title' => 'Deep Dive Course']);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'sort_order' => 1,
            'title' => 'Introduction to Copywriting',
        ]);
        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Headline Formulation',
            'slug' => 'headline-formulation',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);
        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Body Copy Strategy',
            'slug' => 'body-copy-strategy',
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
        ]);

        // Student 1: Not started (0 lessons completed)
        $s1 = User::factory()->create();
        Enrollment::create([
            'user_id' => $s1->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Student 2: Completed lesson 1 (50% progress)
        $s2 = User::factory()->create();
        Enrollment::create([
            'user_id' => $s2->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $s2->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Student 3: Completed both lessons (100% completed)
        $s3 = User::factory()->create();
        $enrollment3 = Enrollment::create([
            'user_id' => $s3->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(10),
            'completed_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $s3->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $s3->id,
            'lesson_id' => $lesson2->id,
            'completed' => true,
            'completed_at' => now(),
        ]);
        Certificate::create([
            'user_id' => $s3->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment3->id,
            'certificate_number' => 'CERT-TEST-999',
            'student_name' => $s3->name,
            'course_title' => $course->title,
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses.show', $course));
        $response->assertOk();
        $response->assertSee('Headline Formulation');
        $response->assertSee('Body Copy Strategy');
        $response->assertSee('1 issued'); // 1 certificate
    }

    // -------------------------------------------------------------
    // Filters & Sorting
    // -------------------------------------------------------------

    public function test_courses_can_be_filtered_by_status(): void
    {
        $publishedCourse = $this->createCourse([
            'title' => 'Visible Published Course',
            'status' => CourseStatus::PUBLISHED,
        ]);
        $draftCourse = $this->createCourse([
            'title' => 'Secret Draft Course',
            'status' => CourseStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses', ['status' => 'published']));
        $response->assertOk();
        $response->assertSee('Visible Published Course');
        $response->assertDontSee('Secret Draft Course');
    }

    public function test_courses_can_be_filtered_by_category(): void
    {
        $catA = CourseCategory::create(['name' => 'SEO Strategy', 'slug' => 'seo-strategy']);
        $catB = CourseCategory::create(['name' => 'Email Marketing', 'slug' => 'email-marketing']);

        $courseA = $this->createCourse([
            'title' => 'Mastering Technical SEO',
            'course_category_id' => $catA->id,
        ]);
        $courseB = $this->createCourse([
            'title' => 'Email Sequence Mastery',
            'course_category_id' => $catB->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses', ['category' => $catA->id]));
        $response->assertOk();
        $response->assertSee('Mastering Technical SEO');
        $response->assertDontSee('Email Sequence Mastery');
    }

    public function test_courses_can_be_searched_by_title(): void
    {
        $this->createCourse(['title' => 'Local SEO Playbook']);
        $this->createCourse(['title' => 'Facebook Ads Fastlane']);

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses', ['search' => 'Local SEO']));
        $response->assertOk();
        $response->assertSee('Local SEO Playbook');
        $response->assertDontSee('Facebook Ads Fastlane');
    }

    public function test_courses_can_be_sorted_by_revenue_and_enrollments(): void
    {
        $lowCourse = $this->createCourse(['title' => 'Low Enrolled Course']);
        $highCourse = $this->createCourse(['title' => 'High Enrolled Course']);

        // 5 enrollments for highCourse
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $highCourse->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.analytics.courses', ['sort' => 'enrollments_desc']));
        $response->assertOk();
        $response->assertSeeInOrder(['High Enrolled Course', 'Low Enrolled Course']);
    }
}