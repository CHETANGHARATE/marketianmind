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
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StudentOrdersAndPurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $admin;
    protected Course $courseA;
    protected Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'name' => 'Alex Founder',
            'email' => 'alex@startup.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'name' => 'Sara Merchant',
            'email' => 'sara@ecommerce.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->courseA = Course::create([
            'title' => 'Google Ads Mastery for Local Business',
            'slug' => 'google-ads-mastery',
            'short_description' => 'Master search and map pack ads.',
            'price' => 3999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->courseB = Course::create([
            'title' => 'Email Marketing Automations',
            'slug' => 'email-marketing-automations',
            'short_description' => 'Build automated revenue funnels.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * TEST 1: Guest cannot access student orders.
     */
    public function test_guest_cannot_access_student_orders(): void
    {
        $this->get('/student/orders')->assertRedirect('/login');

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-2026-0001',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $this->get('/student/orders/' . $order->id)->assertRedirect('/login');
    }

    /**
     * TEST 2: Authenticated student can view own orders.
     */
    public function test_authenticated_student_can_view_own_orders(): void
    {
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-2026-TEST01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders');

        $response->assertStatus(200);
        $response->assertSee('Purchase History');
        $response->assertSee('MM-ORD-2026-TEST01');
        $response->assertSee('Google Ads Mastery for Local Business');
        $response->assertSee('₹3,999.00');
        $response->assertSee('Paid & Active');
    }

    /**
     * TEST 3: Student sees only their own orders.
     */
    public function test_student_sees_only_their_own_orders(): void
    {
        // Student's order
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-ALEX-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        // Other student's order
        Order::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'MM-ORD-SARA-02',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders');

        $response->assertStatus(200);
        $response->assertSee('MM-ORD-ALEX-01');
        $response->assertDontSee('MM-ORD-SARA-02');
    }

    /**
     * TEST 4: Student cannot access another student's order.
     */
    public function test_student_cannot_access_another_students_order(): void
    {
        $sarasOrder = Order::create([
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'MM-ORD-SECRET-99',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        // Alex tries to view Sara's order
        $response = $this->actingAs($this->student)->get('/student/orders/' . $sarasOrder->id);

        $response->assertStatus(403);
    }

    /**
     * TEST 5: OrderPolicy forbids student update and delete mutations.
     */
    public function test_student_cannot_modify_an_order(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-IMMUTABLE-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Policy checks
        $this->assertTrue(Gate::forUser($this->student)->allows('view', $order));
        $this->assertFalse(Gate::forUser($this->student)->allows('update', $order));
        $this->assertFalse(Gate::forUser($this->student)->allows('delete', $order));
    }

    /**
     * TEST 6: Student cannot modify payment status or payment records.
     */
    public function test_student_cannot_modify_payment_status(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-STATUS-LOCK',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'razorpay_payment_id' => 'pay_dummy123',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => PaymentStatus::CREATED,
            'captured' => false,
        ]);

        // Verify there is no student route allowing PUT/PATCH to payments or orders
        $this->actingAs($this->student)->put('/student/orders/' . $order->id, [
            'status' => 'paid',
        ])->assertStatus(405); // Method Not Allowed

        $this->actingAs($this->student)->patch('/student/orders/' . $order->id, [
            'status' => 'paid',
        ])->assertStatus(405);

        // Database records remain unaltered
        $order->refresh();
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $payment->refresh();
        $this->assertEquals(PaymentStatus::CREATED, $payment->status);
    }

    /**
     * TEST 7: Order details show the correct course.
     */
    public function test_order_details_show_correct_course(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-DETAILS-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('MM-ORD-DETAILS-01');
        $response->assertSee('Google Ads Mastery for Local Business');
        $response->assertSee('₹3,999.00');
        $response->assertSee('Print Receipt');
        $response->assertSee('Alex Founder');
        $response->assertSee('alex@startup.com');
    }

    /**
     * TEST 8: Paid order shows appropriate course access CTA.
     */
    public function test_paid_order_shows_course_access_cta(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-ENROLLED-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Active enrollment
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Payment Verified &amp; Course Access Active', false);
        $response->assertSee('Continue Learning');
        $response->assertSee(route('student.courses.show', $this->courseA));
    }

    /**
     * TEST 9: Pending order displays pending status and payment CTA.
     */
    public function test_pending_order_displays_pending_status_and_payment_cta(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-PENDING-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Payment Pending');
        $response->assertSee('Complete Payment');
        $response->assertSee(route('student.courses.checkout', $order));
    }

    /**
     * TEST 10: Failed order displays failed status.
     */
    public function test_failed_order_displays_failed_status(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-FAILED-01',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        $response = $this->actingAs($this->student)->get('/student/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Payment Unsuccessful');
        $response->assertSee('Retry Purchase');
        $response->assertSee(route('courses.show', $this->courseA));
    }

    /**
     * TEST 11: Empty order history displays the correct empty state.
     */
    public function test_empty_order_history_displays_correct_empty_state(): void
    {
        $response = $this->actingAs($this->student)->get('/student/orders');

        $response->assertStatus(200);
        $response->assertSee('No purchases yet');
        $response->assertSee('Explore Courses');
        $response->assertSee(route('courses'));
    }

    /**
     * TEST 12: Pagination works when more than 10 orders exist.
     */
    public function test_pagination_works_for_orders(): void
    {
        // Create 15 orders for this student with distinct timestamps
        for ($i = 1; $i <= 15; $i++) {
            $order = Order::create([
                'user_id' => $this->student->id,
                'course_id' => $this->courseA->id,
                'order_number' => sprintf('MM-ORD-PAGINATED-%02d', $i),
                'amount' => 399900,
                'currency' => 'INR',
                'status' => OrderStatus::PAID,
            ]);
            $order->created_at = now()->subMinutes(30 - $i);
            $order->save();
        }

        // Page 1 should show latest 10
        $responsePage1 = $this->actingAs($this->student)->get('/student/orders');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('MM-ORD-PAGINATED-15');
        $responsePage1->assertDontSee('MM-ORD-PAGINATED-01');

        // Page 2 should show remaining 5
        $responsePage2 = $this->actingAs($this->student)->get('/student/orders?page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('MM-ORD-PAGINATED-01');
        $responsePage2->assertDontSee('MM-ORD-PAGINATED-15');
    }

    /**
     * TEST 13: Status filtering works correctly.
     */
    public function test_status_filtering_works(): void
    {
        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-PAID-FILTER',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseB->id,
            'order_number' => 'MM-ORD-PENDING-FILTER',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Filter by paid
        $responsePaid = $this->actingAs($this->student)->get('/student/orders?status=paid');
        $responsePaid->assertStatus(200);
        $responsePaid->assertSee('MM-ORD-PAID-FILTER');
        $responsePaid->assertDontSee('MM-ORD-PENDING-FILTER');

        // Filter by pending
        $responsePending = $this->actingAs($this->student)->get('/student/orders?status=pending');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('MM-ORD-PENDING-FILTER');
        $responsePending->assertDontSee('MM-ORD-PAID-FILTER');

        // Invalid status falls back to all
        $responseInvalid = $this->actingAs($this->student)->get('/student/orders?status=invalid_hacker_status');
        $responseInvalid->assertStatus(200);
        $responseInvalid->assertSee('MM-ORD-PAID-FILTER');
        $responseInvalid->assertSee('MM-ORD-PENDING-FILTER');
    }

    /**
     * TEST 14: Admin order functionality remains unaffected.
     */
    public function test_admin_order_functionality_remains_unaffected(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'order_number' => 'MM-ORD-ADMIN-INSPECT',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $this->actingAs($this->admin)->get('/admin/orders')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/orders/' . $order->id)->assertStatus(200);
    }

    /**
     * TEST 15: Existing payment verification remains functional.
     */
    public function test_existing_payment_verification_remains_functional(): void
    {
        $this->actingAs($this->student)->get('/payment/success/NONEXISTENT')->assertStatus(404);
        $this->actingAs($this->student)->get('/payment/failed/NONEXISTENT')->assertStatus(404);
    }

    /**
     * TEST 16: Existing enrollment access remains unaffected.
     */
    public function test_existing_enrollment_access_remains_unaffected(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.show', $this->courseA));
        $response->assertStatus(200);
    }
}
