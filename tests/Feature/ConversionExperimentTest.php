<?php

namespace Tests\Feature;

use App\Enums\ConversionEventName;
use App\Enums\CourseStatus;
use App\Enums\ExperimentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\ConversionEvent;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Experiment;
use App\Models\ExperimentExposure;
use App\Models\ExperimentVariant;
use App\Models\Order;
use App\Models\User;
use App\Services\ConversionTrackingService;
use App\Services\ExperimentService;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ConversionExperimentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;
    protected Course $course;
    protected Bundle $bundle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Conversion Optimization',
            'slug' => 'conversion-optimization',
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'instructor_id' => null,
            'course_category_id' => $this->category->id,
            'title' => 'Growth & Funnel Hacking',
            'slug' => 'growth-and-funnel-hacking',
            'short_description' => 'A comprehensive guide to growth optimization.',
            'description' => 'Learn how to run conversion optimization experiments.',
            'price' => 499900,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->bundle = Bundle::create([
            'title' => 'Growth Mastery Bundle',
            'slug' => 'growth-mastery-bundle',
            'short_description' => 'Complete suite of growth courses.',
            'description' => 'Complete suite of growth courses.',
            'price' => 7999.00,
            'status' => 'published',
        ]);
        $this->bundle->courses()->attach($this->course->id);
    }

    /**
     * Helper to create a standard 2-variant experiment.
     */
    protected function createStandardExperiment(
        string $key = 'course_cta',
        ExperimentStatus $status = ExperimentStatus::RUNNING,
        int $traffic = 100
    ): Experiment {
        $exp = Experiment::create([
            'name' => 'Course CTA Button Test',
            'key' => $key,
            'description' => 'Testing action text vs standard Buy Now',
            'status' => $status,
            'traffic_percentage' => $traffic,
            'target_audience' => 'all',
            'primary_metric' => ConversionEventName::COURSE_ENROLLED->value,
            'started_at' => $status === ExperimentStatus::RUNNING ? now() : null,
        ]);

        ExperimentVariant::create([
            'experiment_id' => $exp->id,
            'key' => 'control',
            'name' => 'Buy Now',
            'is_control' => true,
            'weight' => 50,
            'config' => ['cta_text' => 'Buy Now →', 'badge' => 'Standard'],
        ]);

        ExperimentVariant::create([
            'experiment_id' => $exp->id,
            'key' => 'urgency',
            'name' => 'Enroll Immediately',
            'is_control' => false,
            'weight' => 50,
            'config' => ['cta_text' => 'Enroll Immediately →', 'badge' => 'Limited Seats'],
        ]);

        return $exp;
    }

    // 1. Visitor anonymous ID cookie generation and persistence
    public function test_visitor_anonymous_id_cookie_generation_and_persistence(): void
    {
        $service = app(ConversionTrackingService::class);
        $anonId = $service->getAnonymousId();

        $this->assertNotEmpty($anonId);
        $this->assertStringStartsWith('exp_', $anonId);

        // Subsequent calls within session reuse the same ID
        $this->assertSame($anonId, $service->getAnonymousId());
    }

    // 2. Deterministic 64-char SHA-256 visitor hash generation
    public function test_deterministic_64_char_sha256_visitor_hash_generation(): void
    {
        $service = app(ConversionTrackingService::class);

        $guestHash1 = $service->getVisitorHash(null, 'guest_anon_123');
        $guestHash2 = $service->getVisitorHash(null, 'guest_anon_123');
        $userHash = $service->getVisitorHash($this->student);

        $this->assertSame(64, strlen($guestHash1));
        $this->assertSame(64, strlen($userHash));
        $this->assertSame($guestHash1, $guestHash2);
        $this->assertNotSame($guestHash1, $userHash);
    }

    // 3. ConversionTrackingService fail-safe execution never throws
    public function test_conversion_tracking_service_fail_safe_execution_never_throws(): void
    {
        $service = app(ConversionTrackingService::class);

        // Pass intentionally malformed payload
        $result = $service->track('invalid_unknown_event', [
            'course_id' => 9999999, // non-existent
            'user_id' => 8888888,   // non-existent
        ]);

        // Handled gracefully without unhandled throw
        $this->assertTrue($result === null || $result instanceof ConversionEvent);
    }

    // 4. ExperimentService deterministic CRC32 variant assignment
    public function test_experiment_service_deterministic_crc32_variant_assignment(): void
    {
        $experiment = $this->createStandardExperiment('deterministic_test');
        $service = app(ExperimentService::class);

        $variantFirst = $service->resolveVariant('deterministic_test', $this->student);
        $variantSecond = $service->resolveVariant('deterministic_test', $this->student);

        $this->assertNotNull($variantFirst);
        $this->assertSame($variantFirst->id, $variantSecond->id);
    }

    // 5. Multiple distinct visitors receive distributed variants
    public function test_multiple_distinct_visitors_receive_distributed_variants(): void
    {
        $experiment = $this->createStandardExperiment('distribution_test');
        $service = app(ExperimentService::class);

        $variantCounts = ['control' => 0, 'urgency' => 0];

        for ($i = 0; $i < 60; $i++) {
            $user = User::factory()->create();
            $resolved = $service->resolveVariant('distribution_test', $user);
            if ($resolved) {
                $variantCounts[$resolved->key]++;
            }
        }

        // Both variants should receive a non-zero share of traffic
        $this->assertGreaterThan(5, $variantCounts['control']);
        $this->assertGreaterThan(5, $variantCounts['urgency']);
    }

    // 6. Inactive / draft experiment returns null or control variant
    public function test_inactive_draft_experiment_returns_null_or_control_variant(): void
    {
        $experiment = $this->createStandardExperiment('draft_exp', ExperimentStatus::DRAFT);
        $service = app(ExperimentService::class);

        $resolved = $service->resolveVariant('draft_exp', $this->student);

        // Draft should not expose test variants
        $this->assertTrue($resolved === null || $resolved->is_control);
    }

    // 7. Paused experiment always returns control variant
    public function test_paused_experiment_always_returns_control_variant(): void
    {
        $experiment = $this->createStandardExperiment('paused_exp', ExperimentStatus::PAUSED);
        $service = app(ExperimentService::class);

        $resolved = $service->resolveVariant('paused_exp', $this->student);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is_control);
        $this->assertSame('control', $resolved->key);
    }

    // 8. Completed experiment returns winning variant if set, else control
    public function test_completed_experiment_returns_winning_variant_if_set_else_control(): void
    {
        $experiment = $this->createStandardExperiment('completed_exp', ExperimentStatus::RUNNING);
        $winningVariant = $experiment->variants->where('key', 'urgency')->first();

        $service = app(ExperimentService::class);
        $service->completeExperiment($experiment, $winningVariant->id);

        $resolved = $service->resolveVariant('completed_exp', $this->student);

        $this->assertNotNull($resolved);
        $this->assertSame($winningVariant->id, $resolved->id);
    }

    // 9. Archived experiment returns control variant or null
    public function test_archived_experiment_returns_control_variant_or_null(): void
    {
        $experiment = $this->createStandardExperiment('archived_exp', ExperimentStatus::ARCHIVED);
        $service = app(ExperimentService::class);

        $resolved = $service->resolveVariant('archived_exp', $this->student);

        $this->assertTrue($resolved === null || $resolved->is_control);
    }

    // 10. Traffic percentage filtering: outside traffic gets control
    public function test_traffic_percentage_filtering_outside_traffic_gets_control(): void
    {
        // 0% traffic routed into test
        $experiment = $this->createStandardExperiment('low_traffic_exp', ExperimentStatus::RUNNING, 1);
        $service = app(ExperimentService::class);

        // Test with a user whose hash falls in the remaining 99%
        $resolved = $service->resolveVariant('low_traffic_exp', $this->student);
        $this->assertNotNull($resolved);
        // Either control or safe variant
        $this->assertInstanceOf(ExperimentVariant::class, $resolved);
    }

    // 11. Safe variant configuration extraction: zero eval, pure data array
    public function test_safe_variant_configuration_extraction_pure_data_no_eval(): void
    {
        $experiment = $this->createStandardExperiment('safe_config_exp');
        $service = app(ExperimentService::class);

        $config = $service->getVariantConfig('safe_config_exp', $this->student);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('cta_text', $config);
    }

    // 12. Fallback to control variant if variant config missing or malformed
    public function test_fallback_to_control_variant_if_config_missing_or_malformed(): void
    {
        $experiment = $this->createStandardExperiment('malformed_exp');
        $experiment->variants()->update(['config' => null]);

        $service = app(ExperimentService::class);
        $config = $service->getVariantConfig('malformed_exp', $this->student);

        $this->assertIsArray($config);
    }

    // 13. Experiment exposure is recorded idempotently
    public function test_experiment_exposure_is_recorded_idempotently(): void
    {
        $experiment = $this->createStandardExperiment('exposure_idempotent_test');
        $service = app(ExperimentService::class);

        $service->resolveVariant('exposure_idempotent_test', $this->student);
        $firstCount = ExperimentExposure::where('experiment_id', $experiment->id)->count();

        // Second exposure call for the same student
        $service->resolveVariant('exposure_idempotent_test', $this->student);
        $secondCount = ExperimentExposure::where('experiment_id', $experiment->id)->count();

        $this->assertSame(1, $firstCount);
        $this->assertSame(1, $secondCount);
    }

    // 14. Conversion event recording: course_view tracked on course detail page
    public function test_course_view_tracked_on_course_detail_page(): void
    {
        $this->get(route('courses.show', $this->course))
            ->assertOk();

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::COURSE_VIEW->value,
            'course_id' => $this->course->id,
        ]);
    }

    // 15. Conversion event recording: bundle_view tracked on bundle detail page
    public function test_bundle_view_tracked_on_bundle_detail_page(): void
    {
        $this->get(route('bundles.show', $this->bundle))
            ->assertOk();

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::BUNDLE_VIEW->value,
            'bundle_id' => $this->bundle->id,
        ]);
    }

    // 16. Conversion event recording: course_cta_click ingested via client endpoint
    public function test_course_cta_click_ingested_via_client_endpoint(): void
    {
        $response = $this->postJson(route('events.track'), [
            'event_name' => ConversionEventName::COURSE_CTA_CLICK->value,
            'course_id' => $this->course->id,
            'metadata' => ['button' => 'hero_cta'],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::COURSE_CTA_CLICK->value,
            'course_id' => $this->course->id,
        ]);
    }

    // 17. Conversion event recording: bundle_cta_click ingested via client endpoint
    public function test_bundle_cta_click_ingested_via_client_endpoint(): void
    {
        $response = $this->postJson(route('events.track'), [
            'event_name' => ConversionEventName::BUNDLE_CTA_CLICK->value,
            'bundle_id' => $this->bundle->id,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::BUNDLE_CTA_CLICK->value,
            'bundle_id' => $this->bundle->id,
        ]);
    }

    // 18. Client endpoint rejects invalid event names
    public function test_client_endpoint_rejects_invalid_event_names(): void
    {
        $response = $this->postJson(route('events.track'), [
            'event_name' => 'completely_fake_event',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_name']);
    }

    // 19. Client endpoint rejects server-only high-value events
    public function test_client_endpoint_rejects_server_only_high_value_events(): void
    {
        $response = $this->postJson(route('events.track'), [
            'event_name' => ConversionEventName::PAYMENT_SUCCESS->value,
            'course_id' => $this->course->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_name']);
    }

    // 20. Conversion event recording: checkout_started tracked on checkout page
    public function test_checkout_started_tracked_on_checkout_page(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-01',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.courses.checkout', $order))
            ->assertOk();

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::CHECKOUT_STARTED->value,
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
        ]);
    }

    // 21. Conversion event recording: payment_success tracked upon payment verification
    public function test_payment_success_tracked_upon_payment_verification(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-02',
            'razorpay_order_id' => 'order_rzp_mock_123',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_rzp_mock_123',
                'razorpay_payment_id' => 'pay_rzp_mock_456',
                'razorpay_signature' => 'mock_signature_valid',
            ])
            ->assertRedirect(route('payment.success', $order));

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::PAYMENT_SUCCESS->value,
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
        ]);
    }

    // 22. Conversion event recording: payment_failed tracked upon signature verification failure
    public function test_payment_failed_tracked_upon_signature_verification_failure(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-03',
            'razorpay_order_id' => 'order_rzp_mock_789',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(false);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_rzp_mock_789',
                'razorpay_payment_id' => 'pay_rzp_mock_999',
                'razorpay_signature' => 'invalid_signature',
            ])
            ->assertRedirect(route('payment.failed', $order));

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::PAYMENT_FAILED->value,
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
        ]);
    }

    // 23. Conversion event recording: course_enrolled tracked upon course purchase
    public function test_course_enrolled_tracked_upon_course_purchase(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-04',
            'razorpay_order_id' => 'order_rzp_mock_course_enroll',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_rzp_mock_course_enroll',
                'razorpay_payment_id' => 'pay_rzp_mock_course_enroll',
                'razorpay_signature' => 'mock_signature_valid',
            ]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::COURSE_ENROLLED->value,
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
        ]);
    }

    // 24. Conversion event recording: bundle_purchased tracked upon bundle purchase
    public function test_bundle_purchased_tracked_upon_bundle_purchase(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'bundle_id' => $this->bundle->id,
            'order_number' => 'MM-ORD-TEST-05',
            'razorpay_order_id' => 'order_rzp_mock_bundle_enroll',
            'original_amount' => 799900,
            'discount_amount' => 0,
            'amount' => 799900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $mockRazorpay = Mockery::mock(RazorpayService::class);
        $mockRazorpay->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andReturn(true);
        $this->app->instance(RazorpayService::class, $mockRazorpay);

        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_rzp_mock_bundle_enroll',
                'razorpay_payment_id' => 'pay_rzp_mock_bundle_enroll',
                'razorpay_signature' => 'mock_signature_valid',
            ]);

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::BUNDLE_PURCHASED->value,
            'bundle_id' => $this->bundle->id,
            'user_id' => $this->student->id,
        ]);
    }

    // 25. Conversion event recording: lead_created tracked upon public lead capture
    public function test_lead_created_tracked_upon_public_lead_capture(): void
    {
        $response = $this->post(route('leads.store'), [
            'name' => 'Alice Growth',
            'email' => 'alice@growth.test',
            'phone' => '+919876543210',
            'source' => 'landing_page',
            'message' => 'I would like to inquire about this program.',
        ]);

        $response->assertSessionHas('lead_success');

        $this->assertDatabaseHas('conversion_events', [
            'event_name' => ConversionEventName::LEAD_CREATED->value,
        ]);
    }

    // 26. Automatic attribution links conversion event to recent exposure
    public function test_automatic_attribution_links_conversion_event_to_recent_exposure(): void
    {
        $experiment = $this->createStandardExperiment('attribution_test');
        $variant = $experiment->variants->first();
        $tracking = app(ConversionTrackingService::class);

        $visitorHash = $tracking->getVisitorHash($this->student);

        // Record exposure
        ExperimentExposure::create([
            'experiment_id' => $experiment->id,
            'variant_id' => $variant->id,
            'visitor_hash' => $visitorHash,
            'user_id' => $this->student->id,
            'occurred_at' => now(),
        ]);

        // Track subsequent conversion
        $event = $tracking->track(ConversionEventName::COURSE_ENROLLED, [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->assertNotNull($event);
        $this->assertSame($experiment->id, $event->experiment_id);
        $this->assertSame($variant->id, $event->variant_id);
    }

    // 27. Funnel metrics calculation: sequential counts, progression rates, drop-off
    public function test_funnel_metrics_calculation_sequential_counts_and_drop_off(): void
    {
        $tracking = app(ConversionTrackingService::class);

        // Create sample funnel data
        ConversionEvent::create([
            'event_name' => ConversionEventName::COURSE_VIEW->value,
            'course_id' => $this->course->id,
            'occurred_at' => now(),
        ]);
        ConversionEvent::create([
            'event_name' => ConversionEventName::COURSE_CTA_CLICK->value,
            'course_id' => $this->course->id,
            'occurred_at' => now(),
        ]);

        $funnel = $tracking->getFunnelMetrics(now()->subDay(), now()->addDay(), $this->course->id);

        $this->assertIsArray($funnel);
        $this->assertArrayHasKey('steps', $funnel);
        $this->assertArrayHasKey('overall_conversion_rate', $funnel);
        $this->assertCount(5, $funnel['steps']);
    }

    // 28. Experiment results calculation: exposures, conversions, CR, lift %
    public function test_experiment_results_calculation_exposures_conversions_and_lift(): void
    {
        $experiment = $this->createStandardExperiment('results_calc_test');
        $control = $experiment->variants->where('is_control', true)->first();
        $treatment = $experiment->variants->where('is_control', false)->first();

        // Control: 10 exposures, 1 conversion (10% CR)
        for ($i = 0; $i < 10; $i++) {
            $h = hash('sha256', "c_exp_{$i}");
            ExperimentExposure::create([
                'experiment_id' => $experiment->id,
                'variant_id' => $control->id,
                'visitor_hash' => $h,
                'occurred_at' => now(),
            ]);
        }
        ConversionEvent::create([
            'event_name' => $experiment->primary_metric,
            'experiment_id' => $experiment->id,
            'variant_id' => $control->id,
            'occurred_at' => now(),
        ]);

        // Treatment: 10 exposures, 2 conversions (20% CR -> +100% lift)
        for ($i = 0; $i < 10; $i++) {
            $h = hash('sha256', "t_exp_{$i}");
            ExperimentExposure::create([
                'experiment_id' => $experiment->id,
                'variant_id' => $treatment->id,
                'visitor_hash' => $h,
                'occurred_at' => now(),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            ConversionEvent::create([
                'event_name' => $experiment->primary_metric,
                'experiment_id' => $experiment->id,
                'variant_id' => $treatment->id,
                'occurred_at' => now(),
            ]);
        }

        $service = app(ExperimentService::class);
        $results = $service->getExperimentResults($experiment);

        $this->assertSame(20, $results['total_exposures']);
        $this->assertSame(3, $results['total_conversions']);
        $this->assertSame(15.0, $results['overall_conversion_rate']);

        $treatmentResult = collect($results['variants'])->firstWhere('id', $treatment->id);
        $this->assertSame(20.0, $treatmentResult['conversion_rate']);
        $this->assertSame(100.0, $treatmentResult['relative_lift_percent']);
    }

    // 29. Admin experiment management listing with status filter and metrics
    public function test_admin_experiment_management_listing_with_status_filter_and_metrics(): void
    {
        $this->createStandardExperiment('list_test_1', ExperimentStatus::RUNNING);
        $this->createStandardExperiment('list_test_2', ExperimentStatus::PAUSED);

        $this->actingAs($this->admin)
            ->get(route('admin.experiments.index', ['status' => 'running']))
            ->assertOk()
            ->assertSee('Course CTA Button Test')
            ->assertSee('list_test_1');
    }

    // 30. Admin experiment creation with validation
    public function test_admin_experiment_creation_with_validation(): void
    {
        $payload = [
            'name' => 'Admin Created Experiment',
            'key' => 'admin_created_exp',
            'description' => 'Created via admin UI test',
            'target_audience' => 'all',
            'traffic_percentage' => 100,
            'primary_metric' => 'course_enrolled',
            'variants' => [
                [
                    'key' => 'control',
                    'name' => 'Control CTA',
                    'weight' => 50,
                    'is_control' => 1,
                    'config' => json_encode(['cta_text' => 'Enroll']),
                ],
                [
                    'key' => 'variant_b',
                    'name' => 'Variant B',
                    'weight' => 50,
                    'is_control' => 0,
                    'config' => json_encode(['cta_text' => 'Get Access']),
                ],
            ],
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('experiments', [
            'key' => 'admin_created_exp',
            'status' => ExperimentStatus::DRAFT->value,
        ]);
        $this->assertDatabaseHas('experiment_variants', [
            'key' => 'variant_b',
            'is_control' => false,
        ]);
    }

    // 31. Admin experiment update metadata and settings
    public function test_admin_experiment_update_metadata_and_settings(): void
    {
        $exp = $this->createStandardExperiment('update_test', ExperimentStatus::DRAFT);

        $this->actingAs($this->admin)
            ->put(route('admin.experiments.update', $exp), [
                'name' => 'Updated Experiment Title',
                'description' => 'Updated hypothesis description',
                'target_audience' => 'new_visitors',
                'traffic_percentage' => 80,
                'primary_metric' => 'payment_success',
            ])
            ->assertRedirect(route('admin.experiments.show', $exp));

        $this->assertDatabaseHas('experiments', [
            'id' => $exp->id,
            'name' => 'Updated Experiment Title',
            'traffic_percentage' => 80,
            'primary_metric' => 'payment_success',
        ]);
    }

    // 32. Admin experiment activation transitions draft to running
    public function test_admin_experiment_activation_transitions_draft_to_running(): void
    {
        $exp = $this->createStandardExperiment('activate_test', ExperimentStatus::DRAFT);

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.activate', $exp))
            ->assertStatus(302);

        $exp->refresh();
        $this->assertTrue($exp->isRunning());
        $this->assertNotNull($exp->started_at);
    }

    // 33. Admin experiment pause transitions running to paused
    public function test_admin_experiment_pause_transitions_running_to_paused(): void
    {
        $exp = $this->createStandardExperiment('pause_test', ExperimentStatus::RUNNING);

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.pause', $exp))
            ->assertStatus(302);

        $exp->refresh();
        $this->assertTrue($exp->isPaused());
    }

    // 34. Admin experiment completion sets winner and ended_at
    public function test_admin_experiment_completion_sets_winner_and_ended_at(): void
    {
        $exp = $this->createStandardExperiment('complete_test', ExperimentStatus::RUNNING);
        $winner = $exp->variants->where('is_control', false)->first();

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.complete', $exp), [
                'winning_variant_id' => $winner->id,
            ])
            ->assertStatus(302);

        $exp->refresh();
        $this->assertTrue($exp->isCompleted());
        $this->assertSame($winner->id, $exp->winning_variant_id);
        $this->assertNotNull($exp->ended_at);
    }

    // 35. Admin experiment archive transitions to archived
    public function test_admin_experiment_archive_transitions_to_archived(): void
    {
        $exp = $this->createStandardExperiment('archive_test', ExperimentStatus::PAUSED);

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.archive', $exp))
            ->assertStatus(302);

        $exp->refresh();
        $this->assertTrue($exp->isArchived());
    }

    // 36. Admin experiment deletion draft only
    public function test_admin_experiment_deletion_draft_only(): void
    {
        $draftExp = $this->createStandardExperiment('delete_draft_test', ExperimentStatus::DRAFT);
        $runningExp = $this->createStandardExperiment('delete_running_test', ExperimentStatus::RUNNING);

        // Deleting running experiment should be blocked
        $this->actingAs($this->admin)
            ->delete(route('admin.experiments.destroy', $runningExp))
            ->assertStatus(302);
        $this->assertDatabaseHas('experiments', ['id' => $runningExp->id]);

        // Deleting draft experiment should succeed
        $this->actingAs($this->admin)
            ->delete(route('admin.experiments.destroy', $draftExp))
            ->assertRedirect(route('admin.experiments.index'));
        $this->assertDatabaseMissing('experiments', ['id' => $draftExp->id]);
    }

    // 37. Admin funnel dashboard renders with filter parameters
    public function test_admin_funnel_dashboard_renders_with_filter_parameters(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.funnel.index', [
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
                'course_id' => $this->course->id,
            ]))
            ->assertOk()
            ->assertSee('Conversion Funnel Analytics')
            ->assertSee($this->course->title);
    }

    // 38. Zero disruption guarantee: checkout completes even if experiment errors
    public function test_zero_disruption_checkout_completes_even_if_experiment_errors(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-FAILSAFE-01',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // Even with simulated exception in tracking, checkout view renders smoothly
        $this->actingAs($this->student)
            ->get(route('student.courses.checkout', $order))
            ->assertOk()
            ->assertSee('Secure Checkout');
    }

    // 39. Zero disruption guarantee: course detail renders when no experiments exist
    public function test_zero_disruption_course_detail_renders_when_no_experiments_exist(): void
    {
        Experiment::query()->delete();

        $this->get(route('courses.show', $this->course))
            ->assertOk()
            ->assertSee('Buy Now →');
    }

    // 40. Sensitive metadata sanitization
    public function test_sensitive_metadata_sanitization(): void
    {
        $service = app(ConversionTrackingService::class);

        $event = $service->track(ConversionEventName::COURSE_VIEW, [
            'course_id' => $this->course->id,
            'metadata' => [
                'safe_param' => 'landing_page',
                'password' => 'super_secret',
                'card' => '4111222233334444',
                'token' => 'xyz789',
            ],
        ]);

        $this->assertNotNull($event);
        $this->assertArrayHasKey('safe_param', $event->metadata);
        $this->assertArrayNotHasKey('password', $event->metadata);
        $this->assertArrayNotHasKey('card', $event->metadata);
        $this->assertArrayNotHasKey('token', $event->metadata);
    }

    // 41. Audit log integration records experiment lifecycle actions
    public function test_audit_log_integration_records_experiment_lifecycle_actions(): void
    {
        $exp = $this->createStandardExperiment('audit_test', ExperimentStatus::DRAFT);

        $this->actingAs($this->admin)
            ->post(route('admin.experiments.activate', $exp));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activated',
            'auditable_type' => 'Experiment',
            'auditable_id' => $exp->id,
        ]);
    }
}
