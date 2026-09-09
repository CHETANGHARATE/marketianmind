<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RazorpayWebhookEvent;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePaymentAndPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $admin;
    protected Course $paidCourse;
    protected Course $discountedCourse;
    protected Course $freeCourse;
    protected Course $draftCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        // Standard Paid Course (₹4,999 = 499900 paise)
        $this->paidCourse = Course::create([
            'title' => 'Social Media Growth Engine',
            'slug' => 'social-media-growth-engine',
            'short_description' => 'Scale social channels organically.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Discounted Paid Course (Regular ₹6,999, Discount ₹3,999 = 399900 paise)
        $this->discountedCourse = Course::create([
            'title' => 'High-Conversion Copywriting',
            'slug' => 'high-conversion-copywriting',
            'short_description' => 'Write words that sell.',
            'price' => 6999.00,
            'discount_price' => 3999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Free Course
        $this->freeCourse = Course::create([
            'title' => 'Digital Marketing Fundamentals',
            'slug' => 'digital-marketing-fundamentals',
            'short_description' => 'Free marketing course.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Draft Course
        $this->draftCourse = Course::create([
            'title' => 'Unpublished Growth Playbook',
            'slug' => 'unpublished-growth-playbook',
            'short_description' => 'Draft course.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::DRAFT,
        ]);
    }

    public function test_guest_cannot_purchase_course_and_is_redirected_to_login(): void
    {
        $response = $this->post(route('student.courses.purchase', $this->paidCourse));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('orders', [
            'course_id' => $this->paidCourse->id,
        ]);
    }

    public function test_free_course_cannot_be_purchased_via_paid_checkout_flow(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->freeCourse));

        $response->assertRedirect(route('student.courses.enroll', $this->freeCourse));
        $this->assertDatabaseMissing('orders', [
            'course_id' => $this->freeCourse->id,
        ]);
    }

    public function test_draft_course_cannot_be_purchased(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->draftCourse));

        $response->assertStatus(404);
        $this->assertDatabaseMissing('orders', [
            'course_id' => $this->draftCourse->id,
        ]);
    }

    public function test_already_enrolled_student_cannot_purchase_course_again(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->paidCourse));

        $response->assertRedirect(route('student.courses.show', $this->paidCourse));
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
        ]);
    }

    public function test_student_can_initiate_purchase_and_order_amount_is_calculated_server_side(): void
    {
        // Even if client attempted to send malicious manipulated price, it is ignored
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->paidCourse), [
                'price' => 1.00,
                'amount' => 100,
            ]);

        $order = Order::where('user_id', $this->student->id)
            ->where('course_id', $this->paidCourse->id)
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals(499900, $order->amount); // ₹4,999 in paise
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $this->assertNotNull($order->razorpay_order_id);
        $this->assertStringStartsWith('MM-ORD-', $order->order_number);

        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_discount_price_is_correctly_applied_to_order_amount(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->discountedCourse));

        $order = Order::where('user_id', $this->student->id)
            ->where('course_id', $this->discountedCourse->id)
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals(399900, $order->amount); // ₹3,999 in paise (discounted from ₹6,999)
        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_student_can_view_checkout_screen(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-TEST-001',
            'razorpay_order_id' => 'order_test_123',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.checkout', $order));

        $response->assertStatus(200);
        $response->assertSee('Social Media Growth Engine');
        $response->assertSee('4,999.00');
        $response->assertSee('order_test_123');
    }

    public function test_student_cannot_access_another_students_checkout(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-TEST-002',
            'razorpay_order_id' => 'order_test_456',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->otherStudent)
            ->get(route('student.courses.checkout', $order));

        $response->assertStatus(403);
    }

    public function test_valid_payment_signature_verification_activates_order_and_enrollment(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-TEST-003',
            'razorpay_order_id' => 'order_valid_123',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_valid_123',
                'razorpay_payment_id' => 'pay_valid_789',
                'razorpay_signature' => 'valid_test_signature',
            ]);

        $response->assertRedirect(route('payment.success', $order));

        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'razorpay_payment_id' => 'pay_valid_789',
            'captured' => true,
            'status' => PaymentStatus::CAPTURED->value,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    public function test_invalid_payment_signature_marks_order_failed_and_denies_enrollment(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-TEST-004',
            'razorpay_order_id' => 'order_invalid_123',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_invalid_123',
                'razorpay_payment_id' => 'pay_invalid_789',
                'razorpay_signature' => 'forged_fake_signature',
            ]);

        $response->assertRedirect(route('payment.failed', $order));
        $this->assertEquals(OrderStatus::FAILED, $order->fresh()->status);

        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
        ]);
    }

    public function test_duplicate_verification_call_is_idempotent_and_does_not_create_duplicate_enrollment(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-TEST-005',
            'razorpay_order_id' => 'order_idem_123',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        // First verification
        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_idem_123',
                'razorpay_payment_id' => 'pay_idem_789',
                'razorpay_signature' => 'valid_test_signature',
            ]);

        $this->assertCount(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->paidCourse->id)->get());

        // Second verification
        $response2 = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_idem_123',
                'razorpay_payment_id' => 'pay_idem_789',
                'razorpay_signature' => 'valid_test_signature',
            ]);

        $response2->assertRedirect(route('payment.success', $order));
        $this->assertCount(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->paidCourse->id)->get());
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'event' => 'order.paid',
            'id' => 'evt_fake_001',
        ]);

        $response = $this->call(
            'POST',
            route('webhooks.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X-Razorpay-Signature' => 'invalid_webhook_sig',
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(400);
    }

    public function test_webhook_order_paid_event_activates_order_and_enrollment(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-WEBHOOK-001',
            'razorpay_order_id' => 'order_wh_123',
            'amount' => 499900,
            'status' => OrderStatus::PENDING,
        ]);

        $payloadArray = [
            'id' => 'evt_wh_1001',
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_wh_123',
                        'amount' => 499900,
                        'currency' => 'INR',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_wh_1001',
                        'amount' => 499900,
                        'currency' => 'INR',
                        'method' => 'upi',
                    ],
                ],
            ],
        ];

        $rawPayload = json_encode($payloadArray);
        $expectedSignature = hash_hmac('sha256', $rawPayload, 'mock_webhook_secret');

        $response = $this->call(
            'POST',
            route('webhooks.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X-Razorpay-Signature' => $expectedSignature,
                'HTTP_X-Razorpay-Event-Id' => 'evt_wh_1001',
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);

        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('razorpay_webhook_events', [
            'event_id' => 'evt_wh_1001',
            'status' => 'processed',
        ]);
    }

    public function test_webhook_idempotency_prevents_duplicate_processing_on_retry(): void
    {
        RazorpayWebhookEvent::create([
            'event_id' => 'evt_duplicate_001',
            'event_type' => 'order.paid',
            'payload' => ['foo' => 'bar'],
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $rawPayload = json_encode([
            'id' => 'evt_duplicate_001',
            'event' => 'order.paid',
        ]);
        $expectedSignature = hash_hmac('sha256', $rawPayload, 'mock_webhook_secret');

        $response = $this->call(
            'POST',
            route('webhooks.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X-Razorpay-Signature' => $expectedSignature,
                'HTTP_X-Razorpay-Event-Id' => 'evt_duplicate_001',
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'already_processed']);
    }

    public function test_student_can_view_order_history_and_receipt(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-HIST-001',
            'razorpay_order_id' => 'order_hist_123',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('MM-ORD-HIST-001');
        $response->assertSee('4,999.00');
        $response->assertSee('Social Media Growth Engine');

        // View single order receipt
        $showResponse = $this->actingAs($this->student)
            ->get(route('student.orders.show', $order));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('MM-ORD-HIST-001');
    }

    public function test_admin_can_view_and_filter_orders(): void
    {
        $paidOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'order_number' => 'MM-ORD-ADMIN-001',
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $pendingOrder = Order::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->discountedCourse->id,
            'order_number' => 'MM-ORD-ADMIN-002',
            'amount' => 399900,
            'status' => OrderStatus::PENDING,
        ]);

        // Student cannot access admin orders
        $this->actingAs($this->student)
            ->get(route('admin.orders.index'))
            ->assertStatus(403);

        // Admin can access admin orders
        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('MM-ORD-ADMIN-001');
        $response->assertSee('MM-ORD-ADMIN-002');

        // Filter by paid
        $paidResponse = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'paid']));

        $paidResponse->assertStatus(200);
        $paidResponse->assertSee('MM-ORD-ADMIN-001');
        $paidResponse->assertDontSee('MM-ORD-ADMIN-002');

        // Admin view single order
        $showResponse = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $paidOrder));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('MM-ORD-ADMIN-001');
    }
}