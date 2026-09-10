<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'name' => 'John Student',
            'email' => 'student@marketianmind.com',
            'role' => UserRole::STUDENT,
        ]);
    }

    /**
     * Test guest cannot access admin dashboard and is redirected to login.
     */
    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot access admin dashboard and receives 403 Forbidden.
     */
    public function test_student_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    /**
     * Test authorized admin can access the dashboard.
     */
    public function test_authorized_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Total Revenue');
        $response->assertSee('Total Students');
        $response->assertSee('Total Courses');
    }

    /**
     * Test empty dashboard handles zero data gracefully without crashing.
     */
    public function test_dashboard_renders_empty_state_gracefully(): void
    {
        // Delete the extra student so only the admin exists
        $this->student->delete();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('₹0.00');
        $response->assertSee('No orders recorded yet');
        $response->assertSee('No students registered yet');
        $response->assertSee('No enrollments yet');
        $response->assertSee('No courses available');
    }

    /**
     * Test student metrics count only users with student role and ignore admins.
     */
    public function test_student_metrics_count_only_students(): void
    {
        // Create 3 additional students and 2 additional admins
        User::factory()->count(3)->create(['role' => UserRole::STUDENT]);
        User::factory()->count(2)->create(['role' => UserRole::ADMIN]);

        // Total students: $this->student (1) + 3 created = 4 students (admins excluded)
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(4, $metrics['total_students']);
        $this->assertEquals(4, $metrics['new_students_30d']);
    }

    /**
     * Test course metrics correctly separate published vs draft courses.
     */
    public function test_course_metrics_separate_published_and_draft_courses(): void
    {
        $category = CourseCategory::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
        ]);

        // 2 Published courses
        Course::create([
            'course_category_id' => $category->id,
            'title' => 'Course 1',
            'slug' => 'course-1',
            'short_description' => 'Course 1 short description',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
        Course::create([
            'course_category_id' => $category->id,
            'title' => 'Course 2',
            'slug' => 'course-2',
            'short_description' => 'Course 2 short description',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
            'featured' => true,
        ]);

        // 1 Draft course
        Course::create([
            'course_category_id' => $category->id,
            'title' => 'Draft Course',
            'slug' => 'draft-course',
            'short_description' => 'Draft course short description',
            'price' => 999.00,
            'status' => CourseStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(3, $metrics['total_courses']);
        $this->assertEquals(2, $metrics['published_courses']);
        $this->assertEquals(1, $metrics['draft_courses']);
        $this->assertEquals(1, $metrics['featured_courses']);
    }

    /**
     * Test enrollment metrics accurately track total, active, and completed enrollments.
     */
    public function test_enrollment_metrics_track_active_and_completed_enrollments(): void
    {
        $category = CourseCategory::create(['name' => 'SEO', 'slug' => 'seo']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'SEO Mastery',
            'slug' => 'seo-mastery',
            'short_description' => 'SEO course short description',
            'status' => CourseStatus::PUBLISHED,
        ]);

        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $student3 = User::factory()->create(['role' => UserRole::STUDENT]);

        // 2 Active enrollments
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // 1 Completed enrollment
        Enrollment::create([
            'user_id' => $student3->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(5),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(3, $metrics['total_enrollments']);
        $this->assertEquals(2, $metrics['active_enrollments']);
        $this->assertEquals(1, $metrics['completed_enrollments']);
        $this->assertEquals(33.3, $metrics['completion_rate']);
    }

    /**
     * Test order metrics accurately track total, paid, pending, and failed orders.
     */
    public function test_order_metrics_track_orders_by_status(): void
    {
        $category = CourseCategory::create(['name' => 'Ads', 'slug' => 'ads']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'FB Ads',
            'slug' => 'fb-ads',
            'short_description' => 'FB Ads short description',
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // 2 Paid orders
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1001',
            'amount' => 499900, // in paise
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1002',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // 1 Pending order
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1003',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // 1 Failed order
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-1004',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(4, $metrics['total_orders']);
        $this->assertEquals(2, $metrics['paid_orders']);
        $this->assertEquals(1, $metrics['pending_orders']);
        $this->assertEquals(1, $metrics['failed_orders']);
    }

    /**
     * Test revenue calculation strictly sums only paid orders and converts paise to INR correctly.
     */
    public function test_revenue_calculates_strictly_paid_orders_and_formats_inr(): void
    {
        $category = CourseCategory::create(['name' => 'Email', 'slug' => 'email']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Email Marketing',
            'slug' => 'email-marketing',
            'short_description' => 'Email Marketing short description',
            'price' => 1250.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Paid order: ₹1,250.00 = 125000 paise
        $order1 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-REV-1',
            'amount' => 125000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Paid order: ₹2,500.50 = 250050 paise
        $order2 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-REV-2',
            'amount' => 250050,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Unpaid pending order: ₹10,000.00 = 1000000 paise (MUST NOT be counted)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-REV-3',
            'amount' => 1000000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Failed order: ₹5,000.00 (MUST NOT be counted)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-REV-4',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        // Multiple payment attempts for order 1 should not inflate revenue
        Payment::create([
            'order_id' => $order1->id,
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'razorpay_payment_id' => 'pay_attempt_1',
            'amount' => 125000,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
        ]);
        Payment::create([
            'order_id' => $order1->id,
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'razorpay_payment_id' => 'pay_attempt_2',
            'amount' => 125000,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'captured' => true,
        ]);

        // Total expected revenue = 1250.00 + 2500.50 = 3750.50 INR
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(3750.50, $metrics['total_revenue']);
        $this->assertEquals('₹3,750.50', $metrics['formatted_revenue']);
        $response->assertSee('₹3,750.50');
    }

    /**
     * Test recent students feed contains only students and is limited to latest 5.
     */
    public function test_recent_students_feed_is_limited_and_excludes_admins(): void
    {
        // Create 6 students
        for ($i = 1; $i <= 6; $i++) {
            User::factory()->create([
                'name' => "Student $i",
                'email' => "student$i@test.com",
                'role' => UserRole::STUDENT,
                'created_at' => now()->addMinutes($i),
            ]);
        }

        // Create an admin recently
        User::factory()->create([
            'name' => 'Extra Admin',
            'email' => 'extra_admin@test.com',
            'role' => UserRole::ADMIN,
            'created_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $recentStudents = $response->viewData('recentStudents');
        $this->assertCount(5, $recentStudents);

        // Verify none of the items in recentStudents is an admin
        foreach ($recentStudents as $student) {
            $this->assertNotEquals('extra_admin@test.com', $student->email);
        }

        // Latest student (Student 6) should be first
        $this->assertEquals('Student 6', $recentStudents->first()->name);
    }

    /**
     * Test recent orders feed loads relationships and is limited to latest 5.
     */
    public function test_recent_orders_feed_is_limited_and_loads_relationships(): void
    {
        $category = CourseCategory::create(['name' => 'Funnels', 'slug' => 'funnels']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Funnel Design',
            'slug' => 'funnel-design',
            'short_description' => 'Funnel Design short description',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $order = Order::create([
                'user_id' => $this->student->id,
                'course_id' => $course->id,
                'order_number' => "ORD-TEST-00$i",
                'amount' => 99900,
                'currency' => 'INR',
                'status' => OrderStatus::PAID,
            ]);
            $order->created_at = now()->addMinutes($i);
            $order->save();
        }

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $recentOrders = $response->viewData('recentOrders');
        $this->assertCount(5, $recentOrders);

        // Verify relations loaded without extra queries
        $this->assertTrue($recentOrders->first()->relationLoaded('user'));
        $this->assertTrue($recentOrders->first()->relationLoaded('course'));
        $this->assertEquals('ORD-TEST-007', $recentOrders->first()->order_number);
    }

    /**
     * Test recent enrollments feed is limited and loads relations.
     */
    public function test_recent_enrollments_feed_is_limited_and_loads_relations(): void
    {
        $category = CourseCategory::create(['name' => 'Branding', 'slug' => 'branding']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Branding 101',
            'slug' => 'branding-101',
            'short_description' => 'Branding 101 short description',
            'status' => CourseStatus::PUBLISHED,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $st = User::factory()->create(['role' => UserRole::STUDENT]);
            $enrollment = Enrollment::create([
                'user_id' => $st->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now()->addMinutes($i),
            ]);
            $enrollment->created_at = now()->addMinutes($i);
            $enrollment->save();
        }

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $recentEnrollments = $response->viewData('recentEnrollments');
        $this->assertCount(5, $recentEnrollments);
        $this->assertTrue($recentEnrollments->first()->relationLoaded('user'));
        $this->assertTrue($recentEnrollments->first()->relationLoaded('course'));
    }

    /**
     * Test course performance overview displays course stats with enrollment counts.
     */
    public function test_courses_overview_displays_enrollment_counts(): void
    {
        $category = CourseCategory::create(['name' => 'Content', 'slug' => 'content']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Content Strategy',
            'slug' => 'content-strategy',
            'short_description' => 'Content Strategy short description',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Add 2 enrollments: 1 active, 1 completed
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        $st2 = User::factory()->create(['role' => UserRole::STUDENT]);
        Enrollment::create([
            'user_id' => $st2->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(2),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $coursesOverview = $response->viewData('coursesOverview');
        $this->assertCount(1, $coursesOverview);

        $firstCourse = $coursesOverview->first();
        $this->assertEquals('Content Strategy', $firstCourse->title);
        $this->assertEquals(2, $firstCourse->enrollments_count);
        $this->assertEquals(1, $firstCourse->completed_enrollments_count);
    }

    /**
     * Test existing admin course management remains functional.
     */
    public function test_existing_admin_course_management_remains_functional(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.courses.index'));
        $response->assertOk();
    }

    /**
     * Test existing student dashboard remains functional.
     */
    public function test_existing_student_dashboard_remains_functional(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
    }

    /**
     * Test existing admin orders management remains functional.
     */
    public function test_existing_admin_orders_management_remains_functional(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertOk();
    }

    /**
     * Test student cannot access admin order inspector or dashboard data via URL manipulation.
     */
    public function test_student_cannot_access_admin_orders(): void
    {
        $category = CourseCategory::create(['name' => 'Tech', 'slug' => 'tech']);
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Tech Course',
            'slug' => 'tech-course',
            'short_description' => 'Tech course short description',
            'price' => 1000.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-SEC-01',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->student)->get(route('admin.orders.show', $order));
        $response->assertForbidden();
    }
}