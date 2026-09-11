<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReferralStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReferralAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_link_redirects_and_sets_cookie_and_session(): void
    {
        $referrer = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);
        $code = $referrer->getReferralCode();

        $response = $this->get(route('referral.capture', ['code' => $code]));

        $response->assertRedirect(route('register', ['ref' => $code]));
        $response->assertSessionHas('referral_code', $code);
        $response->assertCookie(ReferralService::COOKIE_NAME, $code);
    }

    public function test_registration_with_referral_code_creates_referral_record(): void
    {
        $referrer = User::factory()->create(['role' => UserRole::STUDENT]);
        $code = $referrer->getReferralCode();

        $response = $this->post(route('register'), [
            'name' => 'Alice Friend',
            'email' => 'alice@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'ref' => $code,
        ]);

        $this->assertAuthenticated();
        $referredUser = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($referredUser);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $referredUser->id,
            'referral_code' => $code,
            'status' => ReferralStatus::REGISTERED->value,
        ]);
    }

    public function test_registration_with_referral_cookie_creates_referral_record(): void
    {
        $referrer = User::factory()->create(['role' => UserRole::STUDENT]);
        $code = $referrer->getReferralCode();

        $response = $this->withCookie(ReferralService::COOKIE_NAME, $code)
            ->post(route('register'), [
                'name' => 'Bob Cookie',
                'email' => 'bob@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $this->assertAuthenticated();
        $referredUser = User::where('email', 'bob@example.com')->first();
        $this->assertNotNull($referredUser);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $referredUser->id,
            'referral_code' => $code,
            'status' => ReferralStatus::REGISTERED->value,
        ]);
    }

    public function test_self_referral_is_prevented(): void
    {
        $user = User::factory()->create(['role' => UserRole::STUDENT]);
        $code = $user->getReferralCode();

        $referral = app(ReferralService::class)->attributeRegistration($user, $code);

        $this->assertNull($referral);
        $this->assertDatabaseMissing('referrals', [
            'referrer_id' => $user->id,
            'referred_id' => $user->id,
        ]);
    }

    public function test_duplicate_referral_attribution_is_prevented(): void
    {
        $referrer1 = User::factory()->create(['role' => UserRole::STUDENT]);
        $referrer2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $referred = User::factory()->create(['role' => UserRole::STUDENT]);

        // First attribution succeeds
        $first = app(ReferralService::class)->attributeRegistration($referred, $referrer1->getReferralCode());
        $this->assertNotNull($first);

        // Second attribution attempt for the same referred user must fail
        $second = app(ReferralService::class)->attributeRegistration($referred, $referrer2->getReferralCode());
        $this->assertNull($second);

        $this->assertEquals(1, Referral::where('referred_id', $referred->id)->count());
    }

    public function test_invalid_referral_code_does_not_break_registration(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Charlie Neutral',
            'email' => 'charlie@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'ref' => 'NONEXISTENT_CODE_999',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'charlie@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseMissing('referrals', [
            'referred_id' => $user->id,
        ]);
    }

    public function test_order_payment_converts_referral(): void
    {
        $referrer = User::factory()->create(['role' => UserRole::STUDENT]);
        $referred = User::factory()->create(['role' => UserRole::STUDENT]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'referral_code' => $referrer->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $course = Course::create([
            'title' => 'Digital Marketing Pro',
            'slug' => 'digital-marketing-pro',
            'short_description' => 'Course short description',
            'description' => 'Course description',
            'price' => 4999.00,
            'is_free' => false,
            'status' => \App\Enums\CourseStatus::PUBLISHED,
        ]);

        $order = Order::create([
            'user_id' => $referred->id,
            'course_id' => $course->id,
            'order_number' => 'ORD-TEST-001',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_test_razor_123',
        ]);

        // Mock payment verification / execution
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $referred->id,
            'course_id' => $course->id,
            'razorpay_payment_id' => 'pay_test_razor_123',
            'razorpay_order_id' => 'order_test_razor_123',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
            'captured' => true,
            'paid_at' => now(),
        ]);

        $order->markPaid();
        app(ReferralService::class)->attributeConversion($order);

        $referral->refresh();
        $this->assertEquals(ReferralStatus::CONVERTED, $referral->status);
        $this->assertEquals($order->id, $referral->order_id);
        $this->assertNotNull($referral->converted_at);
    }

    public function test_multiple_orders_by_same_referred_user_do_not_duplicate_conversion(): void
    {
        $referrer = User::factory()->create(['role' => UserRole::STUDENT]);
        $referred = User::factory()->create(['role' => UserRole::STUDENT]);

        $course1 = Course::create([
            'title' => 'Course 1',
            'slug' => 'course-1',
            'short_description' => 'Short 1',
            'price' => 1000.00,
            'is_free' => false,
            'status' => \App\Enums\CourseStatus::PUBLISHED,
        ]);
        $course2 = Course::create([
            'title' => 'Course 2',
            'slug' => 'course-2',
            'short_description' => 'Short 2',
            'price' => 2000.00,
            'is_free' => false,
            'status' => \App\Enums\CourseStatus::PUBLISHED,
        ]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'referral_code' => $referrer->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $order1 = Order::create([
            'user_id' => $referred->id,
            'course_id' => $course1->id,
            'order_number' => 'ORD-TEST-002',
            'amount' => 1000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'razorpay_order_id' => 'order_test_2',
        ]);

        app(ReferralService::class)->attributeConversion($order1);
        $referral->refresh();
        $this->assertEquals($order1->id, $referral->order_id);

        // Second order
        $order2 = Order::create([
            'user_id' => $referred->id,
            'course_id' => $course2->id,
            'order_number' => 'ORD-TEST-003',
            'amount' => 2000,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'razorpay_order_id' => 'order_test_3',
        ]);

        $secondConversion = app(ReferralService::class)->attributeConversion($order2);
        $this->assertNull($secondConversion);

        // Order 1 remains attributed
        $referral->refresh();
        $this->assertEquals($order1->id, $referral->order_id);
    }
}