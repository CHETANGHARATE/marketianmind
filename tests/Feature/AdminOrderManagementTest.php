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

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Order Admin',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->studentA = User::factory()->create([
            'name' => 'John Buyer',
            'email' => 'john@buyer.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->studentB = User::factory()->create([
            'name' => 'Emma Customer',
            'email' => 'emma@customer.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Performance Marketing',
            'slug' => 'performance-marketing',
        ]);

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Meta Ads Mastery 2026',
            'slug' => 'meta-ads-mastery-2026',
            'short_description' => 'Scale Facebook and Instagram ads profitably.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Google Search Ads Pro',
            'slug' => 'google-search-ads-pro',
            'short_description' => 'High ROI Google Ads strategy.',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test 1: Guests cannot access admin order routes.
     */
    public function test_guest_is_redirected_from_admin_order_routes(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-0001',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $responseIndex = $this->get(route('admin.orders.index'));
        $responseIndex->assertRedirect(route('login'));

        $responseShow = $this->get(route('admin.orders.show', $order));
        $responseShow->assertRedirect(route('login'));
    }

    /**
     * Test 2: Students receive 403 Forbidden on admin order routes.
     */
    public function test_student_cannot_access_admin_order_routes(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-0002',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $responseIndex = $this->actingAs($this->studentA)->get(route('admin.orders.index'));
        $responseIndex->assertForbidden();

        $responseShow = $this->actingAs($this->studentA)->get(route('admin.orders.show', $order));
        $responseShow->assertForbidden();
    }

    /**
     * Test 3: Authorized admin can view orders index and KPI metrics.
     */
    public function test_authorized_admin_can_view_orders_index(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-0003',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertOk();
        $response->assertSeeText('Orders & Transactions');
        $response->assertSee('Total Revenue');
        $response->assertSee('MM-2026-0003');
        $response->assertSee('John Buyer');
        $response->assertSee('₹1,999.00');
    }

    /**
     * Test 4: Authorized admin can view individual order details.
     */
    public function test_authorized_admin_can_view_order_details(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-0004',
            'razorpay_order_id' => 'order_rp_test_004',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertSee('MM-2026-0004');
        $response->assertSee('order_rp_test_004');
        $response->assertSee('John Buyer');
        $response->assertSee('Meta Ads Mastery 2026');
        $response->assertSee('Historical Transaction Amount');
        $response->assertSee('₹1,999.00');
    }

    /**
     * Test 5: Search by order number works.
     */
    public function test_search_by_order_number_works(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-UNIQUE-111',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-2026-OTHER-222',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'UNIQUE-111']));
        $response->assertOk();
        $response->assertSee('MM-2026-UNIQUE-111');
        $response->assertDontSee('MM-2026-OTHER-222');
    }

    /**
     * Test 6: Search by student name and email works.
     */
    public function test_search_by_student_name_and_email_works(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-STU-A',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-2026-STU-B',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        // Search by name
        $responseName = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'John Buyer']));
        $responseName->assertOk();
        $responseName->assertSee('MM-2026-STU-A');
        $responseName->assertDontSee('MM-2026-STU-B');

        // Search by email
        $responseEmail = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'emma@customer.com']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('MM-2026-STU-B');
        $responseEmail->assertDontSee('MM-2026-STU-A');
    }

    /**
     * Test 7: Search by course title works.
     */
    public function test_search_by_course_title_works(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-CRS-1',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-2026-CRS-2',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'Google Search Ads Pro']));
        $response->assertOk();
        $response->assertSee('MM-2026-CRS-2');
        $response->assertDontSee('MM-2026-CRS-1');
    }

    /**
     * Test 8: Search by Razorpay identifiers works.
     */
    public function test_search_by_razorpay_identifiers_works(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-2026-RP-1',
            'razorpay_order_id' => 'order_RPSEARCH123',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_RPPAYMENT456',
            'razorpay_order_id' => 'order_RPSEARCH123',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'captured' => true,
        ]);

        // Search by razorpay_order_id
        $responseOrder = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'RPSEARCH123']));
        $responseOrder->assertOk();
        $responseOrder->assertSee('MM-2026-RP-1');

        // Search by razorpay_payment_id
        $responsePayment = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'RPPAYMENT456']));
        $responsePayment->assertOk();
        $responsePayment->assertSee('MM-2026-RP-1');
    }

    /**
     * Test 9: Order status filter works.
     */
    public function test_order_status_filters_work(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-PAID-001',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-PENDING-002',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-FAILED-003',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        // Filter: paid
        $responsePaid = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'paid']));
        $responsePaid->assertOk();
        $responsePaid->assertSee('MM-PAID-001');
        $responsePaid->assertDontSee('MM-PENDING-002');
        $responsePaid->assertDontSee('MM-FAILED-003');

        // Filter: pending
        $responsePending = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'pending']));
        $responsePending->assertOk();
        $responsePending->assertSee('MM-PENDING-002');
        $responsePending->assertDontSee('MM-PAID-001');

        // Filter: failed
        $responseFailed = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'failed']));
        $responseFailed->assertOk();
        $responseFailed->assertSee('MM-FAILED-003');
        $responseFailed->assertDontSee('MM-PAID-001');
    }

    /**
     * Test 10: Payment status filter works.
     */
    public function test_payment_status_filter_works(): void
    {
        $order1 = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-PAY-CAPTURED',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Payment::create([
            'order_id' => $order1->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_cap_001',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'captured' => true,
        ]);

        $order2 = Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-PAY-FAILED',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        Payment::create([
            'order_id' => $order2->id,
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'razorpay_payment_id' => 'pay_fail_002',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
            'captured' => false,
        ]);

        $responseCaptured = $this->actingAs($this->admin)->get(route('admin.orders.index', ['payment_status' => 'captured']));
        $responseCaptured->assertOk();
        $responseCaptured->assertSee('MM-PAY-CAPTURED');
        $responseCaptured->assertDontSee('MM-PAY-FAILED');

        $responseFailed = $this->actingAs($this->admin)->get(route('admin.orders.index', ['payment_status' => 'failed']));
        $responseFailed->assertOk();
        $responseFailed->assertSee('MM-PAY-FAILED');
        $responseFailed->assertDontSee('MM-PAY-CAPTURED');
    }

    /**
     * Test 11: Course filter works.
     */
    public function test_course_filter_works(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-CRS-FILTER-1',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-CRS-FILTER-2',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['course' => $this->course1->slug]));
        $response->assertOk();
        $response->assertSee('MM-CRS-FILTER-1');
        $response->assertDontSee('MM-CRS-FILTER-2');
        $this->assertCount(1, $response->viewData('orders'));
    }

    /**
     * Test 12: Date filter works.
     */
    public function test_date_filter_works(): void
    {
        $todayOrder = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-DATE-TODAY',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $pastOrder = Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-DATE-PAST',
            'amount' => 299900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);
        $pastOrder->created_at = now()->subMonths(3);
        $pastOrder->save();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['date' => 'today']));
        $response->assertOk();
        $response->assertSee('MM-DATE-TODAY');
        $response->assertDontSee('MM-DATE-PAST');
    }

    /**
     * Test 13: Sorting works and sanitizes malicious input.
     */
    public function test_sorting_works_and_sanitizes_input(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-LOW-AMOUNT',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'created_at' => now()->subDay(),
        ]);

        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-HIGH-AMOUNT',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'created_at' => now(),
        ]);

        // Sort: amount_high
        $responseHigh = $this->actingAs($this->admin)->get(route('admin.orders.index', ['sort' => 'amount_high']));
        $responseHigh->assertOk();
        $this->assertEquals('MM-HIGH-AMOUNT', $responseHigh->viewData('orders')->first()->order_number);

        // Sort: amount_low
        $responseLow = $this->actingAs($this->admin)->get(route('admin.orders.index', ['sort' => 'amount_low']));
        $responseLow->assertOk();
        $this->assertEquals('MM-LOW-AMOUNT', $responseLow->viewData('orders')->first()->order_number);

        // SQL injection payload does not crash
        $responseMalicious = $this->actingAs($this->admin)->get(route('admin.orders.index', ['sort' => 'amount; DROP TABLE orders;--']));
        $responseMalicious->assertOk();
    }

    /**
     * Test 14: Pagination works and preserves query parameters.
     */
    public function test_pagination_works_and_preserves_query_parameters(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'user_id' => $this->studentA->id,
                'course_id' => $this->course1->id,
                'order_number' => sprintf('MM-PAGE-%03d', $i),
                'amount' => 199900,
                'currency' => 'INR',
                'status' => OrderStatus::PAID,
            ]);
        }

        $responsePage2 = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'status' => 'paid',
            'page' => 2,
        ]));
        $responsePage2->assertOk();
        $this->assertCount(5, $responsePage2->viewData('orders'));
        $responsePage2->assertSee('status=paid');
    }

    /**
     * Test 15: Historical order amount is preserved even when course price changes.
     */
    public function test_historical_order_amount_is_preserved_when_course_price_changes(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-HISTORICAL-001',
            'amount' => 99900, // ₹999.00 purchased originally
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Course price is updated later in catalog
        $this->course1->update(['price' => 4999.00]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        // Preserves historical ₹999.00
        $response->assertSee('₹999.00');
        // Catalog price also displayed for comparison
        $response->assertSee('₹4,999.00');
    }

    /**
     * Test 16: Revenue calculation is accurate and excludes pending, failed, cancelled orders.
     */
    public function test_revenue_calculation_is_accurate_and_excludes_non_paid_orders(): void
    {
        // Paid: ₹1,000
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-REV-PAID-1',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        // Paid: ₹2,500
        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-REV-PAID-2',
            'amount' => 250000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        // Pending: ₹3,000 (must be excluded)
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-REV-PENDING',
            'amount' => 300000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Failed: ₹4,000 (must be excluded)
        Order::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-REV-FAILED',
            'amount' => 400000,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        // Cancelled: ₹5,000 (must be excluded)
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course2->id,
            'order_number' => 'MM-REV-CANCELLED',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::CANCELLED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        // Total revenue = 1000 + 2500 = 3500.00
        $this->assertEquals(3500.00, $metrics['total_revenue']);
        $this->assertEquals('₹3,500.00', $metrics['formatted_revenue']);
        $this->assertEquals(2, $metrics['paid_orders']);
        $this->assertEquals(1, $metrics['pending_orders']);
        $this->assertEquals(1, $metrics['failed_orders']);
        $this->assertEquals(1, $metrics['cancelled_orders']);
        $this->assertEquals(5, $metrics['total_orders']);
    }

    /**
     * Test 17: Double-counting protection - multiple payment records for one order do NOT double count revenue.
     */
    public function test_multiple_payment_attempts_do_not_double_count_revenue(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-MULTI-PAY',
            'amount' => 150000, // ₹1,500.00
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // Attempt 1 failed
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_attempt_1',
            'amount' => 150000,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
            'captured' => false,
        ]);

        // Attempt 2 failed
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_attempt_2',
            'amount' => 150000,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
            'captured' => false,
        ]);

        // Attempt 3 succeeded
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_attempt_3',
            'amount' => 150000,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'captured' => true,
            'method' => 'upi',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        // Authoritative revenue is ₹1,500.00, NOT ₹4,500.00!
        $this->assertEquals(1500.00, $metrics['total_revenue']);
        $this->assertEquals('₹1,500.00', $metrics['formatted_revenue']);
    }

    /**
     * Test 18: Payment details, method, and failure info displayed safely on show page.
     */
    public function test_payment_details_and_failure_info_displayed_safely(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-AUDIT-SHOW',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_audit_test_99',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'method' => 'netbanking',
            'captured' => true,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'razorpay_payment_id' => 'pay_audit_fail_99',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => PaymentStatus::FAILED,
            'failure_code' => 'BAD_REQUEST_ERROR',
            'failure_description' => 'Card expired or declined by issuing bank.',
            'captured' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertSee('pay_audit_test_99');
        $response->assertSee('Netbanking');
        $response->assertSee('pay_audit_fail_99');
        $response->assertSee('BAD_REQUEST_ERROR');
        $response->assertSee('Card expired or declined by issuing bank.');

        // Verify secrets are NOT exposed
        $response->assertDontSee('key_secret');
        $response->assertDontSee('webhook_secret');
    }

    /**
     * Test 19: Enrollment relationship is displayed when present.
     */
    public function test_enrollment_relationship_is_displayed_when_present(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-ENROLL-CHK',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertSee('Enrollment Granted');
        $response->assertSee(route('admin.enrollments.show', $enrollment));
    }

    /**
     * Test 20: Non-existent order returns 404.
     */
    public function test_non_existent_order_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', 99999));
        $response->assertNotFound();
    }

    /**
     * Test 21: Refunded orders are excluded from revenue and filtered correctly.
     */
    public function test_refunded_orders_are_excluded_from_revenue_and_filtered_correctly(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-REFUNDED-01',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::REFUNDED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'refunded']));
        $response->assertOk();
        $response->assertSee('MM-REFUNDED-01');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(0, $metrics['total_revenue']);
        $this->assertEquals(1, $metrics['refunded_orders']);
    }

    /**
     * Test 22: Order routes are strictly read-only and reject modifications.
     */
    public function test_order_routes_are_strictly_read_only(): void
    {
        $order = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-READONLY-01',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Attempting to PUT or DELETE returns 405 Method Not Allowed
        $responsePut = $this->actingAs($this->admin)->put("/admin/orders/{$order->id}", ['status' => 'paid']);
        $responsePut->assertStatus(405);

        $responseDelete = $this->actingAs($this->admin)->delete("/admin/orders/{$order->id}");
        $responseDelete->assertStatus(405);
    }

    /**
     * Test 23: Admin dashboard revenue is consistent with orders page revenue.
     */
    public function test_admin_dashboard_revenue_is_consistent_with_orders_page(): void
    {
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'MM-CONSISTENCY-01',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $responseOrders = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $ordersRevenue = $responseOrders->viewData('metrics')['total_revenue'];

        $responseDashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashboardRevenue = $responseDashboard->viewData('metrics')['total_revenue'];

        $this->assertEquals($dashboardRevenue, $ordersRevenue);
    }
}