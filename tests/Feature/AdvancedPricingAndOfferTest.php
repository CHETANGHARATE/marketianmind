<?php

namespace Tests\Feature;

use App\Enums\BundleStatus;
use App\Enums\CouponDiscountType;
use App\Enums\CourseStatus;
use App\Enums\OfferDiscountType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Bundle;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\PricingService;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdvancedPricingAndOfferTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;
    protected Course $freeCourse;
    protected Bundle $bundle1;
    protected PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = app(PricingService::class);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@marketianmind.test',
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'email' => 'student@marketianmind.test',
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'is_active' => true,
        ]);

        // Regular course without discount price
        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Complete Growth Marketing',
            'slug' => 'complete-growth-marketing',
            'short_description' => 'Growth strategies for startups.',
            'price' => 2000.00,
            'discount_price' => null,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Course with an existing discount price
        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Advanced SEO Bootcamp',
            'slug' => 'advanced-seo-bootcamp',
            'short_description' => 'Master organic search.',
            'price' => 3000.00,
            'discount_price' => 1500.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Free course
        $this->freeCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Marketing Basics 101',
            'slug' => 'marketing-basics-101',
            'short_description' => 'Free intro course.',
            'price' => 0.00,
            'discount_price' => null,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Course bundle
        $this->bundle1 = Bundle::create([
            'title' => 'Founder Growth Package',
            'slug' => 'founder-growth-package',
            'short_description' => 'Ultimate startup founder stack.',
            'description' => 'Everything you need to launch and scale.',
            'price' => 2500.00,
            'status' => BundleStatus::PUBLISHED,
            'featured' => true,
        ]);
        $this->bundle1->courses()->attach([$this->course1->id, $this->course2->id]);
    }

    // =========================================================================
    // SECTION 1: PRICE RESOLUTION TESTS
    // =========================================================================

    public function test_normal_course_price_resolves_base_price_when_no_offer_active(): void
    {
        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertEquals(2000.00, $pricing['base_price']);
        $this->assertEquals(200000, $pricing['base_price_in_paise']);
        $this->assertFalse($pricing['has_offer']);
        $this->assertNull($pricing['offer']);
        $this->assertEquals(0.00, $pricing['offer_discount']);
        $this->assertEquals(2000.00, $pricing['final_price']);
        $this->assertEquals(200000, $pricing['final_price_in_paise']);
        $this->assertEquals(0.00, $pricing['total_savings']);
        $this->assertEquals('₹2,000.00', $pricing['formatted_final_price']);
    }

    public function test_existing_course_discount_price_is_preserved_as_base(): void
    {
        // Course 2 has price=3000, discount_price=1500 -> base should be 1500
        $pricing = $this->pricingService->resolveForProduct($this->course2);

        $this->assertEquals(1500.00, $pricing['base_price']);
        $this->assertEquals(150000, $pricing['base_price_in_paise']);
        $this->assertEquals(1500.00, $pricing['final_price']);
    }

    public function test_active_percentage_offer_calculates_discount_correctly(): void
    {
        $offer = Offer::create([
            'title' => 'Diwali Flash Sale',
            'slug' => 'diwali-flash-sale',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 25.00, // 25% off
            'is_active' => true,
            'priority' => 1,
        ]);
        $offer->courses()->attach($this->course1->id);

        // Course 1 base price: 2000. 25% of 2000 = 500 discount -> final 1500
        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertTrue($pricing['has_offer']);
        $this->assertEquals($offer->id, $pricing['offer']->id);
        $this->assertEquals(500.00, $pricing['offer_discount']);
        $this->assertEquals(50000, $pricing['offer_discount_in_paise']);
        $this->assertEquals(1500.00, $pricing['final_price']);
        $this->assertEquals(150000, $pricing['final_price_in_paise']);
        $this->assertEquals(25, $pricing['savings_percentage']);
        $this->assertEquals('₹1,500.00', $pricing['formatted_final_price']);
    }

    public function test_active_fixed_offer_calculates_discount_correctly(): void
    {
        $offer = Offer::create([
            'title' => 'Flat 400 Off Special',
            'slug' => 'flat-400-off-special',
            'discount_type' => OfferDiscountType::FIXED,
            'discount_value' => 400.00, // ₹400 off
            'is_active' => true,
            'priority' => 1,
        ]);
        $offer->courses()->attach($this->course1->id);

        // Course 1 base: 2000. Fixed discount: 400 -> final 1600
        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertTrue($pricing['has_offer']);
        $this->assertEquals(400.00, $pricing['offer_discount']);
        $this->assertEquals(40000, $pricing['offer_discount_in_paise']);
        $this->assertEquals(1600.00, $pricing['final_price']);
        $this->assertEquals(160000, $pricing['final_price_in_paise']);
        $this->assertEquals(20, $pricing['savings_percentage']);
    }

    public function test_percentage_offer_on_course_with_existing_discount_applies_to_discounted_base(): void
    {
        // Course 2 has discount_price=1500. Offer is 20% off.
        // 20% of 1500 = 300 discount -> final price = 1200
        $offer = Offer::create([
            'title' => 'Extra 20 Off',
            'slug' => 'extra-20-off',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course2->id);

        $pricing = $this->pricingService->resolveForProduct($this->course2);

        $this->assertEquals(1500.00, $pricing['base_price']);
        $this->assertEquals(300.00, $pricing['offer_discount']);
        $this->assertEquals(1200.00, $pricing['final_price']);
        $this->assertEquals(120000, $pricing['final_price_in_paise']);
    }

    public function test_fixed_discount_exceeding_base_price_caps_at_zero(): void
    {
        $offer = Offer::create([
            'title' => 'Mega Credit',
            'slug' => 'mega-credit',
            'discount_type' => OfferDiscountType::FIXED,
            'discount_value' => 5000.00, // exceeds course1 price of 2000
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertEquals(2000.00, $pricing['offer_discount']);
        $this->assertEquals(0.00, $pricing['final_price']);
        $this->assertEquals(0, $pricing['final_price_in_paise']);
    }

    public function test_expired_offer_is_not_applied(): void
    {
        $offer = Offer::create([
            'title' => 'Expired Summer Deal',
            'slug' => 'expired-summer-deal',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 50.00,
            'is_active' => true,
            'starts_at' => Carbon::now()->subDays(10),
            'ends_at' => Carbon::now()->subDays(1), // ended yesterday
        ]);
        $offer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertFalse($pricing['has_offer']);
        $this->assertEquals(2000.00, $pricing['final_price']);
    }

    public function test_future_scheduled_offer_is_not_applied(): void
    {
        $offer = Offer::create([
            'title' => 'Upcoming Black Friday',
            'slug' => 'upcoming-black-friday',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 50.00,
            'is_active' => true,
            'starts_at' => Carbon::now()->addDays(5), // starts in 5 days
            'ends_at' => Carbon::now()->addDays(10),
        ]);
        $offer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertFalse($pricing['has_offer']);
        $this->assertEquals(2000.00, $pricing['final_price']);
    }

    public function test_disabled_offer_is_not_applied(): void
    {
        $offer = Offer::create([
            'title' => 'Disabled Promo',
            'slug' => 'disabled-promo',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 30.00,
            'is_active' => false, // disabled
        ]);
        $offer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertFalse($pricing['has_offer']);
        $this->assertEquals(2000.00, $pricing['final_price']);
    }

    public function test_offer_reaching_usage_limit_is_not_applied(): void
    {
        $offer = Offer::create([
            'title' => 'Limited Seats Offer',
            'slug' => 'limited-seats-offer',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 40.00,
            'is_active' => true,
            'usage_limit' => 5,
            'times_used' => 5, // fully consumed
        ]);
        $offer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        $this->assertFalse($pricing['has_offer']);
        $this->assertEquals(2000.00, $pricing['final_price']);
    }

    public function test_multiple_offers_resolve_by_highest_priority(): void
    {
        // Low priority offer (priority 1, 30% off)
        $lowPriorityOffer = Offer::create([
            'title' => 'Standard Discount',
            'slug' => 'standard-discount',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 30.00,
            'priority' => 1,
            'is_active' => true,
        ]);
        $lowPriorityOffer->courses()->attach($this->course1->id);

        // High priority offer (priority 10, 20% off)
        $highPriorityOffer = Offer::create([
            'title' => 'VIP Flash Deal',
            'slug' => 'vip-flash-deal',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00,
            'priority' => 10,
            'is_active' => true,
        ]);
        $highPriorityOffer->courses()->attach($this->course1->id);

        $pricing = $this->pricingService->resolveForProduct($this->course1);

        // High priority offer should win even if low priority has higher discount
        $this->assertTrue($pricing['has_offer']);
        $this->assertEquals($highPriorityOffer->id, $pricing['offer']->id);
        $this->assertEquals(400.00, $pricing['offer_discount']); // 20% of 2000
        $this->assertEquals(1600.00, $pricing['final_price']);
    }

    public function test_free_courses_remain_free_and_ignore_offers(): void
    {
        $offer = Offer::create([
            'title' => 'Free Course Promo',
            'slug' => 'free-course-promo',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 50.00,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->freeCourse->id);

        $pricing = $this->pricingService->resolveForProduct($this->freeCourse);

        $this->assertEquals(0.00, $pricing['base_price']);
        $this->assertFalse($pricing['has_offer']);
        $this->assertEquals(0.00, $pricing['final_price']);
        $this->assertEquals(0, $pricing['final_price_in_paise']);
        $this->assertEquals('Free', $pricing['formatted_final_price']);
    }

    public function test_bundle_promotional_offer_resolves_correctly(): void
    {
        // Bundle price is 2500. Offer gives 20% off.
        // Discount: 500 -> final: 2000
        $offer = Offer::create([
            'title' => 'Bundle Super Saver',
            'slug' => 'bundle-super-saver',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00,
            'is_active' => true,
        ]);
        $offer->bundles()->attach($this->bundle1->id);

        $pricing = $this->pricingService->resolveForProduct($this->bundle1);

        $this->assertTrue($pricing['has_offer']);
        $this->assertEquals('bundle', $pricing['product_type']);
        $this->assertEquals(2500.00, $pricing['base_price']);
        $this->assertEquals(500.00, $pricing['offer_discount']);
        $this->assertEquals(2000.00, $pricing['final_price']);
        $this->assertEquals(200000, $pricing['final_price_in_paise']);
        $this->assertEquals('₹2,000.00', $pricing['formatted_final_price']);
    }

    // =========================================================================
    // SECTION 2: COUPON STACKING COMBINATION RULES
    // =========================================================================

    public function test_coupon_cannot_stack_when_offer_disallows_coupons(): void
    {
        // Offer with allow_coupons = false (default)
        $offer = Offer::create([
            'title' => 'Exclusive Summer Offer',
            'slug' => 'exclusive-summer-offer',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00,
            'allow_coupons' => false,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $coupon = Coupon::create([
            'name' => 'Extra 10% Off',
            'code' => 'EXTRA10',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
        ]);

        $pricing = $this->pricingService->resolveForProduct($this->course1, $this->student, 'EXTRA10');

        $this->assertTrue($pricing['has_offer']);
        $this->assertFalse($pricing['has_coupon']);
        $this->assertEquals(400.00, $pricing['offer_discount']);
        $this->assertEquals(0.00, $pricing['coupon_discount']);
        $this->assertEquals(1600.00, $pricing['final_price']);
        $this->assertNotNull($pricing['coupon_error']);
        $this->assertStringContainsString('Coupons cannot be combined', $pricing['coupon_error']);
    }

    public function test_coupon_stacks_on_top_of_offer_when_offer_allows_coupons(): void
    {
        // Offer with allow_coupons = true
        $offer = Offer::create([
            'title' => 'Stackable Fest Promo',
            'slug' => 'stackable-fest-promo',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00, // 20% off 2000 = 1600 offer price
            'allow_coupons' => true,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $coupon = Coupon::create([
            'name' => 'Save 10% Off',
            'code' => 'SAVE10',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10.00, // 10% off 1600 = 160 coupon discount
            'is_active' => true,
        ]);

        $pricing = $this->pricingService->resolveForProduct($this->course1, $this->student, 'SAVE10');

        $this->assertTrue($pricing['has_offer']);
        $this->assertTrue($pricing['has_coupon']);
        $this->assertEquals(400.00, $pricing['offer_discount']);
        $this->assertEquals(160.00, $pricing['coupon_discount']);
        $this->assertEquals(1440.00, $pricing['final_price']); // 2000 - 400 - 160 = 1440
        $this->assertEquals(144000, $pricing['final_price_in_paise']);
        $this->assertEquals(560.00, $pricing['total_savings']);
        $this->assertNull($pricing['coupon_error']);
    }

    // =========================================================================
    // SECTION 3: CHECKOUT & SERVER-SIDE PRICE ENFORCEMENT
    // =========================================================================

    public function test_checkout_enforces_server_side_pricing_with_active_offer(): void
    {
        $offer = Offer::create([
            'title' => 'Launch Special',
            'slug' => 'launch-special',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 25.00, // 25% off 2000 = 1500 (150000 paise)
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        // Mock RazorpayService to expect 150000 paise
        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('createOrder')
            ->once()
            ->withArgs(function ($amountInPaise, $receipt, $notes) {
                return $amountInPaise === 150000;
            })
            ->andReturn([
                'id' => 'order_mock_offer_123',
                'amount' => 150000,
                'currency' => 'INR',
                'status' => 'created',
            ]);

        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course1), [
                'tampered_price' => 10.00, // Malicious input ignored
            ]);

        $order = Order::where('user_id', $this->student->id)->latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('student.courses.checkout', $order));

        $this->assertEquals($offer->id, $order->offer_id);
        $this->assertEquals(50000, $order->offer_discount_amount);
        $this->assertEquals(150000, $order->amount);
        $this->assertEquals(200000, $order->original_amount);
    }

    public function test_bundle_checkout_enforces_server_side_pricing_with_active_offer(): void
    {
        $offer = Offer::create([
            'title' => 'Bundle 30% Off',
            'slug' => 'bundle-30-off',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 30.00, // 30% off 2500 = 750 discount -> 1750 (175000 paise)
            'is_active' => true,
        ]);
        $offer->bundles()->attach($this->bundle1->id);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('createOrder')
            ->once()
            ->withArgs(function ($amountInPaise, $receipt, $notes) {
                return $amountInPaise === 175000;
            })
            ->andReturn([
                'id' => 'order_mock_bundle_offer_456',
                'amount' => 175000,
                'currency' => 'INR',
                'status' => 'created',
            ]);

        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->student)
            ->post(route('student.bundles.purchase', $this->bundle1));

        $order = Order::where('user_id', $this->student->id)->where('bundle_id', $this->bundle1->id)->latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('student.courses.checkout', $order));

        $this->assertEquals($offer->id, $order->offer_id);
        $this->assertEquals(75000, $order->offer_discount_amount);
        $this->assertEquals(175000, $order->amount);
        $this->assertEquals(250000, $order->original_amount);
    }

    // =========================================================================
    // SECTION 4: USAGE LIMIT INCREMENT ON CONFIRMED PAYMENT
    // =========================================================================

    public function test_offer_times_used_increments_on_payment_verification(): void
    {
        $offer = Offer::create([
            'title' => 'Verification Promo',
            'slug' => 'verification-promo',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
            'times_used' => 0,
        ]);
        $offer->courses()->attach($this->course1->id);

        // Order created with offer
        $order = Order::create([
            'order_number' => 'ORD-TEST-OFFER-001',
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'bundle_id' => null,
            'offer_id' => $offer->id,
            'offer_discount_amount' => 20000,
            'original_amount' => 200000,
            'discount_amount' => 20000,
            'amount' => 180000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_rzp_ver_123',
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(true);

        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_rzp_ver_123',
                'razorpay_payment_id' => 'pay_rzp_ver_123',
                'razorpay_signature' => 'valid_mock_sig',
            ]);

        $response->assertRedirect(route('payment.success', $order));

        // Assert offer times_used was incremented
        $offer->refresh();
        $this->assertEquals(1, $offer->times_used);
    }

    public function test_offer_times_used_increments_on_webhook_order_paid(): void
    {
        $offer = Offer::create([
            'title' => 'Webhook Promo',
            'slug' => 'webhook-promo',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 15.00,
            'is_active' => true,
            'times_used' => 2,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-WEBHOOK-002',
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'offer_id' => $offer->id,
            'offer_discount_amount' => 30000,
            'original_amount' => 200000,
            'discount_amount' => 30000,
            'amount' => 170000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'razorpay_order_id' => 'order_rzp_webhook_789',
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $payload = [
            'id' => 'evt_test_offer_001',
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_rzp_webhook_789',
                        'amount' => 170000,
                        'status' => 'paid',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_rzp_webhook_789',
                        'order_id' => 'order_rzp_webhook_789',
                        'amount' => 170000,
                        'status' => 'captured',
                        'method' => 'upi',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('webhooks.razorpay'), $payload, [
            'X-Razorpay-Signature' => 'dummy_signature',
            'X-Razorpay-Event-Id' => 'evt_test_offer_001',
        ]);

        $response->assertStatus(200);

        $offer->refresh();
        $this->assertEquals(3, $offer->times_used);
    }

    // =========================================================================
    // SECTION 5: ADMIN CRUD & VALIDATION & AUTHORIZATION
    // =========================================================================

    public function test_guest_and_student_cannot_access_admin_offers(): void
    {
        $this->get(route('admin.offers.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->student)
            ->get(route('admin.offers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_offers_index(): void
    {
        Offer::create([
            'title' => 'Catalog Listing Offer',
            'slug' => 'catalog-listing-offer',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.offers.index'));

        $response->assertStatus(200);
        $response->assertSee('Catalog Listing Offer');
        $response->assertSee('Promotional Offers');
    }

    public function test_admin_can_create_offer_targeting_courses_and_bundles(): void
    {
        $payload = [
            'name' => 'New Year Mega Bash',
            'badge_text' => 'NEW YEAR 50%',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'priority' => 5,
            'usage_limit' => 100,
            'allow_coupons' => 1,
            'is_active' => 1,
            'courses' => [$this->course1->id, $this->course2->id],
            'bundles' => [$this->bundle1->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), $payload);

        $response->assertRedirect(route('admin.offers.index'));
        $response->assertSessionHas('status');

        $offer = Offer::where('name', 'New Year Mega Bash')->first();
        $this->assertNotNull($offer);
        $this->assertEquals(50.00, (float) $offer->discount_value);
        $this->assertEquals(5, $offer->priority);
        $this->assertTrue($offer->allow_coupons);
        $this->assertEquals(2, $offer->courses()->count());
        $this->assertEquals(1, $offer->bundles()->count());
    }

    public function test_admin_cannot_create_offer_without_products(): void
    {
        $payload = [
            'name' => 'Orphan Offer',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'courses' => [],
            'bundles' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), $payload);

        $response->assertSessionHasErrors(['products']);
        $this->assertDatabaseMissing('offers', ['name' => 'Orphan Offer']);
    }

    public function test_admin_cannot_set_end_date_before_start_date(): void
    {
        $payload = [
            'name' => 'Invalid Date Offer',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'starts_at' => Carbon::now()->addDays(5)->toDateTimeString(),
            'ends_at' => Carbon::now()->addDays(2)->toDateTimeString(), // before starts_at
            'courses' => [$this->course1->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), $payload);

        $response->assertSessionHasErrors(['ends_at']);
    }

    public function test_admin_can_update_offer(): void
    {
        $offer = Offer::create([
            'title' => 'Initial Title',
            'slug' => 'initial-title',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $updatePayload = [
            'name' => 'Updated Offer Title',
            'badge_text' => 'UPDATED 30%',
            'discount_type' => 'percentage',
            'discount_value' => 30.00,
            'priority' => 3,
            'is_active' => 1,
            'courses' => [$this->course2->id],
            'bundles' => [$this->bundle1->id],
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.offers.update', $offer), $updatePayload);

        $response->assertRedirect(route('admin.offers.index'));

        $offer->refresh();
        $this->assertEquals('Updated Offer Title', $offer->name);
        $this->assertEquals(30.00, (float) $offer->discount_value);
        $this->assertEquals(3, $offer->priority);
        $this->assertEquals(1, $offer->courses()->count());
        $this->assertEquals($this->course2->id, $offer->courses()->first()->id);
        $this->assertEquals(1, $offer->bundles()->count());
    }

    public function test_admin_can_toggle_offer_status(): void
    {
        $offer = Offer::create([
            'title' => 'Toggle Me',
            'slug' => 'toggle-me',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.offers.toggle', $offer));

        $response->assertRedirect();
        $offer->refresh();
        $this->assertFalse($offer->is_active);

        // Toggle back to active
        $this->actingAs($this->admin)
            ->patch(route('admin.offers.toggle', $offer));

        $offer->refresh();
        $this->assertTrue($offer->is_active);
    }

    public function test_admin_can_delete_offer(): void
    {
        $offer = Offer::create([
            'title' => 'Delete Me',
            'slug' => 'delete-me',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 10.00,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.offers.destroy', $offer));

        $response->assertRedirect(route('admin.offers.index'));
        $this->assertDatabaseMissing('offers', ['id' => $offer->id]);
        $this->assertDatabaseMissing('offer_products', ['offer_id' => $offer->id]);
    }

    // =========================================================================
    // SECTION 6: PUBLIC CATALOG & PRODUCT DISPLAY
    // =========================================================================

    public function test_public_course_page_displays_offer_pricing_and_badge(): void
    {
        $offer = Offer::create([
            'title' => 'Flash 50',
            'badge_text' => 'FLASH 50% OFF',
            'discount_type' => OfferDiscountType::PERCENTAGE,
            'discount_value' => 50.00,
            'is_active' => true,
        ]);
        $offer->courses()->attach($this->course1->id);

        $response = $this->get(route('courses.show', $this->course1->slug));

        $response->assertStatus(200);
        $response->assertSee('FLASH 50% OFF');
        $response->assertSee('₹1,000.00'); // final discounted price (50% off 2000)
        $response->assertSee('₹2,000.00'); // original base crossed out
    }

    public function test_public_bundle_page_displays_offer_pricing_and_badge(): void
    {
        $offer = Offer::create([
            'title' => 'Bundle Mega Deal',
            'badge_text' => 'BUNDLE SPECIAL',
            'discount_type' => OfferDiscountType::FIXED,
            'discount_value' => 500.00, // ₹500 off 2500 = 2000
            'is_active' => true,
        ]);
        $offer->bundles()->attach($this->bundle1->id);

        $response = $this->get(route('bundles.show', $this->bundle1->slug));

        $response->assertStatus(200);
        $response->assertSee('BUNDLE SPECIAL');
        $response->assertSee('₹2,000.00'); // final bundle price
        $response->assertSee('₹2,500.00'); // original bundle price
    }
}
