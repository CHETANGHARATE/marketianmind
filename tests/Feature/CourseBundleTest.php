<?php

namespace Tests\Feature;

use App\Enums\BundleStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CourseBundleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;
    protected Course $course3;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Course Alpha: SEO Mastery',
            'slug' => 'course-alpha-seo-mastery',
            'short_description' => 'Learn SEO from zero to hero.',
            'price' => 1999.00,
            'discount_price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
            'is_free' => false,
        ]);

        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Course Beta: Social Ads Mastery',
            'slug' => 'course-beta-social-ads-mastery',
            'short_description' => 'Scale social media advertising.',
            'price' => 2999.00,
            'discount_price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
            'is_free' => false,
        ]);

        $this->course3 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Course Gamma: Funnel Strategy',
            'slug' => 'course-gamma-funnel-strategy',
            'short_description' => 'High-converting sales funnels.',
            'price' => 3999.00,
            'discount_price' => null,
            'status' => CourseStatus::PUBLISHED,
            'is_free' => false,
        ]);
    }

    public function test_admin_can_view_bundles_index_page(): void
    {
        Bundle::create([
            'title' => 'Marketing Mega Pack',
            'slug' => 'marketing-mega-pack',
            'price' => 3999.00,
            'status' => BundleStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bundles.index'));

        $response->assertStatus(200);
        $response->assertSee('Marketing Mega Pack');
        $response->assertSee('Course Bundles');
    }

    public function test_non_admin_cannot_access_admin_bundles(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.bundles.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_bundle_with_selected_courses(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bundles.store'), [
            'title' => 'Complete Traffic & Conversion Bundle',
            'slug' => 'complete-traffic-and-conversion-bundle',
            'short_description' => 'Master SEO and Paid Social in one package.',
            'description' => 'Comprehensive marketing curriculum.',
            'price' => 1999.00,
            'status' => 'published',
            'featured' => 1,
            'courses' => [$this->course1->id, $this->course2->id],
        ]);

        $response->assertRedirect(route('admin.bundles.index'));
        $this->assertDatabaseHas('bundles', [
            'title' => 'Complete Traffic & Conversion Bundle',
            'slug' => 'complete-traffic-and-conversion-bundle',
            'price' => 1999.00,
            'status' => 'published',
            'featured' => true,
        ]);

        $bundle = Bundle::where('slug', 'complete-traffic-and-conversion-bundle')->first();
        $this->assertCount(2, $bundle->courses);
        $this->assertTrue($bundle->courses->contains($this->course1));
        $this->assertTrue($bundle->courses->contains($this->course2));
    }

    public function test_admin_cannot_create_bundle_without_courses(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bundles.store'), [
            'title' => 'Empty Bundle',
            'price' => 999.00,
            'status' => 'published',
            'courses' => [],
        ]);

        $response->assertSessionHasErrors('courses');
        $this->assertDatabaseMissing('bundles', ['title' => 'Empty Bundle']);
    }

    public function test_admin_can_update_bundle_details_and_courses(): void
    {
        $bundle = Bundle::create([
            'title' => 'Initial Bundle Title',
            'slug' => 'initial-bundle-slug',
            'price' => 2000.00,
            'status' => BundleStatus::DRAFT,
        ]);
        $bundle->courses()->attach([$this->course1->id]);

        $response = $this->actingAs($this->admin)->put(route('admin.bundles.update', $bundle), [
            'title' => 'Updated Bundle Title',
            'slug' => 'updated-bundle-slug',
            'price' => 2999.00,
            'status' => 'published',
            'courses' => [$this->course1->id, $this->course2->id, $this->course3->id],
        ]);

        $response->assertRedirect(route('admin.bundles.index'));
        $this->assertDatabaseHas('bundles', [
            'id' => $bundle->id,
            'title' => 'Updated Bundle Title',
            'slug' => 'updated-bundle-slug',
            'price' => 2999.00,
            'status' => 'published',
        ]);

        $bundle->refresh();
        $this->assertCount(3, $bundle->courses);
    }

    public function test_admin_can_delete_bundle(): void
    {
        $bundle = Bundle::create([
            'title' => 'Bundle To Delete',
            'slug' => 'bundle-to-delete',
            'price' => 1500.00,
            'status' => BundleStatus::DRAFT,
        ]);
        $bundle->courses()->attach([$this->course1->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.bundles.destroy', $bundle));

        $response->assertRedirect(route('admin.bundles.index'));
        $this->assertDatabaseMissing('bundles', ['id' => $bundle->id]);
        $this->assertDatabaseMissing('bundle_courses', ['bundle_id' => $bundle->id]);
    }

    public function test_public_bundles_index_lists_published_bundles_and_hides_drafts(): void
    {
        Bundle::create([
            'title' => 'Live Public Bundle',
            'slug' => 'live-public-bundle',
            'price' => 2499.00,
            'status' => BundleStatus::PUBLISHED,
        ]);

        Bundle::create([
            'title' => 'Internal Draft Bundle',
            'slug' => 'internal-draft-bundle',
            'price' => 4999.00,
            'status' => BundleStatus::DRAFT,
        ]);

        $response = $this->get(route('bundles.index'));

        $response->assertStatus(200);
        $response->assertSee('Live Public Bundle');
        $response->assertDontSee('Internal Draft Bundle');
    }

    public function test_public_bundle_detail_page_displays_courses_and_savings(): void
    {
        $bundle = Bundle::create([
            'title' => 'Founder Growth Package',
            'slug' => 'founder-growth-package',
            'short_description' => 'Everything you need to launch and scale.',
            'price' => 2999.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        // Effective individual prices: course1 (999) + course2 (1499) + course3 (3999) = 6497.00
        // Savings = 6497 - 2999 = 3498.00
        $bundle->courses()->attach([$this->course1->id, $this->course2->id, $this->course3->id]);

        $response = $this->get(route('bundles.show', $bundle));

        $response->assertStatus(200);
        $response->assertSee('Founder Growth Package');
        $response->assertSee('Course Alpha: SEO Mastery');
        $response->assertSee('Course Beta: Social Ads Mastery');
        $response->assertSee('Course Gamma: Funnel Strategy');
        $response->assertSee('₹2,999.00'); // Bundle price
        $response->assertSee('₹6,497.00'); // Total individual value
        $response->assertSee('3,498.00'); // Savings amount
    }

    public function test_public_cannot_view_draft_bundle(): void
    {
        $bundle = Bundle::create([
            'title' => 'Secret Draft Bundle',
            'slug' => 'secret-draft-bundle',
            'price' => 1000.00,
            'status' => BundleStatus::DRAFT,
        ]);

        $response = $this->get(route('bundles.show', $bundle));
        $response->assertStatus(404);
    }

    public function test_student_can_initiate_bundle_checkout_with_tamper_proof_server_price(): void
    {
        $bundle = Bundle::create([
            'title' => 'Startup Digital Kit',
            'slug' => 'startup-digital-kit',
            'price' => 3499.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id, $this->course2->id]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('createOrder')
            ->once()
            ->with(349900, Mockery::type('string'), Mockery::type('array'))
            ->andReturn(['id' => 'order_rzp_bundle_123']);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->student)->post(route('student.bundles.purchase', $bundle));

        $order = Order::where('user_id', $this->student->id)->where('bundle_id', $bundle->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(349900, $order->amount); // 3499 * 100 paise
        $this->assertNull($order->course_id);
        $this->assertEquals('order_rzp_bundle_123', $order->razorpay_order_id);
        $this->assertTrue($order->isBundleOrder());

        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_bundle_payment_verification_fulfills_all_included_courses(): void
    {
        $bundle = Bundle::create([
            'title' => 'Full Masterclass Suite',
            'slug' => 'full-masterclass-suite',
            'price' => 4500.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id, $this->course2->id, $this->course3->id]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $bundle->id,
            'course_id' => null,
            'order_number' => 'MM-BND-TEST-001',
            'razorpay_order_id' => 'order_rzp_bundle_999',
            'original_amount' => 450000,
            'amount' => 450000,
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->with('order_rzp_bundle_999', 'pay_rzp_bundle_999', 'valid_sig')
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $response = $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_rzp_bundle_999',
            'razorpay_payment_id' => 'pay_rzp_bundle_999',
            'razorpay_signature' => 'valid_sig',
        ]);

        $response->assertRedirect(route('payment.success', $order));

        $order->refresh();
        $this->assertEquals(OrderStatus::PAID, $order->status);

        // Verify Payment record was saved with bundle_id
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'bundle_id' => $bundle->id,
            'razorpay_payment_id' => 'pay_rzp_bundle_999',
            'status' => PaymentStatus::CAPTURED->value,
        ]);

        // Verify user is now actively enrolled in ALL 3 courses
        $this->assertTrue($this->student->isEnrolledIn($this->course1));
        $this->assertTrue($this->student->isEnrolledIn($this->course2));
        $this->assertTrue($this->student->isEnrolledIn($this->course3));
        $this->assertEquals(3, $this->student->enrollments()->where('status', EnrollmentStatus::ACTIVE->value)->count());
    }

    public function test_bundle_fulfillment_supports_partial_ownership_and_preserves_existing(): void
    {
        // Student already owns Course 1
        $existingEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(10),
        ]);

        $bundle = Bundle::create([
            'title' => 'Partial Ownership Pack',
            'slug' => 'partial-ownership-pack',
            'price' => 3000.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id, $this->course2->id]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $bundle->id,
            'order_number' => 'MM-BND-PARTIAL-01',
            'razorpay_order_id' => 'order_partial_123',
            'amount' => 300000,
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_partial_123',
            'razorpay_payment_id' => 'pay_partial_123',
            'razorpay_signature' => 'sig_partial',
        ]);

        // Student now has access to both course1 and course2
        $this->assertTrue($this->student->isEnrolledIn($this->course1));
        $this->assertTrue($this->student->isEnrolledIn($this->course2));

        // Original enrollment in Course 1 was NOT duplicated or overwritten
        $course1Enrollments = Enrollment::where('user_id', $this->student->id)
            ->where('course_id', $this->course1->id)
            ->get();
        $this->assertCount(1, $course1Enrollments);
        $this->assertEquals($existingEnrollment->id, $course1Enrollments->first()->id);
    }

    public function test_student_cannot_purchase_bundle_if_they_already_own_all_courses(): void
    {
        // Enroll in all bundle courses
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $bundle = Bundle::create([
            'title' => 'Already Owned Bundle',
            'slug' => 'already-owned-bundle',
            'price' => 2000.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id, $this->course2->id]);

        $this->assertTrue($bundle->hasUserAccess($this->student));

        $response = $this->actingAs($this->student)->post(route('student.bundles.purchase', $bundle));

        $response->assertRedirect(route('bundles.show', $bundle));
        $response->assertSessionHas('status', 'You already have active access to all courses included in this bundle.');
    }

    public function test_bundle_order_appears_in_student_order_history_and_detail_view(): void
    {
        $bundle = Bundle::create([
            'title' => 'History Test Bundle',
            'slug' => 'history-test-bundle',
            'price' => 2500.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $bundle->id,
            'order_number' => 'MM-BND-HIST-01',
            'amount' => 250000,
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.orders.index'));
        $response->assertStatus(200);
        $response->assertSee('History Test Bundle');
        $response->assertSee('Course Bundle');

        $showResponse = $this->actingAs($this->student)->get(route('student.orders.show', $order));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('History Test Bundle');
        $showResponse->assertSee('Course Bundle');
    }

    public function test_single_course_purchase_regression(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'bundle_id' => null,
            'order_number' => 'MM-ORD-REGR-01',
            'razorpay_order_id' => 'order_regr_01',
            'amount' => 99900,
            'status' => OrderStatus::PENDING,
        ]);

        $this->assertTrue($order->isCourseOrder());
        $this->assertFalse($order->isBundleOrder());
        $this->assertEquals($this->course1->title, $order->productTitle());

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)->post(route('payments.razorpay.verify'), [
            'razorpay_order_id' => 'order_regr_01',
            'razorpay_payment_id' => 'pay_regr_01',
            'razorpay_signature' => 'sig_regr_01',
        ]);

        $this->assertTrue($this->student->isEnrolledIn($this->course1));
        $this->assertFalse($this->student->isEnrolledIn($this->course2));
    }

    public function test_webhook_order_paid_activates_bundle_order_and_all_course_enrollments(): void
    {
        config(['services.razorpay.webhook_secret' => 'mock_webhook_secret']);

        $bundle = Bundle::create([
            'title' => 'Webhook Bundle Pack',
            'slug' => 'webhook-bundle-pack',
            'price' => 2999.00,
            'status' => BundleStatus::PUBLISHED,
        ]);
        $bundle->courses()->attach([$this->course1->id, $this->course2->id]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $bundle->id,
            'course_id' => null,
            'order_number' => 'MM-BND-WH-01',
            'razorpay_order_id' => 'order_wh_bundle_999',
            'amount' => 299900,
            'status' => OrderStatus::PENDING,
        ]);

        $payloadArray = [
            'id' => 'evt_wh_bnd_1001',
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_wh_bundle_999',
                        'amount' => 299900,
                        'currency' => 'INR',
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_wh_bnd_1001',
                        'amount' => 299900,
                        'currency' => 'INR',
                        'method' => 'card',
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
                'HTTP_X-Razorpay-Event-Id' => 'evt_wh_bnd_1001',
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);

        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertTrue($this->student->isEnrolledIn($this->course1));
        $this->assertTrue($this->student->isEnrolledIn($this->course2));
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'bundle_id' => $bundle->id,
            'razorpay_payment_id' => 'pay_wh_bnd_1001',
        ]);
    }
}
