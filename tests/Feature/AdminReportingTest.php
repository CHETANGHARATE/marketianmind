<?php

namespace Tests\Feature;

use App\Enums\CouponDiscountType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course1;
    protected Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course1 = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery-' . uniqid(),
            'short_description' => 'Marketing masterclass',
            'price' => 5000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->course2 = Course::create([
            'title' => 'SEO Fundamentals',
            'slug' => 'seo-fundamentals-' . uniqid(),
            'short_description' => 'SEO fundamentals course',
            'price' => 3000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get(route('admin.reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.reports.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_reports_executive_overview(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('Business Reports & Analytics');
        $response->assertSee('Executive Overview');
        $response->assertSee('Net Collected Revenue');
    }

    public function test_admin_can_filter_reports_by_date_range(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index', ['range' => '7d']));

        $response->assertOk();
        $response->assertSee('Last 7 Days');
    }

    public function test_sales_report_calculates_authoritative_revenue(): void
    {
        // 1 Paid order of 5000 INR (500000 paise), 500 INR discount
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-REV-1',
            'original_amount' => 550000,
            'discount_amount' => 50000,
            'amount' => 500000, // 5000 INR net
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.sales', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee('5,000.00');
    }

    public function test_sales_report_excludes_unpaid_orders_from_revenue(): void
    {
        // Paid order: 3000 INR
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'order_number' => 'ORD-PAID',
            'original_amount' => 300000,
            'discount_amount' => 0,
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Unpaid pending order: 9999 INR (must NOT count as revenue)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-PENDING',
            'original_amount' => 999900,
            'discount_amount' => 0,
            'amount' => 999900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.sales', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee('3,000.00');
        $response->assertDontSee('12,999.00');
    }

    public function test_course_performance_report_calculates_revenue_and_completions(): void
    {
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-CRS-1',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(5),
            'completed_at' => now(),
        ]);

        Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'CERT-TEST-123',
            'course_title' => $this->course1->title,
            'student_name' => $this->student->name,
            'instructor_name' => 'Instructor',
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.courses', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee($this->course1->title);
        $response->assertSee('5,000.00');
        $response->assertSee('100%');
    }

    public function test_coupon_performance_report_calculates_discounts_and_usages(): void
    {
        $coupon = Coupon::create([
            'code' => 'GROWTH50',
            'name' => 'Growth 50%',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 50,
            'is_active' => true,
        ]);

        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'order_number' => 'ORD-CPN-1',
            'original_amount' => 500000,
            'discount_amount' => 250000,
            'amount' => 250000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.coupons', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee('GROWTH50');
        $response->assertSee('2,500.00');
    }

    public function test_enrollment_report_calculates_completion_rate(): void
    {
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);

        // Student 1 completed
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(2),
            'completed_at' => now(),
        ]);

        // Student 2 active (not completed)
        Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.enrollments', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee('50%'); // 1 completed out of 2 = 50%
        $response->assertSee('Enrollments & Completion Report');
    }

    public function test_admin_can_export_sales_csv(): void
    {
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-EXPORT-1',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.sales'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_courses_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.courses'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_enrollments_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.enrollments'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}