<?php

namespace Tests\Feature;

use App\Enums\BundleStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bundle;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CoursePaymentFulfillmentAndAccessHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Growth Marketing Masterclass',
            'slug' => 'growth-marketing-masterclass',
            'short_description' => 'Master growth acquisition.',
            'description' => 'Full masterclass.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 1: INITIAL PURCHASE FULFILLMENT TESTS
    |--------------------------------------------------------------------------
    */

    public function test_initial_course_purchase_creates_one_enrollment_and_one_access_period(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-INIT-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_initial_123',
        ]);

        $razorpayMock = Mockery::mock(RazorpayService::class);
        $razorpayMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_initial_123',
                'razorpay_payment_id' => 'pay_initial_123',
                'razorpay_signature' => 'valid_signature_hash',
            ]);

        $response->assertRedirect(route('payment.success', $order));

        // 1. Order marked paid and fulfilled
        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertTrue($order->isFulfilled());
        $this->assertNotNull($order->metadata['fulfilled_at']);

        // 2. Exactly one enrollment created for user and course
        $enrollments = Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->get();
        $this->assertCount(1, $enrollments);

        $enrollment = $enrollments->first();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        $this->assertEquals('2026-09-14 10:00:00', $enrollment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $enrollment->expires_at->format('Y-m-d H:i:s'));

        // 3. Exactly one access period created
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->get();
        $this->assertCount(1, $periods);

        $period = $periods->first();
        $this->assertEquals('initial', $period->period_type);
        $this->assertEquals($order->id, $period->order_id);
        $this->assertEquals('2026-09-14 10:00:00', $period->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $period->expires_at->format('Y-m-d H:i:s'));

        // 4. Access is active
        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertTrue($this->student->hasActiveAccessTo($this->course));
    }

    public function test_initial_purchase_uses_course_access_validity_days_configuration(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $shortCourse = Course::create([
            'title' => 'Short Bootcamp',
            'slug' => 'short-bootcamp',
            'short_description' => 'Fast bootcamp.',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 90, // 90 days validity
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $shortCourse->id,
            'order_number' => 'MM-ORD-SHORT-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $enrollment = Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $shortCourse->id)
            ->firstOrFail();

        $expectedExpiry = Carbon::parse('2026-09-14 10:00:00')->addDays(90);
        $this->assertEquals($expectedExpiry->format('Y-m-d H:i:s'), $enrollment->expires_at->format('Y-m-d H:i:s'));

        $period = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->firstOrFail();
        $this->assertEquals($expectedExpiry->format('Y-m-d H:i:s'), $period->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_failed_payment_verification_grants_no_access_period(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-FAIL-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_fail_123',
        ]);

        $razorpayMock = Mockery::mock(RazorpayService::class);
        $razorpayMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(false);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_fail_123',
                'razorpay_payment_id' => 'pay_fail_123',
                'razorpay_signature' => 'invalid_signature',
            ]);

        $response->assertRedirect(route('payment.failed', $order));

        $order->refresh();
        $this->assertTrue($order->isFailed());
        $this->assertFalse($order->isFulfilled());

        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
        $this->assertEquals(0, CourseAccessPeriod::count());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 2: EARLY RENEWAL FULFILLMENT TESTS
    |--------------------------------------------------------------------------
    */

    public function test_early_renewal_extends_from_existing_expiry_without_losing_days(): void
    {
        // Initial purchase was on 2026-09-14, valid until 2027-09-14
        $initialStartsAt = Carbon::parse('2026-09-14 10:00:00');
        $initialExpiresAt = Carbon::parse('2027-09-14 10:00:00');

        $initialOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-INIT-002',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => $initialStartsAt,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $initialStartsAt,
            'expires_at' => $initialExpiresAt,
            'enrolled_at' => $initialStartsAt,
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $initialOrder->id,
            'period_type' => 'initial',
            'starts_at' => $initialStartsAt,
            'expires_at' => $initialExpiresAt,
        ]);

        // Student renews early on 2027-08-25 (20 days before expiry)
        Carbon::setTestNow('2027-08-25 14:30:00');

        $renewalOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-REN-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($renewalOrder);

        // Verify: Still exactly ONE enrollment row
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        // Expiry extended by exactly 365 days from old expiry: 2028-09-14
        $expectedNewExpiresAt = $initialExpiresAt->copy()->addDays(365);
        $this->assertEquals($expectedNewExpiresAt->format('Y-m-d H:i:s'), $enrollment->expires_at->format('Y-m-d H:i:s'));

        // Verify: Exactly TWO access periods in chronological history
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)
            ->orderBy('starts_at', 'asc')
            ->get();
        $this->assertCount(2, $periods);

        // Period 1 (initial) remains intact
        $this->assertEquals('initial', $periods[0]->period_type);
        $this->assertEquals($initialOrder->id, $periods[0]->order_id);
        $this->assertEquals('2026-09-14 10:00:00', $periods[0]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $periods[0]->expires_at->format('Y-m-d H:i:s'));

        // Period 2 (renewal) starts seamlessly at previous expiry
        $this->assertEquals('renewal', $periods[1]->period_type);
        $this->assertEquals($renewalOrder->id, $periods[1]->order_id);
        $this->assertEquals('2027-09-14 10:00:00', $periods[1]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedNewExpiresAt->format('Y-m-d H:i:s'), $periods[1]->expires_at->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 3: POST-EXPIRY RENEWAL FULFILLMENT TESTS
    |--------------------------------------------------------------------------
    */

    public function test_post_expiry_renewal_starts_from_current_fulfillment_time_without_gap_backfill(): void
    {
        // Initial purchase was in 2025, expired on 2026-08-01
        $initialStartsAt = Carbon::parse('2025-08-01 10:00:00');
        $initialExpiresAt = Carbon::parse('2026-08-01 10:00:00');

        $initialOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-INIT-003',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => $initialStartsAt,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => $initialStartsAt,
            'expires_at' => $initialExpiresAt,
            'enrolled_at' => $initialStartsAt,
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $initialOrder->id,
            'period_type' => 'initial',
            'starts_at' => $initialStartsAt,
            'expires_at' => $initialExpiresAt,
        ]);

        // Student repurchases on 2026-09-14 (over a month after expiration)
        $now = Carbon::parse('2026-09-14 12:00:00');
        Carbon::setTestNow($now);

        $repurchaseOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-REP-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => $now,
            'metadata' => ['purchase_type' => 'repurchase'],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($repurchaseOrder);

        // Verify: Exactly 1 enrollment row reused, now active
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        $this->assertEquals('2026-09-14 12:00:00', $enrollment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 12:00:00', $enrollment->expires_at->format('Y-m-d H:i:s'));

        // Verify: 2 access periods, gap between 2026-08-01 and 2026-09-14 is preserved
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)
            ->orderBy('starts_at', 'asc')
            ->get();
        $this->assertCount(2, $periods);

        $this->assertEquals('renewal', $periods[1]->period_type);
        $this->assertEquals($repurchaseOrder->id, $periods[1]->order_id);
        $this->assertEquals('2026-09-14 12:00:00', $periods[1]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 12:00:00', $periods[1]->expires_at->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 4: MULTI-YEAR ACCESS HISTORY AUDIT TRAIL
    |--------------------------------------------------------------------------
    */

    public function test_student_renewing_across_three_consecutive_years_has_three_sequential_access_periods(): void
    {
        // Year 1 Purchase
        Carbon::setTestNow('2026-01-01 10:00:00');
        $orderYear1 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-Y1-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);
        app(OrderFulfillmentService::class)->fulfillOrder($orderYear1);

        // Year 2 Renewal (Purchased Dec 2026)
        Carbon::setTestNow('2026-12-15 10:00:00');
        $orderYear2 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-Y2-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);
        app(OrderFulfillmentService::class)->fulfillOrder($orderYear2);

        // Year 3 Renewal (Purchased Dec 2027)
        Carbon::setTestNow('2027-12-10 10:00:00');
        $orderYear3 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-Y3-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);
        app(OrderFulfillmentService::class)->fulfillOrder($orderYear3);

        // Invariants
        $enrollment = Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->firstOrFail();

        // Exactly one enrollment record
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        // Final expiry is 3 full validity cycles from Year 1 start: 2026-01-01 + 365 + 365 + 365 days
        $expectedFinalExpiry = Carbon::parse('2026-01-01 10:00:00')->addDays(365)->addDays(365)->addDays(365);
        $this->assertEquals($expectedFinalExpiry->format('Y-m-d H:i:s'), $enrollment->expires_at->format('Y-m-d H:i:s'));

        // 3 sequential access periods
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)
            ->orderBy('starts_at', 'asc')
            ->get();
        $this->assertCount(3, $periods);

        $this->assertEquals('initial', $periods[0]->period_type);
        $this->assertEquals($orderYear1->id, $periods[0]->order_id);

        $this->assertEquals('renewal', $periods[1]->period_type);
        $this->assertEquals($orderYear2->id, $periods[1]->order_id);

        $this->assertEquals('renewal', $periods[2]->period_type);
        $this->assertEquals($orderYear3->id, $periods[2]->order_id);

        // Periods connect seamlessly
        $this->assertEquals($periods[0]->expires_at->format('Y-m-d H:i:s'), $periods[1]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($periods[1]->expires_at->format('Y-m-d H:i:s'), $periods[2]->starts_at->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 5: IDEMPOTENCY TESTS (BROWSER & WEBHOOK)
    |--------------------------------------------------------------------------
    */

    public function test_repeated_browser_verification_call_is_strictly_idempotent(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-IDEM-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_idem_123',
        ]);

        $razorpayMock = Mockery::mock(RazorpayService::class);
        $razorpayMock->shouldReceive('verifyPaymentSignature')
            ->twice()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        // Call 1
        $response1 = $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_idem_123',
            'razorpay_payment_id' => 'pay_idem_123',
            'razorpay_signature' => 'sig_idem_123',
        ]);
        $response1->assertRedirect(route('payment.success', $order));

        $this->assertEquals(1, Enrollment::count());
        $this->assertEquals(1, CourseAccessPeriod::count());
        $this->assertEquals(1, Payment::count());

        $firstExpiresAt = Enrollment::first()->expires_at->toIso8601String();

        // Call 2 (Duplicate / Refresh)
        $response2 = $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_idem_123',
            'razorpay_payment_id' => 'pay_idem_123',
            'razorpay_signature' => 'sig_idem_123',
        ]);
        $response2->assertRedirect(route('payment.success', $order));

        // Still exactly 1 enrollment, 1 access period, 1 payment
        $this->assertEquals(1, Enrollment::count());
        $this->assertEquals(1, CourseAccessPeriod::count());
        $this->assertEquals(1, Payment::count());
        $this->assertEquals($firstExpiresAt, Enrollment::first()->expires_at->toIso8601String());
    }

    public function test_webhook_order_paid_after_browser_verify_does_not_duplicate_fulfillment(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-HOOK-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_hook_123',
        ]);

        // 1. Browser verification arrives first
        $razorpayMock = Mockery::mock(RazorpayService::class);
        $razorpayMock->shouldReceive('verifyPaymentSignature')->once()->andReturn(true);
        $razorpayMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_hook_123',
            'razorpay_payment_id' => 'pay_hook_123',
            'razorpay_signature' => 'sig_hook_123',
        ]);

        $this->assertEquals(1, CourseAccessPeriod::count());

        // 2. Razorpay webhook order.paid arrives shortly after
        $payload = [
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_hook_123',
                        'amount' => 499900,
                        'currency' => 'INR',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_hook_123',
                        'amount' => 499900,
                        'currency' => 'INR',
                        'method' => 'upi',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('webhooks.razorpay'), $payload, [
            'X-Razorpay-Signature' => 'valid_webhook_signature',
            'X-Razorpay-Event-Id' => 'event_hook_001',
        ]);

        $response->assertStatus(200);

        // Verification: No duplicates
        $this->assertEquals(1, Enrollment::count());
        $this->assertEquals(1, CourseAccessPeriod::count());
        $this->assertEquals(1, Payment::count());
    }

    public function test_duplicate_webhook_events_are_safely_ignored(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-DUP-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_dup_hook_123',
        ]);

        $razorpayMock = Mockery::mock(RazorpayService::class);
        $razorpayMock->shouldReceive('verifyWebhookSignature')->andReturn(true);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $payload = [
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_dup_hook_123',
                        'amount' => 499900,
                        'currency' => 'INR',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_dup_hook_123',
                        'amount' => 499900,
                        'currency' => 'INR',
                        'method' => 'card',
                    ],
                ],
            ],
        ];

        // Delivery 1
        $res1 = $this->postJson(route('webhooks.razorpay'), $payload, [
            'X-Razorpay-Signature' => 'valid_webhook_signature',
            'X-Razorpay-Event-Id' => 'event_dup_001',
        ]);
        $res1->assertStatus(200);

        // Delivery 2 (same Event ID)
        $res2 = $this->postJson(route('webhooks.razorpay'), $payload, [
            'X-Razorpay-Signature' => 'valid_webhook_signature',
            'X-Razorpay-Event-Id' => 'event_dup_001',
        ]);
        $res2->assertStatus(200);

        // Invariants preserved
        $this->assertEquals(1, CourseAccessPeriod::count());
        $this->assertEquals(1, Enrollment::count());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 6: CONCURRENCY SIMULATION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_two_consecutive_renewal_fulfillments_calculate_sequentially(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        // Initial enrollment created
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->copy()->addDays(365), // 2027-09-14
            'enrolled_at' => now(),
        ]);

        $orderInitial = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CONC-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $orderInitial->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->copy()->addDays(365),
        ]);

        // Order 1 (Renewal #1)
        $order1 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CONC-002',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Order 2 (Renewal #2)
        $order2 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CONC-003',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $service = app(OrderFulfillmentService::class);

        // Fulfill both orders
        $service->fulfillOrder($order1);
        $service->fulfillOrder($order2);

        // Both access periods must be created sequentially
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)
            ->orderBy('starts_at', 'asc')
            ->get();
        $this->assertCount(3, $periods);

        // Period 2: 2027-09-14 -> +365 days
        $expectedP1Expiry = Carbon::parse('2026-09-14 10:00:00')->addDays(365);
        $expectedP2Expiry = $expectedP1Expiry->copy()->addDays(365);
        $expectedP3Expiry = $expectedP2Expiry->copy()->addDays(365);

        $this->assertEquals($order1->id, $periods[1]->order_id);
        $this->assertEquals($expectedP1Expiry->format('Y-m-d H:i:s'), $periods[1]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedP2Expiry->format('Y-m-d H:i:s'), $periods[1]->expires_at->format('Y-m-d H:i:s'));

        // Period 3
        $this->assertEquals($order2->id, $periods[2]->order_id);
        $this->assertEquals($expectedP2Expiry->format('Y-m-d H:i:s'), $periods[2]->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedP3Expiry->format('Y-m-d H:i:s'), $periods[2]->expires_at->format('Y-m-d H:i:s'));

        $enrollment->refresh();
        $this->assertEquals($expectedP3Expiry->format('Y-m-d H:i:s'), $enrollment->expires_at->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 7: COMPLETED STUDENT PRESERVATION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_completed_student_renewal_preserves_completed_at_progress_and_certificate(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $completedAt = Carbon::parse('2026-06-01 15:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => Carbon::parse('2025-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2026-09-14 10:00:00'),
            'enrolled_at' => Carbon::parse('2025-09-14 10:00:00'),
            'completed_at' => $completedAt,
        ]);

        // Create lesson progress
        $progress = LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => $completedAt,
            'last_watched_at' => $completedAt,
        ]);

        // Create certificate
        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'certificate_number' => 'CERT-105-TEST',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => $completedAt,
            'issued_at' => $completedAt,
        ]);

        // Student renews
        $renewalOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-COMP-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($renewalOrder);

        $enrollment->refresh();

        // 1. Completion status and timestamp preserved
        $this->assertEquals(EnrollmentStatus::COMPLETED, $enrollment->status);
        $this->assertEquals($completedAt->format('Y-m-d H:i:s'), $enrollment->completed_at->format('Y-m-d H:i:s'));

        // 2. Dates updated and active access permitted
        $this->assertEquals('2027-09-14 10:00:00', $enrollment->expires_at->format('Y-m-d H:i:s'));
        $this->assertTrue($enrollment->hasActiveAccess());

        // 3. Lesson progress preserved
        $progress->refresh();
        $this->assertTrue($progress->completed);
        $this->assertEquals($completedAt->format('Y-m-d H:i:s'), $progress->last_watched_at->format('Y-m-d H:i:s'));

        // 4. Certificate preserved
        $certificate->refresh();
        $this->assertEquals('CERT-105-TEST', $certificate->certificate_number);
        $this->assertEquals(1, Certificate::where('enrollment_id', $enrollment->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 8: LEGACY LIFETIME STUDENT TRANSITION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_legacy_lifetime_student_repurchase_creates_new_access_period_without_fabricating_history(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        // Legacy enrollment with NULL starts_at and expires_at
        $legacyEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => Carbon::parse('2024-01-01 10:00:00'),
        ]);

        // Prior to purchase, there are ZERO access periods
        $this->assertEquals(0, CourseAccessPeriod::count());

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-LEG-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        // Exactly ONE access period created (no fabricated history)
        $periods = CourseAccessPeriod::where('enrollment_id', $legacyEnrollment->id)->get();
        $this->assertCount(1, $periods);

        $period = $periods->first();
        $this->assertEquals('renewal', $period->period_type);
        $this->assertEquals($order->id, $period->order_id);
        $this->assertEquals('2026-09-14 10:00:00', $period->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $period->expires_at->format('Y-m-d H:i:s'));

        // Enrollment safely transitioned to active dates
        $legacyEnrollment->refresh();
        $this->assertEquals('2026-09-14 10:00:00', $legacyEnrollment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $legacyEnrollment->expires_at->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 9: BUNDLE FULFILLMENT REGRESSION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_bundle_purchase_fulfills_each_course_with_its_own_access_period(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $course1 = Course::create([
            'title' => 'Bundle Course 1',
            'slug' => 'bundle-course-1',
            'short_description' => 'Bundle 1 short desc',
            'price' => 3000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $course2 = Course::create([
            'title' => 'Bundle Course 2',
            'slug' => 'bundle-course-2',
            'short_description' => 'Bundle 2 short desc',
            'price' => 3000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 180,
        ]);

        $bundle = Bundle::create([
            'title' => 'Premium Bundle',
            'slug' => 'premium-bundle',
            'price' => 7999.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$course1->id, $course2->id]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $bundle->id,
            'course_id' => null,
            'order_number' => 'MM-ORD-BND-001',
            'amount' => 799900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        // 1. Exactly one enrollment per course
        $enrollment1 = Enrollment::where('user_id', $this->student->id)->where('course_id', $course1->id)->firstOrFail();
        $enrollment2 = Enrollment::where('user_id', $this->student->id)->where('course_id', $course2->id)->firstOrFail();

        // 2. Each enrollment has its own access period under the same bundle order
        $period1 = CourseAccessPeriod::where('enrollment_id', $enrollment1->id)->firstOrFail();
        $this->assertEquals($order->id, $period1->order_id);
        $this->assertEquals('2027-09-14 10:00:00', $period1->expires_at->format('Y-m-d H:i:s')); // 365 days

        $period2 = CourseAccessPeriod::where('enrollment_id', $enrollment2->id)->firstOrFail();
        $this->assertEquals($order->id, $period2->order_id);
        $this->assertEquals('2027-03-13 10:00:00', $period2->expires_at->format('Y-m-d H:i:s')); // 180 days

        // Total access periods in database = 2
        $this->assertEquals(2, CourseAccessPeriod::where('order_id', $order->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 10: SECURITY & AUTHORIZATION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_verify_payment(): void
    {
        $response = $this->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_fake_123',
            'razorpay_payment_id' => 'pay_fake_123',
            'razorpay_signature' => 'sig_fake_123',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_verify_order_belonging_to_another_user(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        Order::create([
            'user_id' => $otherStudent->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-OTHER-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_other_123',
        ]);

        $response = $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_other_123',
            'razorpay_payment_id' => 'pay_other_123',
            'razorpay_signature' => 'sig_other_123',
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, CourseAccessPeriod::count());
    }

    public function test_cancelled_enrollment_is_not_silently_reactivated_by_fulfillment(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => Carbon::parse('2025-01-01'),
            'expires_at' => Carbon::parse('2026-01-01'),
            'enrolled_at' => Carbon::parse('2025-01-01'),
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CANC-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $period = app(OrderFulfillmentService::class)->fulfillCourseAccess($this->student, $this->course, $order);

        $this->assertNull($period);
        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::CANCELLED, $enrollment->status);
        $this->assertEquals(0, CourseAccessPeriod::count());
    }
}
