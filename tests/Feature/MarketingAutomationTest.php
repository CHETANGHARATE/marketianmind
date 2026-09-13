<?php

namespace Tests\Feature;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationStatus;
use App\Enums\AutomationTrigger;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Mail\MarketingAutomationMail;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Lead;
use App\Models\MarketingTemplate;
use App\Models\MarketingUnsubscribe;
use App\Models\Order;
use App\Models\User;
use App\Services\MarketingAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected MarketingTemplate $welcomeTemplate;
    protected MarketingTemplate $nurtureTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->welcomeTemplate = MarketingTemplate::create([
            'name' => 'Lead Welcome Guide',
            'slug' => 'lead-welcome-guide',
            'subject' => 'Welcome to Marketian Mind, {{ lead.name }}!',
            'body_html' => '<h1>Hello {{ lead.name }}</h1><p>Thanks for contacting us from {{ lead.company_name }}.</p><a href="{{ unsubscribe_url }}">Unsubscribe</a>',
            'description' => 'Sent to new inbound leads',
        ]);

        $this->nurtureTemplate = MarketingTemplate::create([
            'name' => 'Course Completion Next Steps',
            'slug' => 'course-completion-next-steps',
            'subject' => 'Congratulations on completing {{ course.title }}!',
            'body_html' => '<p>Hi {{ user.name }}, you have mastered {{ course.title }}.</p>',
            'description' => 'Sent when a course is completed',
        ]);
    }

    public function test_guests_and_students_cannot_access_automation_admin_endpoints(): void
    {
        $this->get(route('admin.automations.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.marketing-templates.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->student)
            ->get(route('admin.automations.index'))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('admin.marketing-templates.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_automations_index_and_create_an_automation(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.automations.index'));

        $response->assertOk()
            ->assertSee('Automations Engine')
            ->assertSee('New Automation');

        $createResponse = $this->actingAs($this->admin)
            ->post(route('admin.automations.store'), [
                'name' => 'New Lead Nurture Sequence',
                'description' => 'Immediate response to new leads',
                'trigger_type' => AutomationTrigger::LEAD_CREATED->value,
                'status' => AutomationStatus::ACTIVE->value,
                'template_id' => $this->welcomeTemplate->id,
                'delay_minutes' => 15,
                'conditions' => json_encode(['source' => 'website']),
            ]);

        $createResponse->assertRedirect();

        $this->assertDatabaseHas('automations', [
            'name' => 'New Lead Nurture Sequence',
            'trigger_type' => AutomationTrigger::LEAD_CREATED->value,
            'status' => AutomationStatus::ACTIVE->value,
            'delay_minutes' => 15,
        ]);
    }

    public function test_admin_can_edit_and_toggle_automation_status(): void
    {
        $automation = Automation::create([
            'name' => 'Test Automation Rule',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.automations.edit', $automation))
            ->assertOk()
            ->assertSee('Test Automation Rule');

        $this->actingAs($this->admin)
            ->put(route('admin.automations.update', $automation), [
                'name' => 'Updated Automation Rule',
                'trigger_type' => AutomationTrigger::STUDENT_REGISTERED->value,
                'status' => AutomationStatus::ACTIVE->value,
                'template_id' => $this->welcomeTemplate->id,
                'delay_minutes' => 30,
            ])
            ->assertRedirect(route('admin.automations.show', $automation));

        $this->assertEquals('Updated Automation Rule', $automation->fresh()->name);
        $this->assertEquals(30, $automation->fresh()->delay_minutes);

        // Toggle status to paused
        $this->actingAs($this->admin)
            ->patch(route('admin.automations.toggle', $automation), [
                'status' => AutomationStatus::PAUSED->value,
            ])
            ->assertRedirect();

        $this->assertEquals(AutomationStatus::PAUSED, $automation->fresh()->status);
    }

    public function test_admin_can_manage_marketing_templates_crud(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.marketing-templates.store'), [
                'name' => 'Special Founder Welcome',
                'slug' => 'special-founder-welcome',
                'subject' => 'Exclusive founder training for {{ user.name }}',
                'body_html' => '<p>Hello {{ user.name }}, welcome aboard!</p>',
                'description' => 'Dedicated founder track template',
            ])
            ->assertRedirect(route('admin.marketing-templates.index'));

        $this->assertDatabaseHas('marketing_templates', [
            'slug' => 'special-founder-welcome',
        ]);

        $tpl = MarketingTemplate::where('slug', 'special-founder-welcome')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.marketing-templates.update', $tpl), [
                'name' => 'Special Founder Welcome Revised',
                'slug' => 'special-founder-welcome',
                'subject' => 'Updated Subject',
                'body_html' => '<p>Updated body</p>',
            ])
            ->assertRedirect(route('admin.marketing-templates.index'));

        $this->assertEquals('Special Founder Welcome Revised', $tpl->fresh()->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.marketing-templates.destroy', $tpl))
            ->assertRedirect(route('admin.marketing-templates.index'));

        $this->assertDatabaseMissing('marketing_templates', ['id' => $tpl->id]);
    }

    public function test_lead_creation_triggers_matching_active_automation(): void
    {
        Mail::fake();

        $automation = Automation::create([
            'name' => 'Instant Lead Welcome',
            'trigger_type' => AutomationTrigger::LEAD_CREATED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $lead = Lead::create([
            'name' => 'Vikram Sharma',
            'email' => 'vikram@example.com',
            'phone' => '9876543210',
            'company_name' => 'Sharma Retail',
            'source' => 'website',
            'status' => LeadStatus::NEW->value,
        ]);

        app(MarketingAutomationService::class)->dispatchTrigger(
            AutomationTrigger::LEAD_CREATED,
            $lead,
            ['source' => 'website'],
            'lead_created_' . $lead->id
        );

        $this->assertDatabaseHas('automation_executions', [
            'automation_id' => $automation->id,
            'recipient_type' => 'lead',
            'recipient_id' => $lead->id,
            'status' => AutomationExecutionStatus::PENDING->value,
        ]);

        // Process due executions
        $service = app(MarketingAutomationService::class);
        $stats = $service->processDueExecutions();

        $this->assertEquals(1, $stats['sent']);

        $this->assertDatabaseHas('automation_executions', [
            'automation_id' => $automation->id,
            'recipient_id' => $lead->id,
            'status' => AutomationExecutionStatus::SENT->value,
        ]);

        Mail::assertSent(MarketingAutomationMail::class, function ($mail) {
            return $mail->hasTo('vikram@example.com')
                && str_contains($mail->emailSubject, 'Vikram Sharma')
                && str_contains($mail->bodyHtml, 'Sharma Retail');
        });
    }

    public function test_duplicate_trigger_dispatches_are_idempotent(): void
    {
        $automation = Automation::create([
            'name' => 'Idempotency Test Automation',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $service = app(MarketingAutomationService::class);

        // First dispatch
        $count1 = $service->dispatchTrigger(
            AutomationTrigger::STUDENT_REGISTERED,
            $this->student,
            [],
            'student_reg_' . $this->student->id
        );

        // Immediate identical dispatch (e.g. network retry or double webhook)
        $count2 = $service->dispatchTrigger(
            AutomationTrigger::STUDENT_REGISTERED,
            $this->student,
            [],
            'student_reg_' . $this->student->id
        );

        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);

        $this->assertEquals(
            1,
            AutomationExecution::where('automation_id', $automation->id)
                ->where('recipient_id', $this->student->id)
                ->count()
        );
    }

    public function test_delay_mechanism_defers_execution_until_scheduled_time(): void
    {
        Mail::fake();

        $automation = Automation::create([
            'name' => 'Delayed Follow-up',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 60, // 1 hour delay
        ]);

        $service = app(MarketingAutomationService::class);
        $service->dispatchTrigger(
            AutomationTrigger::STUDENT_REGISTERED,
            $this->student,
            [],
            'reg_' . $this->student->id
        );

        $execution = AutomationExecution::firstOrFail();
        $this->assertTrue($execution->scheduled_at->isFuture());

        // Processing now should NOT process the deferred execution
        $stats = $service->processDueExecutions();
        $this->assertEquals(0, $stats['processed']);
        Mail::assertNothingSent();

        // Time travel 65 minutes into the future
        $this->travel(65)->minutes();

        $statsAfter = $service->processDueExecutions();
        $this->assertEquals(1, $statsAfter['processed']);
        $this->assertEquals(1, $statsAfter['sent']);
        Mail::assertSent(MarketingAutomationMail::class);
    }

    public function test_unsubscribed_recipients_are_automatically_skipped_without_sending_email(): void
    {
        Mail::fake();

        $automation = Automation::create([
            'name' => 'Opt Out Test',
            'trigger_type' => AutomationTrigger::LEAD_CREATED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $unsubscribedEmail = 'optout@business.com';
        MarketingUnsubscribe::recordUnsubscribe($unsubscribedEmail, 'User unsubscribed');

        $lead = Lead::create([
            'name' => 'Opt Out Lead',
            'email' => $unsubscribedEmail,
            'source' => 'website',
            'status' => LeadStatus::NEW->value,
        ]);

        $service = app(MarketingAutomationService::class);
        $service->dispatchTrigger(
            AutomationTrigger::LEAD_CREATED,
            $lead,
            [],
            'lead_' . $lead->id
        );

        $stats = $service->processDueExecutions();

        $this->assertEquals(1, $stats['skipped']);
        $this->assertEquals(0, $stats['sent']);
        Mail::assertNothingSent();

        $execution = AutomationExecution::firstOrFail();
        $this->assertEquals(AutomationExecutionStatus::SKIPPED, $execution->status);
        $this->assertStringContainsString('unsubscribed', $execution->failure_reason);
    }

    public function test_public_unsubscribe_route_records_email_opt_out(): void
    {
        $testEmail = 'founder@startup.co';

        $this->get(route('marketing.unsubscribe', ['email' => $testEmail]))
            ->assertOk()
            ->assertSee('Unsubscribe from Marketing')
            ->assertSee($testEmail);

        $response = $this->post(route('marketing.unsubscribe.submit'), [
            'email' => $testEmail,
            'reason' => 'Too many emails received',
        ]);

        $response->assertOk()
            ->assertSee('Unsubscribed Successfully');

        $this->assertTrue(MarketingUnsubscribe::isUnsubscribed($testEmail));
        $this->assertDatabaseHas('marketing_unsubscribes', [
            'email' => $testEmail,
            'reason' => 'Too many emails received',
        ]);
    }

    public function test_conditions_filter_out_non_matching_recipients(): void
    {
        Mail::fake();

        // Automation specifically for Qualified leads only
        $automation = Automation::create([
            'name' => 'VIP Qualified Lead Guide',
            'trigger_type' => AutomationTrigger::LEAD_STATUS_CHANGED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
            'conditions' => ['status' => 'qualified'],
        ]);

        $unqualifiedLead = Lead::create([
            'name' => 'Contact Lead',
            'email' => 'contact@test.com',
            'source' => 'website',
            'status' => LeadStatus::CONTACTED->value,
        ]);

        $qualifiedLead = Lead::create([
            'name' => 'Qualified Lead',
            'email' => 'qualified@test.com',
            'source' => 'website',
            'status' => LeadStatus::QUALIFIED->value,
        ]);

        $service = app(MarketingAutomationService::class);

        // Dispatch for unqualified lead - condition mismatch should prevent enqueueing
        $countUnqualified = $service->dispatchTrigger(
            AutomationTrigger::LEAD_STATUS_CHANGED,
            $unqualifiedLead,
            ['status' => 'contacted'],
            'unqualified_' . $unqualifiedLead->id
        );

        // Dispatch for qualified lead - condition match should enqueue
        $countQualified = $service->dispatchTrigger(
            AutomationTrigger::LEAD_STATUS_CHANGED,
            $qualifiedLead,
            ['status' => 'qualified'],
            'qualified_' . $qualifiedLead->id
        );

        $this->assertEquals(0, $countUnqualified);
        $this->assertEquals(1, $countQualified);

        $stats = $service->processDueExecutions();
        $this->assertEquals(1, $stats['sent']);

        Mail::assertSent(MarketingAutomationMail::class, function ($mail) {
            return $mail->hasTo('qualified@test.com');
        });
    }

    public function test_paused_automations_are_not_sent(): void
    {
        Mail::fake();

        $automation = Automation::create([
            'name' => 'Paused Rule',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::PAUSED,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $service = app(MarketingAutomationService::class);
        $dispatched = $service->dispatchTrigger(
            AutomationTrigger::STUDENT_REGISTERED,
            $this->student,
            [],
            'paused_' . $this->student->id
        );

        // Since automation was paused, it shouldn't even be dispatched
        $this->assertEquals(0, $dispatched);
        Mail::assertNothingSent();
    }

    public function test_console_command_processes_due_marketing_automations(): void
    {
        Mail::fake();

        $automation = Automation::create([
            'name' => 'Console Batch Rule',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->nurtureTemplate->id,
            'delay_minutes' => 0,
        ]);

        $execution = AutomationExecution::create([
            'automation_id' => $automation->id,
            'recipient_type' => 'user',
            'recipient_id' => $this->student->id,
            'trigger_event' => AutomationTrigger::STUDENT_REGISTERED->value,
            'reference_id' => 'batch_ref_1',
            'scheduled_at' => now()->subMinute(),
            'status' => AutomationExecutionStatus::PENDING,
        ]);

        $this->artisan('automation:process', ['--batch' => 20])
            ->expectsOutputToContain('Processing due marketing automations')
            ->expectsOutputToContain('- Sent:      1')
            ->assertSuccessful();

        $this->assertEquals(AutomationExecutionStatus::SENT, $execution->fresh()->status);
        Mail::assertSent(MarketingAutomationMail::class);
    }

    public function test_student_registration_fires_automation_trigger(): void
    {
        $automation = Automation::create([
            'name' => 'Registration Onboarding',
            'trigger_type' => AutomationTrigger::STUDENT_REGISTERED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Aarav Patel',
            'email' => 'aarav@patelmarketing.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'aarav@patelmarketing.com')->firstOrFail();

        $this->assertDatabaseHas('automation_executions', [
            'automation_id' => $automation->id,
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'trigger_event' => AutomationTrigger::STUDENT_REGISTERED->value,
            'status' => AutomationExecutionStatus::PENDING->value,
        ]);
    }

    public function test_free_course_enrollment_fires_automation_trigger(): void
    {
        $course = Course::create([
            'title' => 'Social Media Fundamentals',
            'slug' => 'social-media-fundamentals',
            'short_description' => 'Short intro.',
            'description' => 'Free introductory course.',
            'price' => 0.00,
            'is_free' => true,
            'status' => \App\Enums\CourseStatus::PUBLISHED,
        ]);

        $automation = Automation::create([
            'name' => 'Course Enrollment Kickoff',
            'trigger_type' => AutomationTrigger::COURSE_ENROLLED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->welcomeTemplate->id,
            'delay_minutes' => 0,
        ]);

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $course))
            ->assertRedirect();

        $this->assertDatabaseHas('automation_executions', [
            'automation_id' => $automation->id,
            'recipient_type' => 'user',
            'recipient_id' => $this->student->id,
            'trigger_event' => AutomationTrigger::COURSE_ENROLLED->value,
        ]);
    }

    public function test_course_completion_fires_automation_trigger(): void
    {
        $course = Course::create([
            'title' => 'Email Marketing Mastery',
            'slug' => 'email-marketing-mastery',
            'short_description' => 'Short email mastery.',
            'description' => 'Comprehensive email training.',
            'price' => 1999.00,
            'status' => \App\Enums\CourseStatus::PUBLISHED,
        ]);

        $automation = Automation::create([
            'name' => 'Graduate Upsell',
            'trigger_type' => AutomationTrigger::COURSE_COMPLETED,
            'status' => AutomationStatus::ACTIVE,
            'template_id' => $this->nurtureTemplate->id,
            'delay_minutes' => 0,
        ]);

        app(\App\Services\EngagementService::class)->handleCourseCompletion($this->student, $course);

        $this->assertDatabaseHas('automation_executions', [
            'automation_id' => $automation->id,
            'recipient_type' => 'user',
            'recipient_id' => $this->student->id,
            'trigger_event' => AutomationTrigger::COURSE_COMPLETED->value,
        ]);
    }

    public function test_template_variable_rendering_safety(): void
    {
        $service = app(MarketingAutomationService::class);

        $rawTemplate = 'Hello {{ user.name }}, check out {{ unmapped.variable }}! Attack: {{ system("whoami") }}';
        $context = [
            'user.name' => '<b>Ananya</b>',
        ];

        $rendered = $service->renderTemplate($rawTemplate, $context);

        // Bold tag should be HTML escaped
        $this->assertStringContainsString('&lt;b&gt;Ananya&lt;/b&gt;', $rendered);
        // Unmapped and invalid variables should be safely stripped
        $this->assertStringNotContainsString('{{ unmapped.variable }}', $rendered);
        $this->assertStringNotContainsString('system', $rendered);
    }

    public function test_admin_can_trigger_manual_process_now(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.automations.process-now'));

        $response->assertRedirect()
            ->assertSessionHas('success');
    }
}
