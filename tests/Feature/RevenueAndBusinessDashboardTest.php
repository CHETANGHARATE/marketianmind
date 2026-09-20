<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueAndBusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;
    protected Course $courseA;
    protected Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin_test@marketianmind.com',
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'email' => 'student_test@marketianmind.com',
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
        ]);

        $this->courseA = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Growth Hacking Masterclass',
            'slug' => 'growth-hacking-masterclass',
            'short_description' => 'Scale your revenue quickly',
            'price' => 5000.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->courseB = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Copywriting Essentials',
            'slug' => 'copywriting-essentials',
            'short_description' => 'High-converting copy',
            'price' => 3000.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test authorization: guest redirected, non-admin gets 403.
     */
    public function test_dashboard_access_control(): void
    {
        // Unauthenticated
        $guestRes = $this->get(route('admin.dashboard'));
        $guestRes->assertRedirect(route('login'));

        // Non-admin (Student)
        $studentRes = $this->actingAs($this->student)->get(route('admin.dashboard'));
        $studentRes->assertForbidden();

        // Admin
        $adminRes = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminRes->assertOk();
    }

    /**
     * Test verified revenue metrics strictly include paid orders and exclude non-paid orders.
     */
    public function test_revenue_metrics_count_strictly_verified_paid_orders(): void
    {
        // Paid order with coupon discount: price 5000, discount 1000, paid 4000 (in paise: 400000)
        $paidOrder1 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-REV-001',
            'amount' => 400000,
            'discount_amount' => 100000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Second paid order: 3000 (in paise: 300000)
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $paidOrder2 = Order::create([
            'user_id' => $student2->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'ORD-REV-002',
            'amount' => 300000,
            'discount_amount' => 0,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Pending order (should be excluded from revenue)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'ORD-REV-PENDING',
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Failed order (should be excluded from revenue)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-REV-FAILED',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $revenueMetrics = $response->viewData('revenueMetrics');

        // Net revenue: 4000 + 3000 = 7000.00
        $this->assertEquals(7000.00, $revenueMetrics['verified_net_revenue']);
        $this->assertEquals('₹7,000.00', $revenueMetrics['formatted_net_revenue']);

        // Gross sales: 4000 + 1000 (discount) + 3000 = 8000.00
        $this->assertEquals(8000.00, $revenueMetrics['verified_gross_sales']);
        $this->assertEquals(1000.00, $revenueMetrics['total_discounts']);

        // Orders breakdown
        $this->assertEquals(2, $revenueMetrics['paid_orders_count']);
        $this->assertEquals(1, $revenueMetrics['pending_orders_count']);
        $this->assertEquals(1, $revenueMetrics['failed_orders_count']);

        // AOV: 7000 / 2 = 3500.00
        $this->assertEquals(3500.00, $revenueMetrics['average_order_value']);
        $this->assertEquals('₹3,500.00', $revenueMetrics['formatted_aov']);
    }

    /**
     * Test date range filtering correctly bounds orders and revenue.
     */
    public function test_date_range_filtering_filters_revenue_and_orders_accurately(): void
    {
        Carbon::setTestNow('2026-03-20 14:00:00');

        // Order today: 2000
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-TODAY',
            'amount' => 200000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        // Order 5 days ago: 3000
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'ORD-5DAYS',
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Order 45 days ago: 4000
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-45DAYS',
            'amount' => 400000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now()->subDays(45),
            'created_at' => now()->subDays(45),
        ]);

        // 1. Filter: 'today'
        $todayRes = $this->actingAs($this->admin)->get(route('admin.dashboard', ['date_range' => 'today']));
        $todayRes->assertOk();
        $todayMetrics = $todayRes->viewData('revenueMetrics');
        $this->assertEquals(2000.00, $todayMetrics['verified_net_revenue']);
        $this->assertEquals(1, $todayMetrics['paid_orders_count']);

        // 2. Filter: '7d' (should include today + 5 days ago = 5000)
        $sevenDaysRes = $this->actingAs($this->admin)->get(route('admin.dashboard', ['date_range' => '7d']));
        $sevenDaysRes->assertOk();
        $sevenDaysMetrics = $sevenDaysRes->viewData('revenueMetrics');
        $this->assertEquals(5000.00, $sevenDaysMetrics['verified_net_revenue']);
        $this->assertEquals(2, $sevenDaysMetrics['paid_orders_count']);

        // 3. Filter: 'all' (should include all 3 = 9000)
        $allRes = $this->actingAs($this->admin)->get(route('admin.dashboard', ['date_range' => 'all']));
        $allRes->assertOk();
        $allMetrics = $allRes->viewData('revenueMetrics');
        $this->assertEquals(9000.00, $allMetrics['verified_net_revenue']);
        $this->assertEquals(3, $allMetrics['paid_orders_count']);

        // 4. Custom range
        $customRes = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'date_range' => 'custom',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-18',
        ]));
        $customRes->assertOk();
        $customMetrics = $customRes->viewData('revenueMetrics');
        // Only 5 days ago (March 15) is in this range
        $this->assertEquals(3000.00, $customMetrics['verified_net_revenue']);
        $this->assertEquals(1, $customMetrics['paid_orders_count']);

        Carbon::setTestNow();
    }

    /**
     * Test first-time versus returning customer segmentation.
     */
    public function test_first_time_versus_returning_buyer_segmentation(): void
    {
        Carbon::setTestNow('2026-03-20 12:00:00');

        $buyerNew = User::factory()->create(['role' => UserRole::STUDENT]);
        $buyerRepeat = User::factory()->create(['role' => UserRole::STUDENT]);

        // BuyerRepeat had an order 60 days ago
        Order::create([
            'user_id' => $buyerRepeat->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-PAST-01',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now()->subDays(60),
        ]);

        // In the current 30d period:
        // 1. BuyerNew makes their very first purchase (3000)
        Order::create([
            'user_id' => $buyerNew->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'ORD-CURRENT-NEW',
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now()->subDays(5),
        ]);

        // 2. BuyerRepeat buys another course (5000)
        Order::create([
            'user_id' => $buyerRepeat->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CURRENT-REPEAT',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', ['date_range' => '30d']));

        $response->assertOk();
        $revenueMetrics = $response->viewData('revenueMetrics');

        $this->assertEquals(2, $revenueMetrics['unique_paying_customers']);
        $this->assertEquals(1, $revenueMetrics['first_time_buyers_count']);
        $this->assertEquals(1, $revenueMetrics['returning_buyers_count']);
        $this->assertEquals(3000.00, $revenueMetrics['first_time_buyers_revenue']);
        $this->assertEquals(5000.00, $revenueMetrics['returning_buyers_revenue']);

        Carbon::setTestNow();
    }

    /**
     * Test course-level performance aggregates sales, unique buyers, and completion accurately.
     */
    public function test_course_level_performance_aggregates_sales_and_academic_metrics(): void
    {
        $studentA = User::factory()->create(['role' => UserRole::STUDENT]);
        $studentB = User::factory()->create(['role' => UserRole::STUDENT]);

        // Paid order for Course A
        Order::create([
            'user_id' => $studentA->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CA-1',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Enrollments for Course A: 1 Active, 1 Completed
        Enrollment::create([
            'user_id' => $studentA->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
            'starts_at' => now()->subDays(1),
            'expires_at' => now()->addDays(364),
        ]);
        Enrollment::create([
            'user_id' => $studentB->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(10),
            'completed_at' => now(),
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(355),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $coursePerformance = $response->viewData('coursePerformance');

        $perfA = $coursePerformance->firstWhere('id', $this->courseA->id);
        $this->assertNotNull($perfA);
        $this->assertEquals('Growth Hacking Masterclass', $perfA['title']);
        $this->assertEquals(1, $perfA['paid_orders_count']);
        $this->assertEquals(5000.00, $perfA['revenue']);
        $this->assertEquals(1, $perfA['unique_buyers_count']);
        $this->assertEquals(2, $perfA['active_access_count']); // Active + Completed with valid dates
        $this->assertEquals(1, $perfA['completed_enrollments_count']);
        $this->assertEquals(50.0, $perfA['completion_rate']); // 1 completed out of 2 total = 50%
    }

    /**
     * Test operational health detects unfulfilled paid orders requiring attention.
     */
    public function test_operational_health_detects_unfulfilled_paid_orders(): void
    {
        // 1. Create a paid order WITHOUT access periods
        $unfulfilledOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-UNFULFILLED',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $health = $response->viewData('operationalHealth');

        $this->assertEquals(1, $health['unfulfilled_orders_count']);
        $response->assertSee('Action Required');
        $response->assertSee('1 paid order(s) require access period fulfillment!');

        // 2. Fulfill the order by creating an Enrollment and CourseAccessPeriod
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $unfulfilledOrder->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $resolvedResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $resolvedResponse->assertOk();
        $resolvedHealth = $resolvedResponse->viewData('operationalHealth');

        $this->assertEquals(0, $resolvedHealth['unfulfilled_orders_count']);
        $resolvedResponse->assertSee('Healthy');
        $resolvedResponse->assertSee('All paid student orders are 100% fulfilled with valid access periods.');
    }

    /**
     * Test operational health logs recent failed payments with diagnosis.
     */
    public function test_operational_health_logs_failed_payments(): void
    {
        $failedOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-FAILED-DIAG',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        Payment::create([
            'order_id' => $failedOrder->id,
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'razorpay_payment_id' => 'pay_failed_xyz123',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
            'failure_description' => 'Payment declined by issuer bank (insufficient funds)',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $health = $response->viewData('operationalHealth');

        $this->assertEquals(1, $health['failed_payments_count']);
        $this->assertCount(1, $health['recent_failed_payments']);
        $this->assertEquals('Payment declined by issuer bank (insufficient funds)', $health['recent_failed_payments'][0]['error_description']);

        $response->assertSee('Payment declined by issuer bank (insufficient funds)');
        $response->assertSee('ORD-FAILED-DIAG');
    }

    /**
     * Test lead-to-purchase funnel and attribution reporting.
     */
    public function test_lead_funnel_and_attribution_pipeline(): void
    {
        // 1. General lead
        Lead::create([
            'name' => 'General Inquirer',
            'email' => 'general@example.com',
            'source' => 'google_search',
            'status' => LeadStatus::NEW,
            'priority' => LeadPriority::MEDIUM,
        ]);

        // 2. Course-associated lead
        Lead::create([
            'name' => 'Interested in Growth',
            'email' => 'growth_lead@example.com',
            'course_id' => $this->courseA->id,
            'source' => 'linkedin_ads',
            'status' => LeadStatus::CONTACTED,
            'priority' => LeadPriority::HIGH,
        ]);

        // 3. Converted lead
        Lead::create([
            'name' => 'Converted Student',
            'email' => 'converted@example.com',
            'course_id' => $this->courseA->id,
            'source' => 'linkedin_ads',
            'status' => LeadStatus::CONVERTED,
            'priority' => LeadPriority::HIGH,
            'converted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $leadFunnel = $response->viewData('leadFunnel');

        $this->assertEquals(3, $leadFunnel['period_leads_count']);
        $this->assertEquals(2, $leadFunnel['course_leads_count']);
        $this->assertEquals(1, $leadFunnel['converted_leads_count']);
        $this->assertEquals(33.3, $leadFunnel['conversion_rate']); // 1 / 3 = 33.3%

        // Top sources should list linkedin_ads as top with 2 leads
        $topSource = $leadFunnel['top_sources'][0] ?? null;
        $this->assertNotNull($topSource);
        $this->assertEquals('linkedin_ads', $topSource['source']);
        $this->assertEquals(2, $topSource['count']);
    }
}
