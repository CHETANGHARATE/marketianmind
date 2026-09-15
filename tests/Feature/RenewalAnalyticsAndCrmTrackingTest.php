<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ConversionEvent;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CourseRenewalNotificationService;
use App\Services\OrderFulfillmentService;
use App\Services\RenewalAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalAnalyticsAndCrmTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Course $courseA;
    private Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->courseA = Course::create([
            'title' => 'Growth Marketing Masterclass',
            'slug' => 'growth-marketing-masterclass',
            'short_description' => 'Master growth acquisition.',
            'description' => 'Comprehensive masterclass.',
            'price' => 5000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->courseB = Course::create([
            'title' => 'Performance SEO Mastery',
            'slug' => 'performance-seo-mastery',
            'short_description' => 'Master organic search.',
            'description' => 'SEO mastery course.',
            'price' => 3000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 1: RENEWAL ANALYTICS SERVICE — KPIS & CALCULATIONS
    |--------------------------------------------------------------------------
    */

    public function test_renewal_summary_accurately_counts_finite_and_excludes_legacy_lifetime(): void
    {
        // 1 finite enrollment
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays(335),
            'enrolled_at' => now()->subDays(30),
        ]);

        // 1 legacy lifetime enrollment (both null)
        $lifetimeStudent = User::factory()->create(['role' => UserRole::STUDENT]);
        Enrollment::create([
            'user_id' => $lifetimeStudent->id,
            'course_id' => $this->courseB->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => now()->subDays(100),
        ]);

        $service = app(RenewalAnalyticsService::class);
        $summary = $service->getRenewalSummary();

        $this->assertEquals(1, $summary['finite_students']);
        $this->assertEquals(1, $summary['lifetime_students']);
    }

    public function test_renewal_revenue_strictly_calculates_from_paid_renewals(): void
    {
        $orderFulfillment = app(OrderFulfillmentService::class);

        // Initial paid order (Course A, 5000 INR = 500000 paise)
        $initialOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-INIT-001',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'initial'],
        ]);
        $orderFulfillment->fulfillOrder($initialOrder);

        // Paid Renewal Order (Course A, 2500 INR = 250000 paise)
        $renewalOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-REN-001',
            'original_amount' => 250000,
            'discount_amount' => 0,
            'amount' => 250000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);
        $orderFulfillment->fulfillOrder($renewalOrder);

        // Pending Renewal Order (must be excluded from revenue)
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-REN-PENDING',
            'original_amount' => 250000,
            'discount_amount' => 0,
            'amount' => 250000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $service = app(RenewalAnalyticsService::class);
        $summary = $service->getRenewalSummary();

        // Total renewal revenue should be exactly 2500.00 INR (excluding initial 5000 INR and pending 2500 INR)
        $this->assertEquals(2500.00, $summary['renewal_revenue']);
        $this->assertEquals(1, $summary['paid_renewal_orders']);
        $this->assertStringContainsString('2,500.00', $summary['formatted_revenue']);
    }

    public function test_distinguishes_early_vs_post_expiry_renewals(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(300),
            'expires_at' => now()->addDays(65),
            'enrolled_at' => now()->subDays(300),
        ]);

        // Period 1: initial period (expires in 65 days)
        $period1 = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(300),
            'expires_at' => now()->addDays(65),
        ]);

        // Period 2: early renewal (created today while period1 expires in 65 days)
        $earlyPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'renewal',
            'starts_at' => now()->addDays(65),
            'expires_at' => now()->addDays(65 + 365),
        ]);

        $this->assertTrue($earlyPeriod->isEarlyRenewal());
        $this->assertFalse($earlyPeriod->isPostExpiryRenewal());

        // Now test post-expiry renewal on another student
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $enrollment2 = Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
            'enrolled_at' => now()->subDays(400),
        ]);

        $periodOld = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment2->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
        ]);

        $postExpiryPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment2->id,
            'period_type' => 'renewal',
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $this->assertFalse($postExpiryPeriod->isEarlyRenewal());
        $this->assertTrue($postExpiryPeriod->isPostExpiryRenewal());

        $service = app(RenewalAnalyticsService::class);
        $summary = $service->getRenewalSummary();

        $this->assertEquals(1, $summary['early_renewals']);
        $this->assertEquals(1, $summary['post_expiry_renewals']);
    }

    public function test_renewal_latency_metrics(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(350),
            'expires_at' => now()->addDays(15),
            'enrolled_at' => now()->subDays(350),
        ]);

        $period1 = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(350),
            'expires_at' => now()->addDays(15),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'renewal',
            'starts_at' => now()->addDays(15),
            'expires_at' => now()->addDays(380),
        ]);

        $service = app(RenewalAnalyticsService::class);
        $latency = $service->getRenewalLatencyMetrics();

        $this->assertGreaterThanOrEqual(14, $latency['avg_early_lead_days']);
        $this->assertEquals(1, $latency['early_count']);
    }

    public function test_per_course_renewal_metrics(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(360),
            'expires_at' => now()->addDays(5),
            'enrolled_at' => now()->subDays(360),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'renewal',
            'starts_at' => now()->addDays(5),
            'expires_at' => now()->addDays(370),
        ]);

        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CRSE-A-REN',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $service = app(RenewalAnalyticsService::class);
        $courseMetrics = $service->getCourseRenewalMetrics();

        $courseARow = $courseMetrics->firstWhere('id', $this->courseA->id);
        $this->assertNotNull($courseARow);
        $this->assertEquals(1, $courseARow['renewals_count']);
        $this->assertEquals(5000.00, $courseARow['renewal_revenue']);
        $this->assertEquals(100.0, $courseARow['renewal_rate']);
    }

    public function test_expiry_cohort_analysis_groups_by_month_and_excludes_lifetime(): void
    {
        $pastExpiry = now()->subMonths(2)->startOfMonth()->addDays(10);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => $pastExpiry->copy()->subYear(),
            'expires_at' => $pastExpiry,
            'enrolled_at' => $pastExpiry->copy()->subYear(),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $pastExpiry->copy()->subYear(),
            'expires_at' => $pastExpiry,
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'renewal',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $service = app(RenewalAnalyticsService::class);
        $cohorts = $service->getExpiryCohortReport(4);

        $this->assertCount(4, $cohorts);
        $targetMonthKey = $pastExpiry->format('Y-m');
        $targetCohort = collect($cohorts)->firstWhere('month_key', $targetMonthKey);

        $this->assertNotNull($targetCohort);
        $this->assertEquals(1, $targetCohort['cohort_size']);
        $this->assertEquals(1, $targetCohort['renewals_count']);
        $this->assertEquals(100.0, $targetCohort['renewal_rate']);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 2: EVENT TRACKING & CONVERSION SERVICE INTEGRATION
    |--------------------------------------------------------------------------
    */

    public function test_checkout_tracks_renewal_checkout_started(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CHECKOUT-TEST',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.checkout', $order));

        $response->assertOk();

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'renewal_checkout_started',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);
    }

    public function test_fulfillment_tracks_renewal_fulfilled_and_course_access_renewed(): void
    {
        // First enroll student
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(300),
            'expires_at' => now()->addDays(65),
            'enrolled_at' => now()->subDays(300),
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-FULFILL-TEST',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'renewal_fulfilled',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'course_access_renewed',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);
    }

    public function test_notification_service_tracks_renewal_notification_sent_and_expiring(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        $notifService = app(CourseRenewalNotificationService::class);
        $result = $notifService->sendExpiringSoon($enrollment, 7);

        $this->assertTrue($result);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'renewal_notification_sent',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'course_access_expiring',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);
    }

    public function test_notification_service_tracks_course_access_expired(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(366),
            'expires_at' => now()->subDay(),
            'enrolled_at' => now()->subDays(366),
        ]);

        $notifService = app(CourseRenewalNotificationService::class);
        $result = $notifService->sendAccessExpired($enrollment);

        $this->assertTrue($result);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => 'course_access_expired',
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 3: MINI CRM INTEGRATION & LIFECYCLE TRACKING
    |--------------------------------------------------------------------------
    */

    public function test_matched_lead_records_activity_on_renewal_events_without_changing_status(): void
    {
        // Create an existing CRM lead linked to student
        $lead = Lead::create([
            'name' => $this->student->name,
            'email' => $this->student->email,
            'phone' => '9876543210',
            'source' => 'website',
            'status' => LeadStatus::CONVERTED->value,
            'priority' => LeadPriority::HIGH->value,
            'course_id' => $this->courseA->id,
            'converted_user_id' => $this->student->id,
        ]);

        $initialLeadStatus = $lead->status;

        // 1. Trigger Renewal Reminder
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        app(CourseRenewalNotificationService::class)->sendExpiringSoon($enrollment, 7);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'renewal_reminder_sent',
        ]);

        // 2. Trigger Renewal Checkout Started
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CRM-TEST',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $this->actingAs($this->student)->get(route('student.courses.checkout', $order));

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'renewal_checkout_started',
        ]);

        // 3. Trigger Renewal Fulfillment
        $order->update(['status' => OrderStatus::PAID]);
        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'course_renewed',
        ]);

        // Assert lead status was NEVER modified or corrupted
        $lead->refresh();
        $this->assertEquals($initialLeadStatus, $lead->status);
    }

    public function test_crm_tracking_handles_unmatched_student_gracefully(): void
    {
        // Existing enrollment for student
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(300),
            'expires_at' => now()->addDays(65),
            'enrolled_at' => now()->subDays(300),
        ]);

        // Student has NO matching lead in leads table
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-NO-LEAD',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        // Should complete cleanly without exception
        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $this->assertDatabaseHas('course_access_periods', [
            'order_id' => $order->id,
            'period_type' => 'renewal',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 4: ADMIN CONTROLLERS, ROUTES & HTTP RESPONSES
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_access_renewal_reports_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.renewals'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.renewals');
        $response->assertViewHas(['dateFilter', 'summary', 'courseMetrics', 'cohorts', 'latency', 'funnel', 'recentRenewals']);
        $response->assertSee('Renewal Analytics &amp; Lifecycle', false);
        $response->assertSee('Course Catalog Renewal Performance', false);
        $response->assertSee('Expiry Cohort Analysis', false);
    }

    public function test_non_admin_cannot_access_renewal_reports(): void
    {
        // Unauthenticated
        $this->get(route('admin.reports.renewals'))
            ->assertRedirect(route('login'));

        // Student
        $this->actingAs($this->student)
            ->get(route('admin.reports.renewals'))
            ->assertForbidden();
    }

    public function test_admin_can_export_renewals_csv(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(360),
            'expires_at' => now()->addDays(5),
            'enrolled_at' => now()->subDays(360),
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-CSV-REN',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'renewal',
            'starts_at' => now()->addDays(5),
            'expires_at' => now()->addDays(370),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.export.renewals'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_dashboard_displays_renewal_metrics_card(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Renewals', false);
        $response->assertViewHas('metrics', function ($metrics) {
            return isset($metrics['renewals_this_month'])
                && isset($metrics['renewal_revenue_this_month'])
                && isset($metrics['formatted_renewal_revenue'])
                && isset($metrics['expiring_soon']);
        });
    }

    public function test_admin_course_analytics_show_displays_renewal_card(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.courses.show', $this->courseA));

        $response->assertOk();
        $response->assertSee('Access Validity &amp; Renewal Performance', false);
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['renewals_count'])
                && isset($stats['renewal_revenue'])
                && isset($stats['renewal_rate'])
                && isset($stats['finite_enrollments']);
        });
    }

    public function test_bundles_and_admin_grants_are_excluded_from_renewal_revenue(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
            'enrolled_at' => now(),
        ]);

        // Admin grant access period (no order, period_type = admin_grant)
        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => null,
            'period_type' => 'admin_grant',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Bundle order (must never be renewal revenue)
        $bundle = \App\Models\Bundle::create([
            'title' => 'Premium Bundle',
            'slug' => 'premium-bundle',
            'description' => 'Test bundle',
            'price' => 9999.00,
            'status' => \App\Enums\BundleStatus::PUBLISHED,
        ]);

        Order::create([
            'user_id' => $this->student->id,
            'course_id' => null,
            'bundle_id' => $bundle->id,
            'order_number' => 'ORD-BUNDLE-01',
            'original_amount' => 999900,
            'discount_amount' => 0,
            'amount' => 999900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'metadata' => ['purchase_type' => 'bundle'],
        ]);

        $service = app(RenewalAnalyticsService::class);
        $summary = $service->getRenewalSummary();

        // Renewal revenue must be 0
        $this->assertEquals(0.00, $summary['renewal_revenue']);
        $this->assertEquals(0, $summary['paid_renewal_orders']);
    }

    public function test_renewal_reports_support_date_range_filtering(): void
    {
        // Renewal order from 45 days ago
        $oldOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-OLD-REN',
            'original_amount' => 200000,
            'discount_amount' => 0,
            'amount' => 200000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'created_at' => now()->subDays(45),
            'paid_at' => now()->subDays(45),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        // Renewal order from 3 days ago
        $recentOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'ORD-REC-REN',
            'original_amount' => 300000,
            'discount_amount' => 0,
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'created_at' => now()->subDays(3),
            'paid_at' => now()->subDays(3),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $service = app(RenewalAnalyticsService::class);

        // 7 days filter: only recent order should count
        $sevenDaysSummary = $service->getRenewalSummary(now()->subDays(7)->startOfDay(), now()->endOfDay());
        $this->assertEquals(3000.00, $sevenDaysSummary['renewal_revenue']);
        $this->assertEquals(1, $sevenDaysSummary['paid_renewal_orders']);

        // All time filter: both orders should count
        $allTimeSummary = $service->getRenewalSummary();
        $this->assertEquals(5000.00, $allTimeSummary['renewal_revenue']);
        $this->assertEquals(2, $allTimeSummary['paid_renewal_orders']);
    }
}
