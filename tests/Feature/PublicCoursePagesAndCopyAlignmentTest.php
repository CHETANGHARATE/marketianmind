<?php

namespace Tests\Feature;

use App\Enums\BundleStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCoursePagesAndCopyAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $standardCourse;
    protected Course $customCourse;
    protected Course $freeCourse;
    protected CourseModule $module;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->standardCourse = Course::create([
            'title' => 'Digital Marketing Masterclass',
            'slug' => 'digital-marketing-masterclass',
            'short_description' => 'Comprehensive masterclass on digital customer acquisition.',
            'description' => 'Full business digital acquisition system.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->customCourse = Course::create([
            'title' => 'High-Velocity Ad Scaling',
            'slug' => 'high-velocity-ad-scaling',
            'short_description' => 'Scale paid performance ads in 6 months.',
            'description' => 'Intensive 180-day growth program.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 180,
        ]);

        $this->freeCourse = Course::create([
            'title' => 'Marketing Fundamentals For Beginners',
            'slug' => 'marketing-fundamentals-for-beginners',
            'short_description' => 'Essential marketing concepts for new entrepreneurs.',
            'description' => 'Free introduction to marketing.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->standardCourse->id,
            'title' => 'Module 1: Acquisition Foundation',
            'sort_order' => 1,
        ]);

        $this->lesson = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 1: Strategy Core',
            'slug' => 'lesson-1-strategy-core',
            'sort_order' => 1,
            'status' => \App\Enums\LessonStatus::PUBLISHED,
        ]);
    }

    /**
     * Scenario A: Public course detail page displays configured access duration (365 days / 1 Year).
     */
    public function test_public_course_detail_page_displays_configured_access_duration_365_days(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('1 Year Access');
        $response->assertSee('365-day course access');
        $response->assertSee('Course Access');
        $response->assertSee('1 Year');
    }

    /**
     * Scenario B: Public course detail page displays custom access validity when course has non-default validity (e.g. 180 days).
     */
    public function test_public_course_detail_page_displays_custom_access_validity_when_course_has_non_default_validity_180_days(): void
    {
        $response = $this->get(route('courses.show', $this->customCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('180 Days Access');
        $response->assertSee('180-day course access');
        $response->assertSee('180 Days');
    }

    /**
     * Scenario C: Public course detail page displays clear price presentation.
     */
    public function test_public_course_detail_page_displays_clear_price_presentation(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('Tuition &amp; Access', false);
        $response->assertSee('₹4,999.00');
    }

    /**
     * Scenario D: Public course detail page displays one-time payment wording.
     */
    public function test_public_course_detail_page_displays_one_time_payment_wording(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('One-time payment');
        $response->assertSee('no recurring subscriptions or automatic charges');
    }

    /**
     * Scenario E: Public course detail page does not contain 'Lifetime access' for finite-access courses.
     */
    public function test_public_course_detail_page_does_not_contain_lifetime_access_for_finite_access_courses(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertDontSee('Lifetime access');
        $response->assertDontSee('lifetime access to the course');
        $response->assertDontSee('Lifetime access • All future updates included');
    }

    /**
     * Scenario F: Public course detail page FAQ reflects the configured validity duration.
     */
    public function test_public_course_detail_page_faq_reflects_the_configured_validity_duration(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('How long do I have access to this course after enrolling?');
        $response->assertSee('1 Year Access of full curriculum access (365 days from purchase)');

        // Test custom 180 days FAQ
        $customResponse = $this->get(route('courses.show', $this->customCourse->slug));
        $customResponse->assertStatus(200);
        $customResponse->assertSee('180 Days Access of full curriculum access (180 days from purchase)');
    }

    /**
     * Scenario G: Public course detail page renewal explanation is visible and accurate.
     */
    public function test_public_course_detail_page_renewal_explanation_is_visible_and_accurate(): void
    {
        $response = $this->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('Manual renewal');
        $response->assertSee('renew manually at any time to extend or reactivate access');
        $response->assertSee('Progress permanently preserved');
    }

    /**
     * Scenario H: Public course cards on course catalog display access duration.
     */
    public function test_public_course_cards_on_course_catalog_display_access_duration(): void
    {
        $response = $this->get(route('courses'));

        $response->assertStatus(200);
        $response->assertSee('1 Year Access');
        $response->assertSee('180 Days Access');
    }

    /**
     * Scenario I: Public course cards on homepage display access duration.
     */
    public function test_public_course_cards_on_homepage_display_access_duration(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Full-Year Access');
        $response->assertDontSee('Lifetime Access');
    }

    /**
     * Scenario J: Checkout page displays correct access validity duration.
     */
    public function test_checkout_page_displays_correct_access_validity_duration(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-180',
            'user_id' => $this->student->id,
            'course_id' => $this->customCourse->id,
            'amount' => 2999.00,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.checkout', $order));

        $response->assertStatus(200);
        $response->assertSee('180 Days Access');
        $response->assertSee('Access:');
    }

    /**
     * Scenario K: Checkout page displays one-time payment wording.
     */
    public function test_checkout_page_displays_one_time_payment_wording(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-365',
            'user_id' => $this->student->id,
            'course_id' => $this->standardCourse->id,
            'amount' => 4999.00,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.checkout', $order));

        $response->assertStatus(200);
        $response->assertSee('One-time payment');
        $response->assertSee('One-time payment with no recurring charges');
    }

    /**
     * Scenario L: Checkout order summary shows accurate amount and validity terms.
     */
    public function test_checkout_order_summary_shows_accurate_amount_and_validity_terms(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-SUMM',
            'user_id' => $this->student->id,
            'course_id' => $this->customCourse->id,
            'amount' => 2999.00,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.checkout', $order));

        $response->assertStatus(200);
        $response->assertSee('Total Due Today');
        $response->assertSee('₹2,999.00');
        $response->assertSee('One-time payment');
        $response->assertSee('180 Days Access');
    }

    /**
     * Scenario M: Course enrollment confirmation email displays access validity, not lifetime access.
     */
    public function test_course_enrollment_confirmation_email_displays_access_validity_not_lifetime_access(): void
    {
        $html = view('emails.course_enrollment', [
            'user' => $this->student,
            'course' => $this->standardCourse,
        ])->render();

        $this->assertStringContainsString('1 Year Access (Self-Paced)', $html);
        $this->assertStringNotContainsString('Lifetime Self-Paced', $html);
    }

    /**
     * Scenario N: Course order confirmation email displays access validity, not lifetime access.
     */
    public function test_course_order_confirmation_email_displays_access_validity_not_lifetime_access(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-CONF',
            'user_id' => $this->student->id,
            'course_id' => $this->standardCourse->id,
            'amount' => 4999.00,
            'status' => OrderStatus::PAID,
        ]);

        $html = view('emails.order_confirmation', [
            'user' => $this->student,
            'order' => $order,
        ])->render();

        $this->assertStringContainsString('Your order has been confirmed and your course access is now active', $html);
        $this->assertStringContainsString('Access Duration', $html);
        $this->assertStringContainsString('1 Year Access', $html);
        $this->assertStringNotContainsString('lifetime access to the course', $html);
    }

    /**
     * Scenario O: Free course displays appropriate wording (Free access, no misleading expiry).
     */
    public function test_free_course_displays_appropriate_wording(): void
    {
        $response = $this->get(route('courses.show', $this->freeCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('Free');
        $response->assertSee('100% free access for business owners &amp; founders.', false);
        $response->assertDontSee('365-day course access');
        $response->assertDontSee('One-time payment');
    }

    /**
     * Scenario P: Logged-in enrolled active student sees correct access status on public page.
     */
    public function test_logged_in_enrolled_active_student_sees_correct_access_status_on_public_page(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->standardCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(10),
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->addDays(355),
        ]);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('Continue Learning');
        $response->assertDontSee('Course Access Expired');
    }

    /**
     * Scenario Q: Logged-in expired student sees renewal CTA on public page.
     */
    public function test_logged_in_expired_student_sees_renewal_cta_on_public_page(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->standardCourse->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->standardCourse->slug));

        $response->assertStatus(200);
        $response->assertSee('Course Access Expired');
        $response->assertSee('Renew Course Access');
        $response->assertSee('Your learning progress and certificate records remain permanently preserved');
    }

    /**
     * Scenario R: Logged-in student with legacy lifetime access still sees lifetime wording where appropriate.
     */
    public function test_logged_in_student_with_legacy_lifetime_access_still_sees_lifetime_wording_where_appropriate(): void
    {
        $legacyCourse = Course::create([
            'title' => 'Legacy Marketing Foundation',
            'slug' => 'legacy-marketing-foundation',
            'short_description' => 'Classic principles.',
            'description' => 'Timeless marketing.',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Legacy enrollment with NULL starts_at and NULL expires_at
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $legacyCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(500),
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $this->assertTrue($enrollment->isLegacyLifetimeAccess());
        $this->assertEquals('Lifetime Access', $enrollment->getRemainingDaysText());

        $response = $this->actingAs($this->student)->get(route('student.courses'));
        $response->assertStatus(200);
        $response->assertSee('Lifetime Access');
    }

    /**
     * Scenario S: Course access duration helper methods return correct formatted strings.
     */
    public function test_course_access_duration_helper_methods_return_correct_formatted_strings(): void
    {
        // 365 days
        $course365 = new Course(['access_validity_days' => 365, 'is_free' => false]);
        $this->assertEquals(365, $course365->getAccessValidityDays());
        $this->assertEquals('1 Year Access', $course365->accessDurationLabel());
        $this->assertEquals('1 Year', $course365->accessDurationShort());
        $this->assertEquals('Pay once for 1 Year of full access. Manual renewal available with permanent progress preservation.', $course365->accessValidityDescription());

        // 730 days (2 years)
        $course730 = new Course(['access_validity_days' => 730, 'is_free' => false]);
        $this->assertEquals(730, $course730->getAccessValidityDays());
        $this->assertEquals('2 Years Access', $course730->accessDurationLabel());
        $this->assertEquals('2 Years', $course730->accessDurationShort());

        // 180 days
        $course180 = new Course(['access_validity_days' => 180, 'is_free' => false]);
        $this->assertEquals(180, $course180->getAccessValidityDays());
        $this->assertEquals('180 Days Access', $course180->accessDurationLabel());
        $this->assertEquals('180 Days', $course180->accessDurationShort());
        $this->assertEquals('Pay once for 180 Days of full access. Manual renewal available with permanent progress preservation.', $course180->accessValidityDescription());

        // 90 days
        $course90 = new Course(['access_validity_days' => 90, 'is_free' => false]);
        $this->assertEquals('90 Days Access', $course90->accessDurationLabel());
        $this->assertEquals('90 Days', $course90->accessDurationShort());

        // 30 days
        $course30 = new Course(['access_validity_days' => 30, 'is_free' => false]);
        $this->assertEquals('30 Days Access', $course30->accessDurationLabel());
        $this->assertEquals('30 Days', $course30->accessDurationShort());

        // 1 day
        $course1 = new Course(['access_validity_days' => 1, 'is_free' => false]);
        $this->assertEquals('1 Day Access', $course1->accessDurationLabel());
        $this->assertEquals('1 Day', $course1->accessDurationShort());

        // Free course
        $freeCourse = new Course(['is_free' => true, 'access_validity_days' => 365]);
        $this->assertEquals('Free Access', $freeCourse->accessDurationLabel());
        $this->assertEquals('Free', $freeCourse->accessDurationShort());
        $this->assertEquals('100% free access for business owners & founders.', $freeCourse->accessValidityDescription());

        // Default null fallback
        $courseNull = new Course(['access_validity_days' => null, 'is_free' => false]);
        $this->assertEquals(365, $courseNull->getAccessValidityDays());
        $this->assertEquals('1 Year Access', $courseNull->accessDurationLabel());
    }

    /**
     * Scenario T: Bundle detail page does not make false lifetime access claims.
     */
    public function test_bundle_detail_page_does_not_make_false_lifetime_access_claims(): void
    {
        $bundle = Bundle::create([
            'title' => 'Complete Growth Accelerator Pack',
            'slug' => 'complete-growth-accelerator-pack',
            'price' => 5999.00,
            'status' => BundleStatus::PUBLISHED,
        ]);

        $bundle->courses()->attach([$this->standardCourse->id, $this->customCourse->id]);

        $response = $this->get(route('bundles.show', $bundle->slug));

        $response->assertStatus(200);
        $response->assertSee('immediate, full access');
        $response->assertSee('Course updates');
        $response->assertDontSee('lifetime access to all');
        $response->assertDontSee('Lifetime updates');
        $response->assertDontSee('Full lifetime access is active');
    }
}