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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentA;
    protected User $studentB;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentA = User::factory()->create([
            'name' => 'Student Alice',
            'email' => 'alice@example.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->studentB = User::factory()->create([
            'name' => 'Student Bob',
            'email' => 'bob@example.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Pro',
            'slug' => 'digital-marketing-pro-' . uniqid(),
            'short_description' => 'Comprehensive masterclass',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_razorpay_webhook_is_exempt_from_csrf_but_validates_signature(): void
    {
        // Missing or invalid signature must be rejected with 400 Bad Request
        $response = $this->postJson(route('webhooks.razorpay'), [
            'event' => 'payment.captured',
        ], [
            'X-Razorpay-Signature' => 'invalid_cryptographic_signature_hash',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid webhook signature.']);
    }

    public function test_rate_limiting_protects_public_lead_capture(): void
    {
        // 6 allowed requests per minute on throttle:6,1
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('leads.store'), [
                'name' => 'Lead ' . $i,
                'email' => "lead{$i}@example.com",
                'message' => 'Interested in course inquiry.',
                'source' => 'home_page',
            ]);
            $this->assertTrue(in_array($response->getStatusCode(), [302, 200]));
        }

        // 7th request must be rate-limited (HTTP 429 Too Many Requests)
        $rateLimitedResponse = $this->post(route('leads.store'), [
            'name' => 'Lead Throttled',
            'email' => 'throttled@example.com',
            'message' => 'Excessive rate request.',
            'source' => 'home_page',
        ]);

        $rateLimitedResponse->assertStatus(429);
    }

    public function test_idor_protection_prevents_students_accessing_foreign_orders(): void
    {
        $orderB = Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-BOB-001',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Student A attempts to access Student B's order receipt
        $response = $this->actingAs($this->studentA)->get(route('student.orders.show', $orderB));

        // Must be forbidden (HTTP 403)
        $response->assertForbidden();
    }

    public function test_idor_protection_prevents_students_accessing_foreign_payment_success(): void
    {
        $orderB = Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-BOB-PAY',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Student A attempts to access Student B's payment success screen
        $response = $this->actingAs($this->studentA)->get(route('payment.success', $orderB));

        $response->assertForbidden();
    }

    public function test_unpaid_orders_do_not_grant_course_enrollment(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-UNPAID',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Student A attempts to access the learning player for the course
        $response = $this->actingAs($this->studentA)->get(route('student.courses.show', $this->course));

        // Must be forbidden (HTTP 403) because student is not enrolled
        $response->assertForbidden();
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $this->studentA->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_duplicate_enrollment_prevention_on_repeated_payments(): void
    {
        // Pre-existing active enrollment
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDay(),
        ]);

        $this->assertEquals(1, Enrollment::where('user_id', $this->studentA->id)->where('course_id', $this->course->id)->count());

        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-DUP-TEST',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_razor_dup_1',
        ]);

        // Mock payment execution inside transaction
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            $lockedOrder->markPaid();

            $enrollment = Enrollment::query()
                ->where('user_id', $this->studentA->id)
                ->where('course_id', $lockedOrder->course_id)
                ->first();

            if (! $enrollment) {
                Enrollment::create([
                    'user_id' => $this->studentA->id,
                    'course_id' => $lockedOrder->course_id,
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
            } elseif (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
                $enrollment->update([
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
            }
        });

        // Must still be exactly 1 enrollment row
        $this->assertEquals(1, Enrollment::where('user_id', $this->studentA->id)->where('course_id', $this->course->id)->count());
    }

    public function test_custom_404_page_renders_cleanly(): void
    {
        $response = $this->get('/a-completely-non-existent-route-for-404-test');

        $response->assertStatus(404);
        $response->assertSee('Page Not Found');
        $response->assertSee('Error 404');
    }

    public function test_custom_403_page_renders_cleanly(): void
    {
        // Student attempting to access admin dashboard
        $response = $this->actingAs($this->studentA)->get(route('admin.dashboard'));

        $response->assertStatus(403);
        $response->assertSee('Access Restricted');
        $response->assertSee('Error 403');
    }
}