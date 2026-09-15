<?php

namespace Tests\Feature;

use App\Enums\CoursePurchaseType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RazorpayWebhookEvent;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseRenewalAndRepurchaseCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected Course $course;
    protected CourseModule $module;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Growth Marketing Masterclass',
            'slug' => 'growth-marketing-masterclass',
            'short_description' => 'Master growth channels.',
            'description' => 'Comprehensive masterclass on acquisition.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $this->lesson = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Initial Purchase Flow
    |--------------------------------------------------------------------------
    */

    public function test_user_without_enrollment_can_initiate_purchase(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course));

        $order = Order::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($order);
        $this->assertTrue($order->isInitialPurchase());
        $this->assertFalse($order->isRenewal());
        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_successful_initial_payment_creates_enrollment_and_access_period(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-INIT-001',
            'razorpay_order_id' => 'rzp_order_init_1',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::INITIAL_PURCHASE->value],
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'rzp_order_init_1',
                'razorpay_payment_id' => 'rzp_pay_init_1',
                'razorpay_signature' => 'valid_test_signature',
            ]);

        $response->assertRedirect(route('payment.success', $order));

        $enrollment = Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        $this->assertNotNull($enrollment->starts_at);
        $this->assertNotNull($enrollment->expires_at);

        // Exactly one CourseAccessPeriod created
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->get();
        $this->assertCount(1, $periods);
        $period = $periods->first();

        $this->assertEquals('initial', $period->period_type);
        $this->assertEquals($order->id, $period->order_id);
        $this->assertEquals($enrollment->starts_at->toDateTimeString(), $period->starts_at->toDateTimeString());
        $this->assertEquals($enrollment->expires_at->toDateTimeString(), $period->expires_at->toDateTimeString());
    }

    public function test_initial_purchase_uses_course_access_validity_days(): void
    {
        $this->course->update(['access_validity_days' => 180]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-INIT-180',
            'razorpay_order_id' => 'rzp_order_180',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $this->assertEquals($frozenNow->toDateTimeString(), $enrollment->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(180)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_failed_initial_payment_creates_no_enrollment_and_no_access_period(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-FAIL-001',
            'razorpay_order_id' => 'rzp_order_fail',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        // Signature mismatch
        $razorpayMock = $this->createMock(RazorpayService::class);
        $razorpayMock->method('verifyPaymentSignature')->willReturn(false);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'rzp_order_fail',
                'razorpay_payment_id' => 'rzp_pay_fail',
                'razorpay_signature' => 'invalid_sig',
            ]);

        $response->assertRedirect(route('payment.failed', $order));

        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->assertDatabaseMissing('course_access_periods', [
            'order_id' => $order->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Early Renewal Flow
    |--------------------------------------------------------------------------
    */

    public function test_active_student_can_initiate_early_renewal(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(3),
            'expires_at' => now()->addMonths(9),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course));

        $order = Order::where('user_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($order);
        $this->assertTrue($order->isRenewal());
        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_early_renewal_extends_from_current_expiry_and_preserves_remaining_access(): void
    {
        $frozenNow = Carbon::create(2027, 8, 25, 10, 0, 0);
        Carbon::setTestNow($frozenNow);

        $currentExpiresAt = Carbon::create(2027, 9, 14, 0, 0, 0);
        $currentStartsAt = Carbon::create(2026, 9, 14, 0, 0, 0);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $currentStartsAt,
            'expires_at' => $currentExpiresAt,
        ]);

        // Historical initial period
        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $currentStartsAt,
            'expires_at' => $currentExpiresAt,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-RNW-001',
            'razorpay_order_id' => 'rzp_order_rnw_1',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::RENEWAL->value],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        // Enrollment must NOT duplicate
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        $freshEnrollment = $enrollment->fresh();

        // Starts_at remains 2026-09-14 so student has uninterrupted learning access today
        $this->assertEquals($currentStartsAt->toDateTimeString(), $freshEnrollment->starts_at->toDateTimeString());

        // Expires_at is extended by 365 days from 2027-09-14 -> 2028-09-13 (365 days)
        $expectedNewExpiresAt = $currentExpiresAt->copy()->addDays(365);
        $this->assertEquals($expectedNewExpiresAt->toDateTimeString(), $freshEnrollment->expires_at->toDateTimeString());

        // Exactly 2 CourseAccessPeriods exist
        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->orderBy('starts_at')->get();
        $this->assertCount(2, $periods);

        $renewalPeriod = $periods->last();
        $this->assertEquals('renewal', $renewalPeriod->period_type);
        $this->assertEquals($order->id, $renewalPeriod->order_id);
        $this->assertEquals($currentExpiresAt->toDateTimeString(), $renewalPeriod->starts_at->toDateTimeString());
        $this->assertEquals($expectedNewExpiresAt->toDateTimeString(), $renewalPeriod->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | Post-Expiry Renewal Flow
    |--------------------------------------------------------------------------
    */

    public function test_expired_student_can_renew_and_access_starts_at_purchase_time(): void
    {
        $frozenNow = Carbon::create(2027, 10, 1, 14, 30, 0);
        Carbon::setTestNow($frozenNow);

        $oldStartsAt = Carbon::create(2026, 9, 14, 0, 0, 0);
        $oldExpiresAt = Carbon::create(2027, 9, 14, 0, 0, 0);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => $oldStartsAt,
            'expires_at' => $oldExpiresAt,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-POSTEXP-001',
            'razorpay_order_id' => 'rzp_order_post_1',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::RENEWAL->value],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $freshEnrollment = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $freshEnrollment->status);
        $this->assertEquals($frozenNow->toDateTimeString(), $freshEnrollment->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $freshEnrollment->expires_at->toDateTimeString());

        // No second enrollment created
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        $period = CourseAccessPeriod::where('order_id', $order->id)->first();
        $this->assertNotNull($period);
        $this->assertEquals('renewal', $period->period_type);
        $this->assertEquals($frozenNow->toDateTimeString(), $period->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $period->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelled Enrollment Handling
    |--------------------------------------------------------------------------
    */

    public function test_cancelled_enrollment_is_blocked_from_initiating_purchase(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course));

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('error', 'Your enrollment in this course has been cancelled. Please contact support.');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_cancelled_enrollment_is_never_silently_reactivated_by_fulfillment(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CANCELLED-001',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $this->assertEquals(EnrollmentStatus::CANCELLED, $enrollment->fresh()->status);
        $this->assertDatabaseMissing('course_access_periods', ['order_id' => $order->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Lifetime Enrollment Transition
    |--------------------------------------------------------------------------
    */

    public function test_legacy_lifetime_student_is_not_destroyed_during_checkout_and_transitions_safely(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
        ]);

        // Checkout creation does not mutate or destroy enrollment
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course));

        $this->assertNull($enrollment->fresh()->starts_at);
        $this->assertNull($enrollment->fresh()->expires_at);

        $order = Order::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();

        // Successful fulfillment transitions to annual access
        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $fresh = $enrollment->fresh();
        $this->assertEquals($frozenNow->toDateTimeString(), $fresh->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $fresh->expires_at->toDateTimeString());

        // Only one period created (for the renewal), no fabricated past periods
        $this->assertEquals(1, CourseAccessPeriod::where('enrollment_id', $enrollment->id)->count());

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | Completed Student Renewal & Preservation
    |--------------------------------------------------------------------------
    */

    public function test_completed_student_preserves_completion_progress_and_certificate_on_renewal(): void
    {
        $completedAt = now()->subMonths(6);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
            'completed_at' => $completedAt,
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
            'completed_at' => $completedAt,
        ]);

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-CERT-2026-COMPLETED',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => $completedAt,
            'issued_at' => $completedAt,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-COMPL-RNW',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::RENEWAL->value],
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $fresh = $enrollment->fresh();
        // Status remains completed and completed_at is preserved
        $this->assertEquals(EnrollmentStatus::COMPLETED, $fresh->status);
        $this->assertEquals($completedAt->toDateTimeString(), $fresh->completed_at->toDateTimeString());

        // Progress record remains intact
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => 1,
        ]);

        // Certificate remains intact
        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'certificate_number' => 'MM-CERT-2026-COMPLETED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pricing & Coupon Integration
    |--------------------------------------------------------------------------
    */

    public function test_renewal_uses_server_side_pricing_and_ignores_client_tampering(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(6),
            'expires_at' => now()->addMonths(6),
        ]);

        // Attempt client price manipulation
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course), [
                'price' => 1.00,
                'amount' => 100,
            ]);

        $order = Order::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $this->assertEquals(499900, $order->amount);
    }

    public function test_valid_coupon_can_apply_to_renewal_order(): void
    {
        $coupon = Coupon::create([
            'name' => 'Renewal 20% Off',
            'code' => 'RENEW20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(6),
            'expires_at' => now()->addMonths(6),
        ]);

        $this->actingAs($this->student)->post(route('student.courses.purchase', $this->course));
        $order = Order::where('user_id', $this->student->id)->first();

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'RENEW20',
            ]);

        $response->assertSessionHas('status');
        $freshOrder = $order->fresh();
        $this->assertTrue($freshOrder->hasCoupon());
        // 20% discount on 499900 = 99980 paise discount -> 399920 paise
        $this->assertEquals(399920, $freshOrder->amount);
    }

    /*
    |--------------------------------------------------------------------------
    | Idempotency & Webhook Compatibility
    |--------------------------------------------------------------------------
    */

    public function test_duplicate_fulfillment_does_not_create_duplicate_access_period(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-IDEMP-001',
            'razorpay_order_id' => 'rzp_idemp_1',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $service = app(OrderFulfillmentService::class);

        // First fulfillment
        $service->fulfillOrder($order);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $initialExpiry = $enrollment->expires_at;

        // Second fulfillment (simulating duplicate webhook / verify call)
        $service->fulfillOrder($order);

        $fresh = $enrollment->fresh();
        $this->assertEquals($initialExpiry->toDateTimeString(), $fresh->expires_at->toDateTimeString());
        $this->assertEquals(1, CourseAccessPeriod::where('enrollment_id', $enrollment->id)->count());
    }

    public function test_sequential_legitimate_renewals_produce_sequential_access_periods(): void
    {
        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        // First purchase
        $order1 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-SEQ-1',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);
        app(OrderFulfillmentService::class)->fulfillOrder($order1);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        // Second purchase (early renewal for year 2)
        $order2 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-SEQ-2',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);
        app(OrderFulfillmentService::class)->fulfillOrder($order2);

        $enrollment->refresh();
        $this->assertEquals($frozenNow->copy()->addDays(730)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        $periods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->orderBy('starts_at')->get();
        $this->assertCount(2, $periods);

        $this->assertEquals($frozenNow->toDateTimeString(), $periods[0]->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $periods[0]->expires_at->toDateTimeString());

        $this->assertEquals($frozenNow->copy()->addDays(365)->toDateTimeString(), $periods[1]->starts_at->toDateTimeString());
        $this->assertEquals($frozenNow->copy()->addDays(730)->toDateTimeString(), $periods[1]->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | Date Edge Cases
    |--------------------------------------------------------------------------
    */

    public function test_renewal_one_second_before_expiry_is_treated_as_early_renewal(): void
    {
        $currentExpiry = Carbon::create(2027, 9, 14, 12, 0, 0);
        $oneSecondBefore = $currentExpiry->copy()->subSecond();

        Carbon::setTestNow($oneSecondBefore);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::create(2026, 9, 14, 12, 0, 0),
            'expires_at' => $currentExpiry,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-EDGE-1',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $period = CourseAccessPeriod::where('order_id', $order->id)->first();
        // Starts at currentExpiry (preserving the 1 remaining second)
        $this->assertEquals($currentExpiry->toDateTimeString(), $period->starts_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_renewal_exactly_at_expiry_starts_from_now(): void
    {
        $currentExpiry = Carbon::create(2027, 9, 14, 12, 0, 0);
        Carbon::setTestNow($currentExpiry);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::create(2026, 9, 14, 12, 0, 0),
            'expires_at' => $currentExpiry,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-EDGE-2',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $period = CourseAccessPeriod::where('order_id', $order->id)->first();
        $this->assertEquals($currentExpiry->toDateTimeString(), $period->starts_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_renewal_one_second_after_expiry_starts_from_now(): void
    {
        $currentExpiry = Carbon::create(2027, 9, 14, 12, 0, 0);
        $oneSecondAfter = $currentExpiry->copy()->addSecond();
        Carbon::setTestNow($oneSecondAfter);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::create(2026, 9, 14, 12, 0, 0),
            'expires_at' => $currentExpiry,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-EDGE-3',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $period = CourseAccessPeriod::where('order_id', $order->id)->first();
        $this->assertEquals($oneSecondAfter->toDateTimeString(), $period->starts_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_leap_year_renewal_calculates_exact_day_duration(): void
    {
        // 2028 is a leap year (February has 29 days)
        $leapNow = Carbon::create(2028, 2, 1, 10, 0, 0);
        Carbon::setTestNow($leapNow);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-LEAP-1',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        // 365 days from Feb 1, 2028 in a leap year lands on Jan 31, 2029
        $expectedExpiry = $leapNow->copy()->addDays(365);
        $this->assertEquals($expectedExpiry->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | Security & User Isolation
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_initiate_renewal_checkout(): void
    {
        $response = $this->post(route('student.courses.purchase', $this->course));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_checkout_another_students_order(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-ISO-01',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->otherStudent)
            ->get(route('student.courses.checkout', $order));

        $response->assertStatus(403);
    }

    public function test_free_coupon_order_fulfillment_via_complete_free_grants_renewal_access(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(6),
            'expires_at' => now()->addMonths(6),
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-FREE-01',
            'amount' => 0,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::RENEWAL->value],
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.complete-free', $order));

        $response->assertRedirect(route('payment.success', $order));

        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertEquals(1, CourseAccessPeriod::where('order_id', $order->id)->count());
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());
    }

    public function test_webhook_order_paid_fulfills_course_renewal_access(): void
    {
        $currentExpiry = now()->addMonths(2);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(10),
            'expires_at' => $currentExpiry,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-WH-01',
            'razorpay_order_id' => 'order_webhook_test_1',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
            'metadata' => ['purchase_type' => CoursePurchaseType::RENEWAL->value],
        ]);

        // Mock Razorpay webhook signature verification
        $razorpayMock = $this->createMock(RazorpayService::class);
        $razorpayMock->method('verifyWebhookSignature')->willReturn(true);
        $this->app->instance(RazorpayService::class, $razorpayMock);

        $payload = [
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_webhook_test_1',
                        'amount' => 499900,
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_webhook_test_1',
                        'amount' => 499900,
                        'currency' => 'INR',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('webhooks.razorpay'), $payload, [
            'X-Razorpay-Signature' => 'valid_webhook_sig',
            'X-Razorpay-Event-Id' => 'evt_test_001',
        ]);

        $response->assertStatus(200);

        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertEquals(1, CourseAccessPeriod::where('order_id', $order->id)->count());
        $this->assertEquals(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        $freshEnrollment = $enrollment->fresh();
        $this->assertEquals($currentExpiry->copy()->addDays(365)->toDateTimeString(), $freshEnrollment->expires_at->toDateTimeString());
    }

    public function test_configured_validity_of_30_and_90_days(): void
    {
        $this->course->update(['access_validity_days' => 30]);

        $order30 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-30',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        app(OrderFulfillmentService::class)->fulfillOrder($order30);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $this->assertEquals($frozenNow->copy()->addDays(30)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        // Update course to 90 days and renew
        $this->course->update(['access_validity_days' => 90]);

        $order90 = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-90',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order90);

        $enrollment->refresh();
        // 30 days + 90 days = 120 days
        $this->assertEquals($frozenNow->copy()->addDays(120)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_order_expires_at_is_distinct_from_course_access_expires_at(): void
    {
        $orderPendingExpiry = now()->addHours(2);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-DISTINCT',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'expires_at' => $orderPendingExpiry, // Order lifecycle expiration
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $enrollment = Enrollment::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();

        // Order expires_at is in 2 hours
        $this->assertEquals($orderPendingExpiry->toDateTimeString(), $order->expires_at->toDateTimeString());

        // Course enrollment access expires in 365 days, completely distinct
        $this->assertNotEquals($order->expires_at->toDateTimeString(), $enrollment->expires_at->toDateTimeString());
        $this->assertEquals(now()->addDays(365)->toDateTimeString(), $enrollment->expires_at->toDateTimeString());
    }
}