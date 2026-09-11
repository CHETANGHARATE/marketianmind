<?php

namespace Tests\Feature;

use App\Enums\CouponDiscountType;
use App\Enums\CourseStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery-' . uniqid(),
            'short_description' => 'Comprehensive marketing masterclass',
            'price' => 2000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Mock RazorpayService to prevent external API calls
        $this->mock(RazorpayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getKeyId')->andReturn('rzp_test_mock_key');
            $mock->shouldReceive('createOrder')->andReturn(['id' => 'order_mock_123']);
        });
    }

    protected function createPendingOrder(int $amountInPaise = 200000): Order
    {
        return Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-' . uniqid(),
            'original_amount' => $amountInPaise,
            'discount_amount' => 0,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);
    }

    public function test_student_can_apply_valid_percentage_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'DISCOUNT20',
            'name' => '20 Percent Off',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000); // ₹2,000

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'discount20', // test lowercase
            ]);

        $response->assertRedirect(route('student.courses.checkout', $order));
        $response->assertSessionHas('status');

        $order->refresh();
        $this->assertEquals('DISCOUNT20', $order->coupon_code);
        $this->assertEquals(40000, $order->discount_amount); // ₹400
        $this->assertEquals(160000, $order->amount); // ₹1,600
        $this->assertEquals(200000, $order->original_amount);
    }

    public function test_student_can_apply_valid_fixed_amount_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'FLAT500',
            'name' => 'Flat ₹500 Off',
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 50000, // ₹500 in paise
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000); // ₹2,000

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'FLAT500',
            ]);

        $response->assertRedirect(route('student.courses.checkout', $order));

        $order->refresh();
        $this->assertEquals('FLAT500', $order->coupon_code);
        $this->assertEquals(50000, $order->discount_amount); // ₹500
        $this->assertEquals(150000, $order->amount); // ₹1,500
    }

    public function test_percentage_coupon_respects_max_discount_cap(): void
    {
        $coupon = Coupon::create([
            'code' => 'HALFPRICE',
            'name' => '50% with ₹500 cap',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 50, // would be ₹1,000 on ₹2,000
            'max_discount_amount' => 50000, // capped at ₹500
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'HALFPRICE',
            ]);

        $order->refresh();
        $this->assertEquals(50000, $order->discount_amount); // Capped at ₹500
        $this->assertEquals(150000, $order->amount); // ₹1,500
    }

    public function test_cannot_apply_inactive_coupon(): void
    {
        Coupon::create([
            'code' => 'INACTIVE',
            'name' => 'Inactive Code',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'is_active' => false,
        ]);

        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'INACTIVE',
            ]);

        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertNull($order->coupon_code);
        $this->assertEquals(200000, $order->amount);
    }

    public function test_cannot_apply_expired_coupon(): void
    {
        Coupon::create([
            'code' => 'EXPIRED',
            'name' => 'Expired Code',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'EXPIRED',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_cannot_apply_upcoming_coupon(): void
    {
        Coupon::create([
            'code' => 'FUTURE',
            'name' => 'Future Code',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'starts_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'FUTURE',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_cannot_apply_coupon_below_minimum_order_amount(): void
    {
        Coupon::create([
            'code' => 'MINORDER',
            'name' => 'Requires ₹5,000 order',
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 10000,
            'min_order_amount' => 500000, // ₹5,000 min order
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000); // Order is ₹2,000

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'MINORDER',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_cannot_apply_coupon_exceeding_global_usage_limit(): void
    {
        Coupon::create([
            'code' => 'LIMITED',
            'name' => 'Limit reached',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'usage_limit' => 5,
            'times_used' => 5,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'LIMITED',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_cannot_apply_coupon_exceeding_per_user_limit(): void
    {
        $coupon = Coupon::create([
            'code' => 'ONCEONLY',
            'name' => 'One time only',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        // Record a past usage by this student
        $pastOrder = $this->createPendingOrder(100000);
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $this->student->id,
            'order_id' => $pastOrder->id,
            'discount_amount' => 10000,
        ]);

        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'ONCEONLY',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_course_specific_coupon_rejected_on_different_course(): void
    {
        $otherCourse = Course::create([
            'title' => 'SEO Secrets',
            'slug' => 'seo-secrets-' . uniqid(),
            'short_description' => 'Comprehensive SEO and growth strategies.',
            'price' => 1500.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Coupon::create([
            'code' => 'SPECIFIC',
            'name' => 'Specific course only',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'course_id' => $otherCourse->id,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000); // Uses $this->course

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'SPECIFIC',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_student_can_remove_applied_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'REMOVEME',
            'name' => 'To be removed',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 25,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        // First apply
        $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'REMOVEME',
            ]);

        $order->refresh();
        $this->assertEquals('REMOVEME', $order->coupon_code);

        // Now remove
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.remove-coupon', $order));

        $response->assertRedirect(route('student.courses.checkout', $order));

        $order->refresh();
        $this->assertNull($order->coupon_code);
        $this->assertNull($order->coupon_id);
        $this->assertEquals(0, $order->discount_amount);
        $this->assertEquals(200000, $order->amount);
    }

    public function test_other_student_cannot_modify_foreign_order(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);
        $order = $this->createPendingOrder(200000);

        $response = $this->actingAs($otherStudent)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'ANY',
            ]);

        $response->assertForbidden();
    }

    public function test_100_percent_discount_allows_instant_free_enrollment(): void
    {
        $coupon = Coupon::create([
            'code' => '100FREE',
            'name' => '100 Percent Scholarship',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 100,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        // Apply 100% coupon
        $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => '100FREE',
            ]);

        $order->refresh();
        $this->assertEquals(0, $order->amount);
        $this->assertEquals(200000, $order->discount_amount);

        // Submit free completion
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.checkout.complete-free', $order));

        $response->assertRedirect(route('payment.success', $order));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertTrue($this->student->isEnrolledIn($this->course));

        // Verify coupon usage recorded
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->student->id,
            'order_id' => $order->id,
            'discount_amount' => 200000,
        ]);

        $coupon->refresh();
        $this->assertEquals(1, $coupon->times_used);
    }

    public function test_client_cannot_tamper_with_final_price(): void
    {
        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'name' => 'Save 10 Percent',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder(200000);

        // Client attempts to pass tampered amount or discount in request
        $this->actingAs($this->student)
            ->post(route('student.courses.checkout.apply-coupon', $order), [
                'coupon_code' => 'SAVE10',
                'amount' => 100, // forged amount
                'discount_amount' => 199900, // forged discount
            ]);

        $order->refresh();
        // Server authoritative calculation MUST prevail
        $this->assertEquals(20000, $order->discount_amount); // Exactly 10% of ₹2000 = ₹200 (20,000 paise)
        $this->assertEquals(180000, $order->amount); // Exactly ₹1,800
    }
}