<?php

namespace Tests\Feature;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationStatus;
use App\Enums\AutomationTrigger;
use App\Enums\CourseStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\Course;
use App\Models\Lead;
use App\Models\MarketingTemplate;
use App\Models\Order;
use App\Models\User;
use App\Services\LeadService;
use App\Services\MarketingAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadToPurchaseConversionTest extends TestCase
{
    use RefreshDatabase;

    protected Course $course;
    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'title' => 'Retail Marketing Mastery',
            'slug' => 'retail-marketing-mastery',
            'short_description' => 'Comprehensive retail growth strategy.',
            'price' => 3999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);
    }

    public function test_lead_capture_saves_valid_submission_with_course_context(): void
    {
        $payload = [
            'name' => 'Vikram Seth',
            'email' => 'vikram@sethretail.com',
            'phone' => '+91 9876543210',
            'course_id' => $this->course->id,
            'source' => 'course_landing_faq',
            'subject' => 'Retail Marketing Inquiry',
            'message' => 'Does this course include inventory marketing strategies?',
            'website' => '',
        ];

        $response = $this->post(route('leads.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('lead_success');

        $this->assertDatabaseHas('leads', [
            'name' => 'Vikram Seth',
            'email' => 'vikram@sethretail.com',
            'course_id' => $this->course->id,
            'status' => LeadStatus::NEW->value,
        ]);
    }

    public function test_lead_capture_fails_with_invalid_email_and_preserves_old_inputs(): void
    {
        $payload = [
            'name' => 'Invalid Email Tester',
            'email' => 'not-an-email',
            'message' => 'Help me please',
            'website' => '',
        ];

        $response = $this->post(route('leads.store'), $payload);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('leads', [
            'name' => 'Invalid Email Tester',
        ]);
    }

    public function test_honeypot_bot_submission_is_rejected(): void
    {
        $payload = [
            'name' => 'Bot Crawler',
            'email' => 'bot@automated-spam.com',
            'message' => 'Spam content',
            'website' => 'http://malicious-spam.com',
        ];

        $response = $this->post(route('leads.store'), $payload);

        $response->assertSessionHasErrors('website');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_attribution_parameters_are_captured_and_preserved_in_lead_activity(): void
    {
        $payload = [
            'name' => 'Pooja Hegde',
            'email' => 'pooja@growthstudio.in',
            'course_id' => $this->course->id,
            'message' => 'Interested in scaling our agency.',
            'source' => 'meta_ad',
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'q3_growth_accelerator',
            'utm_content' => 'video_creative_1',
            'utm_term' => 'marketing course',
            'referrer' => 'https://facebook.com',
            'website' => '',
        ];

        $response = $this->post(route('leads.store'), $payload);
        $response->assertSessionHasNoErrors();

        $lead = Lead::where('email', 'pooja@growthstudio.in')->first();
        $this->assertNotNull($lead);

        $createdActivity = $lead->activities()->where('activity_type', 'created')->first();
        $this->assertNotNull($createdActivity);
        $this->assertEquals('facebook', $createdActivity->properties['utm_source']);
        $this->assertEquals('cpc', $createdActivity->properties['utm_medium']);
        $this->assertEquals('q3_growth_accelerator', $createdActivity->properties['utm_campaign']);
    }

    public function test_repeated_lead_submission_is_deduplicated_without_overwriting_original_creation_activity(): void
    {
        /** @var LeadService $service */
        $service = app(LeadService::class);

        // First submission (First touch)
        $lead1 = $service->createOrDeduplicateLead([
            'name' => 'Arun Kumar',
            'email' => 'arun@kumarstores.in',
            'phone' => '9876543210',
            'source' => 'google_ads',
            'utm_source' => 'google',
            'utm_campaign' => 'search_brand',
            'message' => 'First inquiry message',
        ]);

        $this->assertDatabaseCount('leads', 1);

        // Repeated submission later through organic search (Returning touch)
        $lead2 = $service->createOrDeduplicateLead([
            'name' => 'Arun Kumar',
            'email' => 'arun@kumarstores.in',
            'phone' => '9876543210',
            'source' => 'organic_search',
            'utm_source' => 'bing',
            'utm_campaign' => 'organic_seo',
            'message' => 'Second follow-up question',
        ]);

        // Same lead ID retained
        $this->assertEquals($lead1->id, $lead2->id);
        $this->assertDatabaseCount('leads', 1);

        // Both activities preserved
        $createdActivity = $lead1->activities()->where('activity_type', 'created')->first();
        $inquiryActivity = $lead1->activities()->where('activity_type', 'inquiry_submitted')->first();

        $this->assertNotNull($createdActivity);
        $this->assertEquals('google', $createdActivity->properties['utm_source']);

        $this->assertNotNull($inquiryActivity);
        $this->assertEquals('bing', $inquiryActivity->properties['utm_source']);
    }

    public function test_contact_page_resolves_course_context_from_query_parameter(): void
    {
        // By course slug
        $response = $this->get(route('contact', ['course' => $this->course->slug]));
        $response->assertOk();
        $response->assertSee('value="' . $this->course->id . '" selected', false);

        // By course ID
        $responseId = $this->get(route('contact', ['course_id' => $this->course->id]));
        $responseId->assertOk();
        $responseId->assertSee('value="' . $this->course->id . '" selected', false);
    }

    public function test_promotional_lead_automation_automatically_skips_if_lead_is_converted(): void
    {
        $template = MarketingTemplate::create([
            'name' => 'Lead Follow-up Nudge',
            'subject' => 'Still interested in our marketing program?',
            'body_html' => '<p>Hello {{ lead.name }}, enroll today!</p>',
        ]);

        $automation = Automation::create([
            'name' => 'Unconverted Lead Follow-Up',
            'trigger_type' => AutomationTrigger::LEAD_CREATED,
            'status' => AutomationStatus::ACTIVE,
            'channel' => 'email',
            'template_id' => $template->id,
            'delay_minutes' => 0,
        ]);

        $lead = Lead::create([
            'name' => 'Converted Prospect',
            'email' => 'prospect@converted.com',
            'status' => LeadStatus::CONVERTED,
            'converted_at' => now(),
            'converted_user_id' => $this->student->id,
        ]);

        $execution = AutomationExecution::create([
            'automation_id' => $automation->id,
            'recipient_type' => 'lead',
            'recipient_id' => $lead->id,
            'trigger_event' => AutomationTrigger::LEAD_CREATED->value,
            'reference_id' => 'lead_nudge_' . $lead->id,
            'status' => AutomationExecutionStatus::PENDING,
            'channel' => 'email',
            'scheduled_at' => now()->subMinute(),
        ]);

        /** @var MarketingAutomationService $automationService */
        $automationService = app(MarketingAutomationService::class);
        $stats = $automationService->processDueExecutions(10);

        $freshExecution = $execution->fresh();
        $this->assertEquals(AutomationExecutionStatus::SKIPPED, $freshExecution->status);
        $this->assertStringContainsString('already converted', $freshExecution->failure_reason);
        $this->assertEquals(1, $stats['skipped']);
    }

    public function test_checkout_session_does_not_mark_lead_as_converted(): void
    {
        $lead = Lead::create([
            'name' => 'Student In Checkout',
            'email' => $this->student->email,
            'course_id' => $this->course->id,
            'status' => LeadStatus::NEW,
        ]);

        // Student creates a pending order / starts checkout
        $response = $this->actingAs($this->student)
            ->post(route('student.courses.purchase', $this->course));

        $response->assertRedirect();

        $freshLead = $lead->fresh();
        $this->assertEquals(LeadStatus::NEW, $freshLead->status);
        $this->assertNull($freshLead->converted_at);
        $this->assertFalse($freshLead->isConverted());
    }

    public function test_admin_lead_show_displays_associated_purchase_orders(): void
    {
        $lead = Lead::create([
            'name' => 'Active Customer Lead',
            'email' => $this->student->email,
            'course_id' => $this->course->id,
            'status' => LeadStatus::CONVERTED,
            'converted_at' => now(),
            'converted_user_id' => $this->student->id,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-CONV-001',
            'amount' => 399900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Associated Purchase Orders');
        $response->assertSee('MM-ORD-CONV-001');
        $response->assertSee('Retail Marketing Mastery');
    }
}
