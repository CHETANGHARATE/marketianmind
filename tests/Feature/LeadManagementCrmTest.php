<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNote;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeadManagementCrmTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staffAdmin;
    protected User $student;
    protected Course $course;
    protected Bundle $bundle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'CRM Administrator',
            'email' => 'crm-admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->staffAdmin = User::factory()->create([
            'name' => 'Staff Manager',
            'email' => 'staff@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Normal Student',
            'email' => 'student@marketianmind.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
            'short_description' => 'Comprehensive marketing course.',
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->bundle = Bundle::create([
            'title' => 'Growth Accelerator Bundle',
            'slug' => 'growth-accelerator-bundle',
            'description' => 'All in one growth pack.',
            'price' => 9999.00,
            'status' => 'published',
        ]);
    }

    public function test_guest_cannot_access_crm_routes(): void
    {
        $lead = Lead::create([
            'name' => 'Test Lead',
            'email' => 'test@lead.com',
            'status' => LeadStatus::NEW->value,
        ]);

        $this->get(route('admin.leads.create'))->assertRedirect(route('login'));
        $this->post(route('admin.leads.store'), [])->assertRedirect(route('login'));
        $this->get(route('admin.leads.edit', $lead))->assertRedirect(route('login'));
        $this->post(route('admin.leads.notes.store', $lead), ['content' => 'Note'])->assertRedirect(route('login'));
        $this->post(route('admin.leads.convert', $lead))->assertRedirect(route('login'));
        $this->patch(route('admin.leads.follow-up.complete', $lead))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_crm_routes(): void
    {
        $lead = Lead::create([
            'name' => 'Test Lead',
            'email' => 'test@lead.com',
            'status' => LeadStatus::NEW->value,
        ]);

        $this->actingAs($this->student)->get(route('admin.leads.create'))->assertForbidden();
        $this->actingAs($this->student)->post(route('admin.leads.store'), [])->assertForbidden();
        $this->actingAs($this->student)->get(route('admin.leads.edit', $lead))->assertForbidden();
        $this->actingAs($this->student)->post(route('admin.leads.notes.store', $lead), ['content' => 'Note'])->assertForbidden();
        $this->actingAs($this->student)->post(route('admin.leads.convert', $lead))->assertForbidden();
        $this->actingAs($this->student)->patch(route('admin.leads.follow-up.complete', $lead))->assertForbidden();
    }

    public function test_admin_can_view_lead_creation_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.leads.create'));

        $response->assertOk();
        $response->assertSee('Create New Lead');
        $response->assertSee('Digital Marketing Mastery');
        $response->assertSee('Growth Accelerator Bundle');
    }

    public function test_admin_can_create_lead_with_full_details_and_initial_note(): void
    {
        $data = [
            'name' => 'Aarav Sharma',
            'email' => 'aarav@sharmaretail.com',
            'phone' => '+91 98765 11223',
            'company_name' => 'Sharma Retail Pvt Ltd',
            'job_title' => 'Managing Director',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'source' => 'phone_inquiry',
            'priority' => LeadPriority::HIGH->value,
            'status' => LeadStatus::QUALIFIED->value,
            'assigned_to' => $this->staffAdmin->id,
            'course_id' => $this->course->id,
            'bundle_id' => $this->bundle->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'follow_up_status' => 'call_scheduled',
            'initial_note' => 'Spoke for 20 mins. Interested in enrolling entire sales team of 8 people.',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'name' => 'Aarav Sharma',
            'email' => 'aarav@sharmaretail.com',
            'phone' => '+91 98765 11223',
            'company_name' => 'Sharma Retail Pvt Ltd',
            'job_title' => 'Managing Director',
            'priority' => LeadPriority::HIGH->value,
            'status' => LeadStatus::QUALIFIED->value,
            'assigned_to' => $this->staffAdmin->id,
            'course_id' => $this->course->id,
            'bundle_id' => $this->bundle->id,
            'follow_up_status' => 'call_scheduled',
        ]);

        $lead = Lead::where('email', 'aarav@sharmaretail.com')->first();
        $this->assertNotNull($lead);

        // Verify initial note created
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $this->admin->id,
            'content' => 'Spoke for 20 mins. Interested in enrolling entire sales team of 8 people.',
        ]);

        // Verify activity logged
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'created',
        ]);

        // Verify Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => 'Lead',
        ]);
    }

    public function test_lead_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.leads.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'status' => 'invalid_status',
            'priority' => 'invalid_priority',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'status', 'priority']);
    }

    public function test_public_lead_capture_deduplicates_existing_lead_and_appends_note(): void
    {
        // Initial lead created
        $initialLead = Lead::create([
            'name' => 'Rajesh Gupta',
            'email' => 'rajesh@guptatech.in',
            'phone' => '+91 99999 88888',
            'source' => 'website',
            'status' => LeadStatus::NEW->value,
            'message' => 'First inquiry about digital marketing.',
        ]);

        $this->assertEquals(1, Lead::where('email', 'rajesh@guptatech.in')->count());

        // Subsequent public inquiry with same email
        $captureData = [
            'name' => 'Rajesh Gupta (Updated)',
            'email' => 'RAJESH@guptatech.in ', // mixed case and trailing spaces
            'phone' => '+91 99999 77777',
            'subject' => 'Follow up inquiry',
            'message' => 'Need pricing details for the full bundle as well.',
            'source' => 'bundle_page',
            'bundle_id' => $this->bundle->id,
        ];

        $response = $this->post(route('contact.submit'), $captureData);
        $response->assertSessionHas('success');

        // Verify no duplicate lead record was created
        $this->assertEquals(1, Lead::where('email', 'rajesh@guptatech.in')->count());

        // Verify an internal note was appended with inquiry details
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $initialLead->id,
        ]);

        // Verify activity logged
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $initialLead->id,
            'activity_type' => 'inquiry_submitted',
        ]);

        $freshLead = $initialLead->fresh();
        $this->assertNotNull($freshLead->last_contacted_at);
        $this->assertEquals('+91 99999 77777', $freshLead->phone);
    }

    public function test_public_lead_capture_reopens_closed_or_lost_lead(): void
    {
        $closedLead = Lead::create([
            'name' => 'Sneha Rao',
            'email' => 'sneha@example.com',
            'source' => 'contact_page',
            'status' => LeadStatus::LOST->value,
            'message' => 'Not interested earlier.',
        ]);

        $response = $this->post(route('contact.submit'), [
            'name' => 'Sneha Rao',
            'email' => 'sneha@example.com',
            'message' => 'I changed my mind, ready to join the next batch!',
        ]);

        $response->assertSessionHas('success');

        $freshLead = $closedLead->fresh();
        $this->assertEquals(LeadStatus::NEW, $freshLead->status);
    }

    public function test_admin_can_add_internal_note_to_lead(): void
    {
        $lead = Lead::create([
            'name' => 'Vikram Seth',
            'email' => 'vikram@sethconsulting.com',
            'status' => LeadStatus::CONTACTED->value,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leads.notes.store', $lead), [
            'content' => 'Discussed cohort timing. Client prefers weekend batch starting next month.',
        ]);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $this->admin->id,
            'content' => 'Discussed cohort timing. Client prefers weekend batch starting next month.',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'note_added',
        ]);
    }

    public function test_admin_can_mark_follow_up_completed(): void
    {
        $lead = Lead::create([
            'name' => 'Pooja Bhatt',
            'email' => 'pooja@bhatt.com',
            'status' => LeadStatus::FOLLOW_UP->value,
            'next_follow_up_at' => now()->addHours(3),
            'follow_up_status' => 'call_scheduled',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.leads.follow-up.complete', $lead));

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('success');

        $fresh = $lead->fresh();
        $this->assertEquals('completed', $fresh->follow_up_status);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'follow_up_completed',
        ]);
    }

    public function test_admin_can_convert_lead_when_matching_user_exists(): void
    {
        $existingStudent = User::factory()->create([
            'name' => 'Kavita Roy',
            'email' => 'kavita@royenterprises.com',
            'role' => UserRole::STUDENT,
        ]);

        $lead = Lead::create([
            'name' => 'Kavita Roy',
            'email' => 'kavita@royenterprises.com',
            'status' => LeadStatus::QUALIFIED->value,
            'course_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leads.convert', $lead));

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('success');

        $fresh = $lead->fresh();
        $this->assertEquals(LeadStatus::CONVERTED, $fresh->status);
        $this->assertEquals($existingStudent->id, $fresh->converted_user_id);
        $this->assertNotNull($fresh->converted_at);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'converted',
        ]);
    }

    public function test_admin_can_convert_lead_when_user_does_not_exist(): void
    {
        $lead = Lead::create([
            'name' => 'Gaurav Jain',
            'email' => 'gaurav@jainlogistics.com',
            'phone' => '+91 91234 56789',
            'status' => LeadStatus::INTERESTED->value,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leads.convert', $lead), [
            'password' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('success');

        // New User was created
        $newUser = User::where('email', 'gaurav@jainlogistics.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Gaurav Jain', $newUser->name);
        $this->assertEquals(UserRole::STUDENT, $newUser->role);
        $this->assertTrue(Hash::check('SecurePass123!', $newUser->password));

        // Lead linked and converted
        $fresh = $lead->fresh();
        $this->assertEquals(LeadStatus::CONVERTED, $fresh->status);
        $this->assertEquals($newUser->id, $fresh->converted_user_id);
    }

    public function test_cannot_convert_already_converted_lead(): void
    {
        $lead = Lead::create([
            'name' => 'Converted Lead',
            'email' => 'converted@test.com',
            'status' => LeadStatus::CONVERTED->value,
            'converted_at' => now(),
            'converted_user_id' => $this->student->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leads.convert', $lead));

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('warning');
    }

    public function test_auto_convert_leads_on_user_registration(): void
    {
        $lead = Lead::create([
            'name' => 'Self Registering Lead',
            'email' => 'selfreg@prospect.com',
            'status' => LeadStatus::INTERESTED->value,
            'priority' => LeadPriority::HIGH->value,
        ]);

        $this->assertFalse($lead->isConverted());

        // Student registers account
        $response = $this->post(route('register'), [
            'name' => 'Self Registering Lead',
            'email' => 'selfreg@prospect.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect();

        $fresh = $lead->fresh();
        $this->assertTrue($fresh->isConverted());
        $this->assertEquals(LeadStatus::CONVERTED, $fresh->status);
        $this->assertNotNull($fresh->converted_at);

        $registeredUser = User::where('email', 'selfreg@prospect.com')->first();
        $this->assertEquals($registeredUser->id, $fresh->converted_user_id);
    }

    public function test_filter_leads_by_priority_and_assignment(): void
    {
        Lead::create([
            'name' => 'Urgent Lead',
            'email' => 'urgent@lead.com',
            'priority' => LeadPriority::URGENT->value,
            'status' => LeadStatus::NEW->value,
            'assigned_to' => $this->admin->id,
        ]);

        Lead::create([
            'name' => 'Low Priority Lead',
            'email' => 'low@lead.com',
            'priority' => LeadPriority::LOW->value,
            'status' => LeadStatus::CONTACTED->value,
            'assigned_to' => $this->staffAdmin->id,
        ]);

        // Filter priority=urgent
        $respUrgent = $this->actingAs($this->admin)->get(route('admin.leads.index', ['priority' => 'urgent']));
        $respUrgent->assertOk();
        $respUrgent->assertSee('Urgent Lead');
        $respUrgent->assertDontSee('Low Priority Lead');

        // Filter assigned_to=me (current admin)
        $respMe = $this->actingAs($this->admin)->get(route('admin.leads.index', ['assigned_to' => 'me']));
        $respMe->assertOk();
        $respMe->assertSee('Urgent Lead');
        $respMe->assertDontSee('Low Priority Lead');
    }

    public function test_filter_leads_by_follow_up_dates(): void
    {
        $dueToday = Lead::create([
            'name' => 'Due Today Lead',
            'email' => 'today@lead.com',
            'status' => LeadStatus::FOLLOW_UP->value,
            'next_follow_up_at' => now()->endOfDay()->subHour(),
        ]);

        $overdue = Lead::create([
            'name' => 'Overdue Lead',
            'email' => 'overdue@lead.com',
            'status' => LeadStatus::FOLLOW_UP->value,
            'next_follow_up_at' => now()->subDays(2),
        ]);

        // Filter follow_up=today
        $respToday = $this->actingAs($this->admin)->get(route('admin.leads.index', ['follow_up' => 'today']));
        $respToday->assertOk();
        $respToday->assertSee('Due Today Lead');
        $respToday->assertDontSee('Overdue Lead');

        // Filter follow_up=overdue
        $respOverdue = $this->actingAs($this->admin)->get(route('admin.leads.index', ['follow_up' => 'overdue']));
        $respOverdue->assertOk();
        $respOverdue->assertSee('Overdue Lead');
        $respOverdue->assertDontSee('Due Today Lead');
    }

    public function test_admin_dashboard_includes_crm_metrics(): void
    {
        Lead::create([
            'name' => 'CRM Metric Lead',
            'email' => 'metric@crm.com',
            'status' => LeadStatus::NEW->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Inbound Leads &amp; Mini CRM Overview', false);
        $response->assertSee('CRM Metric Lead');
        $response->assertSee('Total Leads');
        $response->assertSee('New / Uncontacted');
    }
}
