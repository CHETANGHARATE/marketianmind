<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student1;
    protected User $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student1 = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->student2 = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);
    }

    public function test_guest_and_student_cannot_access_announcements(): void
    {
        $this->get(route('admin.announcements.index'))->assertRedirect(route('login'));

        $this->actingAs($this->student1)
            ->get(route('admin.announcements.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_announcements_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.announcements.index'));

        $response->assertOk();
        $response->assertSee('Announcements');
        $response->assertSee('Create Announcement');
    }

    public function test_admin_can_broadcast_announcement_to_all_students(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'Important Maintenance Notice',
            'message' => 'Platform upgrades scheduled this Sunday midnight.',
            'action_url' => 'https://example.com/updates',
            'target' => 'all',
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $response->assertSessionHas('status');

        $this->assertEquals(1, $this->student1->notifications()->count());
        $this->assertEquals(1, $this->student2->notifications()->count());

        $notification = $this->student1->notifications()->first();
        $this->assertEquals('announcement', $notification->data['type']);
        $this->assertEquals('Important Maintenance Notice', $notification->data['title']);
        $this->assertEquals('https://example.com/updates', $notification->data['action_url']);

        // Check Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'announcement.broadcast',
        ]);
    }

    public function test_admin_can_send_targeted_announcement_to_specific_student(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'Exclusive Invite: Mastermind Session',
            'message' => 'You have been selected for this private session.',
            'target' => 'specific',
            'user_id' => $this->student1->id,
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $this->assertEquals(1, $this->student1->notifications()->count());
        $this->assertEquals(0, $this->student2->notifications()->count());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'announcement.targeted',
        ]);
    }
}