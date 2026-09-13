<?php

namespace Tests\Feature;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationStatus;
use App\Enums\AutomationTrigger;
use App\Enums\UserRole;
use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateCategory;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppWebhookEvent;
use App\Services\PhoneNormalizationService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->studentUser = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        // Default test configuration for WhatsApp
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.provider', 'meta');
        Config::set('whatsapp.access_token', 'test_access_token_12345');
        Config::set('whatsapp.phone_number_id', '109876543210987');
        Config::set('whatsapp.business_account_id', '209876543210987');
        Config::set('whatsapp.webhook_verify_token', 'secret_verify_token_xyz');
        Config::set('whatsapp.app_secret', 'secret_app_hmac_key');
        Config::set('whatsapp.default_country', 'India');
    }

    protected function createLead(array $attributes = []): Lead
    {
        return Lead::create(array_merge([
            'name' => 'John Doe',
            'email' => 'john.' . uniqid() . '@example.com',
            'phone' => '9876543210',
            'country' => 'India',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
        ], $attributes));
    }

    protected function createCourse(array $attributes = []): Course
    {
        return Course::create(array_merge([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery-' . uniqid(),
            'short_description' => 'Master digital marketing fundamentals.',
            'description' => 'A comprehensive course',
            'price' => 4999,
            'is_published' => true,
        ], $attributes));
    }

    public function test_phone_normalization_for_indian_formats(): void
    {
        $service = app(PhoneNormalizationService::class);

        // 10-digit Indian number
        $this->assertEquals('+919876543210', $service->normalize('9876543210'));

        // 11-digit Indian number with leading 0
        $this->assertEquals('+919876543210', $service->normalize('09876543210'));

        // 12-digit Indian number with 91 prefix without plus
        $this->assertEquals('+919876543210', $service->normalize('919876543210'));

        // Formatted with spaces and hyphens
        $this->assertEquals('+919876543210', $service->normalize('+91 98765-43210'));

        // International format (US)
        $this->assertEquals('+14155552671', $service->normalize('+1 (415) 555-2671', 'US'));

        // Meta Cloud API format (without +)
        $this->assertEquals('919876543210', $service->toMetaFormat('+919876543210'));

        // Invalid numbers
        $this->assertNull($service->normalize('123'));
        $this->assertNull($service->normalize('not-a-phone-number'));
        $this->assertNull($service->normalize(null));
    }

    public function test_disabled_whatsapp_integration_prevents_dispatch(): void
    {
        Config::set('whatsapp.enabled', false);

        $template = WhatsAppTemplate::create([
            'name' => 'Enrollment Receipt',
            'template_name' => 'course_enrollment_receipt',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body_text' => 'Hello {{ user.name }}, thank you for enrolling.',
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'phone' => '9876543210',
            'whatsapp_opt_in' => true,
        ]);

        $service = app(WhatsAppService::class);
        $result = $service->sendTemplateMessage($lead, $template);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('disabled', strtolower($result->error));
    }

    public function test_marketing_opt_in_and_opt_out_recording(): void
    {
        $lead = $this->createLead([
            'phone' => '9876543210',
            'whatsapp_opt_in' => false,
        ]);

        $this->assertFalse($lead->hasWhatsAppOptIn());

        // Record Opt-In
        $lead->recordWhatsAppOptIn('landing_page_form');
        $lead->refresh();

        $this->assertTrue($lead->hasWhatsAppOptIn());
        $this->assertNotNull($lead->whatsapp_opted_in_at);
        $this->assertNull($lead->whatsapp_opted_out_at);
        $this->assertEquals('landing_page_form', $lead->whatsapp_consent_source);

        // Record Opt-Out
        $lead->recordWhatsAppOptOut();
        $lead->refresh();

        $this->assertFalse($lead->hasWhatsAppOptIn());
        $this->assertNotNull($lead->whatsapp_opted_out_at);
    }

    public function test_public_whatsapp_opt_out_route(): void
    {
        $lead = $this->createLead([
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => true,
        ]);

        // GET opt-out page
        $response = $this->get(route('whatsapp.opt-out', ['phone' => '+919876543210']));
        $response->assertOk();
        $response->assertSee('Opt Out of WhatsApp Marketing');

        // POST opt-out submission
        $postResponse = $this->post(route('whatsapp.opt-out.submit'), [
            'phone' => '9876543210',
        ]);

        $postResponse->assertRedirect();
        $lead->refresh();
        $this->assertFalse($lead->hasWhatsAppOptIn());
        $this->assertNotNull($lead->whatsapp_opted_out_at);
    }

    public function test_marketing_message_blocked_without_opt_in(): void
    {
        $template = WhatsAppTemplate::create([
            'name' => 'Weekly Marketing Offer',
            'template_name' => 'weekly_marketing_offer',
            'category' => WhatsAppTemplateCategory::MARKETING,
            'language' => 'en',
            'body' => 'Exclusive course offer: {{ course.title }}',
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => false,
        ]);

        $service = app(WhatsAppService::class);
        $result = $service->sendTemplateMessage($lead, $template);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('opted in', strtolower($result->error));
        $this->assertDatabaseMissing('whatsapp_messages', [
            'lead_id' => $lead->id,
        ]);
    }

    public function test_transactional_utility_message_allowed_without_marketing_opt_in(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5MA==']],
            ], 200),
        ]);

        $template = WhatsAppTemplate::create([
            'name' => 'Enrollment Receipt',
            'template_name' => 'course_enrollment_receipt',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body' => 'Hello {{ user.name }}, your enrollment is confirmed.',
            'variables' => ['user.name'],
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => false, // Transactional utility message does not require marketing opt-in
        ]);

        $service = app(WhatsAppService::class);
        $result = $service->sendTemplateMessage($user, $template, ['user.name' => $user->name]);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->message);
        $this->assertEquals('wamid.HBgLMTIzNDU2Nzg5MA==', $result->message->provider_message_id);
        $this->assertEquals(WhatsAppMessageStatus::SENT, $result->message->status);
    }

    public function test_safe_variable_interpolation_without_code_execution(): void
    {
        $course = $this->createCourse([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
        ]);

        $template = WhatsAppTemplate::create([
            'name' => 'Course Welcome',
            'template_name' => 'course_welcome',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body' => 'Hi {{ lead.name }}, your course {{ course.title }} is at {{ course.url }}. Injected: {{ <?php phpinfo(); ?> }}',
            'variables' => ['lead.name', 'course.title', 'course.url'],
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'name' => 'Priya Sharma',
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => true,
        ]);

        $service = app(WhatsAppService::class);
        $context = [
            'course_id' => $course->id,
        ];

        $rendered = $service->interpolateTemplateBody($template, $lead, $context);

        $this->assertStringContainsString('Hi Priya Sharma', $rendered);
        $this->assertStringContainsString('Digital Marketing Mastery', $rendered);
        $this->assertStringContainsString(route('courses.show', $course->slug), $rendered);
        // Ensure arbitrary executable code is stripped and not executed
        $this->assertStringNotContainsString('phpinfo', $rendered);
        $this->assertStringNotContainsString('<?php', $rendered);
    }

    public function test_idempotency_prevents_duplicate_dispatch(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5MA==']],
            ], 200),
        ]);

        $template = WhatsAppTemplate::create([
            'name' => 'Enrollment Confirmation',
            'template_name' => 'course_enrollment_receipt',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body' => 'Welcome {{ user.name }}',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone' => '+919876543210',
            'phone_normalized' => '+919876543210',
        ]);

        $service = app(WhatsAppService::class);

        // First dispatch
        $result1 = $service->sendTemplateMessage(
            recipient: $user,
            template: $template,
            idempotencyKey: 'idemp_key_order_999'
        );
        $this->assertTrue($result1->success);

        // Second dispatch with same idempotency key
        $result2 = $service->sendTemplateMessage(
            recipient: $user,
            template: $template,
            idempotencyKey: 'idemp_key_order_999'
        );
        $this->assertTrue($result2->success);

        // Meta API should have been called only ONCE
        Http::assertSentCount(1);
        $this->assertEquals($result1->message->id, $result2->message->id);
    }

    public function test_meta_provider_failure_is_handled_gracefully(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded for template dispatches.',
                    'type' => 'OAuthException',
                    'code' => 130429,
                    'fbtrace_id' => 'AbCdEf12345',
                ],
            ], 429),
        ]);

        $template = WhatsAppTemplate::create([
            'name' => 'Urgent Notification',
            'template_name' => 'urgent_notification',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body' => 'System alert',
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'phone' => '+919876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => true,
        ]);

        $service = app(WhatsAppService::class);
        $result = $service->sendTemplateMessage($lead, $template);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->message);
        $this->assertEquals(WhatsAppMessageStatus::FAILED, $result->message->status);
        $this->assertEquals('130429', $result->message->error_code);
        $this->assertStringContainsString('Rate limit', $result->message->error_message);
    }

    public function test_webhook_challenge_verification(): void
    {
        // Valid verify token
        $response = $this->get('/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'secret_verify_token_xyz',
            'hub_challenge' => '1158201444',
        ]));

        $response->assertOk();
        $this->assertEquals('1158201444', $response->getContent());

        // Invalid verify token returns 403
        $invalidResponse = $this->get('/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => '1158201444',
        ]));

        $invalidResponse->assertStatus(403);
    }

    public function test_webhook_signature_verification_and_delivery_status_update(): void
    {
        $message = WhatsAppMessage::create([
            'phone_number' => '+919876543210',
            'phone_normalized' => '+919876543210',
            'provider_message_id' => 'wamid.HBgLMTIzNDU2Nzg5MA==',
            'direction' => 'outbound',
            'message_type' => 'template',
            'status' => WhatsAppMessageStatus::SENT,
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => '109876543210987',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMTIzNDU2Nzg5MA==',
                                        'status' => 'delivered',
                                        'timestamp' => (string) time(),
                                        'recipient_id' => '919876543210',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawJson = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $rawJson, config('whatsapp.app_secret'));

        $response = $this->call(
            method: 'POST',
            uri: '/webhooks/whatsapp',
            server: [
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $rawJson
        );

        $response->assertOk();
        $message->refresh();

        $this->assertEquals(WhatsAppMessageStatus::DELIVERED, $message->status);
        $this->assertNotNull($message->delivered_at);

        // Next update to 'read'
        $readPayload = $payload;
        $readPayload['entry'][0]['changes'][0]['value']['statuses'][0]['status'] = 'read';
        $readRaw = json_encode($readPayload);
        $readSig = 'sha256=' . hash_hmac('sha256', $readRaw, config('whatsapp.app_secret'));

        $readResponse = $this->call(
            method: 'POST',
            uri: '/webhooks/whatsapp',
            server: [
                'HTTP_X_HUB_SIGNATURE_256' => $readSig,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $readRaw
        );

        $readResponse->assertOk();
        $message->refresh();

        $this->assertEquals(WhatsAppMessageStatus::READ, $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_webhook_event_deduplication(): void
    {
        $payload = [
            'entry' => [
                [
                    'id' => '109876543210987',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.UNIQUE_EVENT_123',
                                        'status' => 'delivered',
                                        'timestamp' => '1700000000',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawJson = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $rawJson, config('whatsapp.app_secret'));

        // First webhook post
        $response1 = $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawJson);
        $response1->assertOk();

        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'event_id' => 'wamid.UNIQUE_EVENT_123_delivered_1700000000',
        ]);

        // Second webhook post with identical payload
        $response2 = $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawJson);
        $response2->assertOk();

        // Should only have 1 event record in database
        $this->assertEquals(1, WhatsAppWebhookEvent::where('event_id', 'wamid.UNIQUE_EVENT_123_delivered_1700000000')->count());
    }

    public function test_marketing_automation_executes_through_whatsapp_channel(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.AUTO_WHATSAPP_EXEC_01']],
            ], 200),
        ]);

        $whatsappTemplate = WhatsAppTemplate::create([
            'name' => 'Auto Welcome Lead',
            'template_name' => 'auto_welcome_lead',
            'category' => WhatsAppTemplateCategory::MARKETING,
            'language' => 'en',
            'body' => 'Welcome {{ lead.name }} to Marketian Mind!',
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'name' => 'Amit Verma',
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => true,
        ]);

        $automation = Automation::create([
            'name' => 'WhatsApp Lead Welcome Workflow',
            'channel' => 'whatsapp',
            'trigger_type' => AutomationTrigger::LEAD_CREATED,
            'status' => AutomationStatus::ACTIVE,
            'whatsapp_template_id' => $whatsappTemplate->id,
            'delay_minutes' => 0,
        ]);

        $execution = AutomationExecution::create([
            'automation_id' => $automation->id,
            'recipient_type' => 'lead',
            'recipient_id' => $lead->id,
            'trigger_event' => AutomationTrigger::LEAD_CREATED->value,
            'channel' => 'whatsapp',
            'status' => AutomationExecutionStatus::PENDING,
            'scheduled_at' => now()->subMinute(),
        ]);

        $automationService = app(\App\Services\MarketingAutomationService::class);
        $stats = $automationService->processDueExecutions(10);

        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(1, $stats['sent']);

        $execution->refresh();
        $this->assertEquals(AutomationExecutionStatus::SENT, $execution->status);
        $this->assertNotNull($execution->whatsapp_message_id);

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $execution->whatsapp_message_id,
            'provider_message_id' => 'wamid.AUTO_WHATSAPP_EXEC_01',
            'status' => WhatsAppMessageStatus::SENT->value,
        ]);
    }

    public function test_admin_lead_show_page_and_manual_whatsapp_send(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.MANUAL_SEND_TEST_99']],
            ], 200),
        ]);

        $template = WhatsAppTemplate::create([
            'name' => 'Inquiry Reply',
            'template_name' => 'inquiry_reply',
            'category' => WhatsAppTemplateCategory::UTILITY,
            'language' => 'en',
            'body' => 'Hello {{ lead.name }}, we received your inquiry.',
            'is_active' => true,
        ]);

        $lead = $this->createLead([
            'name' => 'Rahul Roy',
            'phone' => '9876543210',
            'phone_normalized' => '+919876543210',
            'whatsapp_opt_in' => true,
        ]);

        // Access Lead Show Page
        $showResponse = $this->actingAs($this->adminUser)->get(route('admin.leads.show', $lead));
        $showResponse->assertOk();
        $showResponse->assertSee('WhatsApp Status');
        $showResponse->assertSee('Opted In');

        // Trigger manual send
        $sendResponse = $this->actingAs($this->adminUser)->post(route('admin.whatsapp.manual-send'), [
            'recipient_type' => 'lead',
            'recipient_id' => $lead->id,
            'template_id' => $template->id,
        ]);

        $sendResponse->assertRedirect();
        $sendResponse->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_messages', [
            'lead_id' => $lead->id,
            'provider_message_id' => 'wamid.MANUAL_SEND_TEST_99',
            'status' => WhatsAppMessageStatus::SENT->value,
        ]);
    }

    public function test_admin_authorization_protects_whatsapp_dashboard_and_templates(): void
    {
        // Unauthenticated guests redirected
        $guestResponse = $this->get(route('admin.whatsapp.dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // Students receive 403 Forbidden
        $studentResponse = $this->actingAs($this->studentUser)->get(route('admin.whatsapp.dashboard'));
        $studentResponse->assertStatus(403);

        // Admins can access dashboard, templates, messages, and settings
        $adminDashboard = $this->actingAs($this->adminUser)->get(route('admin.whatsapp.dashboard'));
        $adminDashboard->assertOk();
        $adminDashboard->assertSee('WhatsApp Integration');

        $adminTemplates = $this->actingAs($this->adminUser)->get(route('admin.whatsapp.templates.index'));
        $adminTemplates->assertOk();

        $adminMessages = $this->actingAs($this->adminUser)->get(route('admin.whatsapp.messages.index'));
        $adminMessages->assertOk();

        $adminSettings = $this->actingAs($this->adminUser)->get(route('admin.whatsapp.settings'));
        $adminSettings->assertOk();
        $adminSettings->assertSee('WhatsApp Cloud API Configuration');
    }
}
