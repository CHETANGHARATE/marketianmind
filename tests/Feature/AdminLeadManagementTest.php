<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Accelerator',
            'slug' => 'digital-marketing-accelerator',
            'short_description' => 'High impact conversion training.',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_admin_leads(): void
    {
        $response = $this->get(route('admin.leads.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_leads(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.leads.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_leads_index_with_statistics(): void
    {
        Lead::create([
            'name' => 'Karan Johar',
            'email' => 'karan@dharma.com',
            'status' => LeadStatus::NEW->value,
            'message' => 'Looking for film marketing training.',
        ]);

        Lead::create([
            'name' => 'Meera Nair',
            'email' => 'meera@nairfilms.com',
            'status' => LeadStatus::CONTACTED->value,
            'message' => 'Spoke on phone yesterday.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));

        $response->assertOk();
        $response->assertSee('Leads &amp; Inquiries', false);
        $response->assertSee('Karan Johar');
        $response->assertSee('karan@dharma.com');
        $response->assertSee('Meera Nair');
        $response->assertSee('Total Inquiries');
    }

    public function test_admin_can_filter_leads_by_status_and_course(): void
    {
        $lead1 = Lead::create([
            'name' => 'New Lead',
            'email' => 'new@lead.com',
            'status' => LeadStatus::NEW->value,
            'course_id' => $this->course->id,
            'message' => 'New inquiry.',
        ]);

        $lead2 = Lead::create([
            'name' => 'Closed Lead',
            'email' => 'closed@lead.com',
            'status' => LeadStatus::CLOSED->value,
            'message' => 'Closed inquiry.',
        ]);

        // Filter status=new
        $response = $this->actingAs($this->admin)->get(route('admin.leads.index', ['status' => 'new']));
        $response->assertOk();
        $response->assertSee('New Lead');
        $response->assertDontSee('Closed Lead');

        // Filter course_id
        $response2 = $this->actingAs($this->admin)->get(route('admin.leads.index', ['course_id' => $this->course->id]));
        $response2->assertOk();
        $response2->assertSee('New Lead');
    }

    public function test_admin_can_search_leads(): void
    {
        Lead::create([
            'name' => 'Specific Target Lead',
            'email' => 'specific@target.com',
            'message' => 'Unique query string.',
        ]);

        Lead::create([
            'name' => 'Random Other Person',
            'email' => 'other@random.com',
            'message' => 'Unrelated question.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.index', ['search' => 'Specific Target']));

        $response->assertOk();
        $response->assertSee('Specific Target Lead');
        $response->assertDontSee('Random Other Person');
    }

    public function test_admin_can_view_lead_details(): void
    {
        $lead = Lead::create([
            'name' => 'Detailed Inquirer',
            'email' => 'detailed@inquirer.com',
            'phone' => '+91 9988776655',
            'course_id' => $this->course->id,
            'subject' => 'Curriculum Query',
            'message' => 'We need custom training for 15 sales managers.',
            'status' => LeadStatus::NEW->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Inquiry from Detailed Inquirer');
        $response->assertSee('detailed@inquirer.com');
        $response->assertSee('+91 9988776655');
        $response->assertSee('We need custom training for 15 sales managers.');
        $response->assertSee('Digital Marketing Accelerator');
    }

    public function test_admin_can_update_lead_status_and_internal_notes(): void
    {
        $lead = Lead::create([
            'name' => 'Status Change Lead',
            'email' => 'status@change.com',
            'status' => LeadStatus::NEW->value,
            'message' => 'Call me tomorrow morning.',
        ]);

        $data = [
            'status' => LeadStatus::CONTACTED->value,
            'notes' => 'Spoke with founder. Sending invoice link.',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.leads.update', $lead), $data);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::CONTACTED->value,
            'notes' => 'Spoke with founder. Sending invoice link.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => 'Lead',
        ]);
    }

    public function test_admin_can_delete_lead_and_audit_log_is_created(): void
    {
        $lead = Lead::create([
            'name' => 'Delete This Lead',
            'email' => 'delete@lead.com',
            'message' => 'Test message.',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.leads.destroy', $lead));

        $response->assertRedirect(route('admin.leads.index'));
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => 'Lead',
        ]);
    }
}