<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Auditor Admin',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Regular Student',
            'email' => 'student@marketianmind.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Marketing Automation',
            'slug' => 'marketing-automation',
        ]);
    }

    private function createCourse(array $attributes = []): Course
    {
        $title = $attributes['title'] ?? 'Course ' . Str::random(6);
        return Course::create(array_merge([
            'course_category_id' => $this->category->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'short_description' => 'Test course description',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ], $attributes));
    }

    // -------------------------------------------------------------
    // Authorization & Access Control
    // -------------------------------------------------------------

    public function test_guest_is_redirected_to_login_when_accessing_audit_logs_index(): void
    {
        $response = $this->get(route('admin.audit_logs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_receives_forbidden_when_accessing_audit_logs_index(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.audit_logs.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_audit_logs_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.index'));
        $response->assertOk();
        $response->assertViewIs('admin.audit-logs.index');
        $response->assertSee('Audit Logs');
        $response->assertSee('Administrative Activity');
    }

    public function test_guest_is_redirected_to_login_when_accessing_audit_log_show(): void
    {
        $log = AuditLogger::log('created', 'Course', 'Sample audit log description');
        $response = $this->get(route('admin.audit_logs.show', $log));
        $response->assertRedirect(route('login'));
    }

    public function test_student_receives_forbidden_when_accessing_audit_log_show(): void
    {
        $log = AuditLogger::log('created', 'Course', 'Sample audit log description');
        $response = $this->actingAs($this->student)->get(route('admin.audit_logs.show', $log));
        $response->assertForbidden();
    }

    public function test_admin_can_view_audit_log_show(): void
    {
        $course = $this->createCourse(['title' => 'Cold Email Mastery']);
        $log = AuditLogger::log(
            action: 'created',
            auditable: $course,
            description: "Created course: {$course->title}",
            oldValues: null,
            newValues: ['title' => $course->title, 'price' => 1999.00],
            actor: $this->admin
        );

        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.show', $log));
        $response->assertOk();
        $response->assertViewIs('admin.audit-logs.show');
        $response->assertSee('Cold Email Mastery');
        $response->assertSee('Auditor Admin');
        $response->assertSee('Audit Information');
    }

    public function test_nonexistent_audit_log_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs/999999');
        $response->assertNotFound();
    }

    public function test_audit_logs_are_read_only_and_have_no_modification_routes(): void
    {
        $log = AuditLogger::log('created', 'Course', 'Immutable record');

        $this->actingAs($this->admin)->put("/admin/audit-logs/{$log->id}", [])->assertStatus(405);
        $this->actingAs($this->admin)->delete("/admin/audit-logs/{$log->id}")->assertStatus(405);
        $this->actingAs($this->admin)->get("/admin/audit-logs/{$log->id}/edit")->assertStatus(404);
    }

    // -------------------------------------------------------------
    // Audit Action Recording & Lifecycle Integration
    // -------------------------------------------------------------

    public function test_creating_a_course_records_an_audit_log(): void
    {
        $payload = [
            'course_category_id' => $this->category->id,
            'title' => 'Organic Traffic System',
            'slug' => 'organic-traffic-system',
            'short_description' => 'A comprehensive guide to free organic traffic.',
            'description' => 'Full long description of organic traffic system.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::DRAFT->value,
            'featured' => false,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), $payload);
        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'created',
            'auditable_type' => 'Course',
            'resource_label' => 'Organic Traffic System',
            'description' => 'Created course: Organic Traffic System',
        ]);
    }

    public function test_updating_a_course_records_old_and_new_values(): void
    {
        $course = $this->createCourse([
            'title' => 'Original Course Title',
            'price' => 1500.00,
            'status' => CourseStatus::DRAFT,
        ]);

        $updatePayload = [
            'course_category_id' => $this->category->id,
            'title' => 'Updated Course Title',
            'slug' => $course->slug,
            'short_description' => $course->short_description,
            'price' => 3500.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED->value,
            'featured' => true,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.courses.update', $course), $updatePayload);
        $response->assertRedirect(route('admin.courses.index'));

        $log = AuditLog::where('auditable_type', 'Course')
            ->where('auditable_id', $course->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('published', $log->action);
        $this->assertSame('Updated Course Title', $log->resource_label);
        $this->assertSame('Original Course Title', $log->old_values['title']);
        $this->assertSame('Updated Course Title', $log->new_values['title']);
    }

    public function test_deleting_a_course_records_deletion_audit_log_with_snapshot(): void
    {
        $course = $this->createCourse(['title' => 'Course To Be Deleted']);
        $courseId = $course->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course));
        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'deleted',
            'auditable_type' => 'Course',
            'resource_label' => 'Course To Be Deleted',
            'description' => 'Deleted course: Course To Be Deleted',
        ]);

        $log = AuditLog::where('action', 'deleted')
            ->where('resource_label', 'Course To Be Deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Course To Be Deleted', $log->old_values['title']);
    }

    public function test_category_crud_operations_record_audit_logs(): void
    {
        // 1. Create Category
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Search Engine Optimization',
            'description' => 'All SEO courses',
            'status' => 'active',
        ]);
        $response->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => 'CourseCategory',
            'resource_label' => 'Search Engine Optimization',
        ]);

        $cat = CourseCategory::where('name', 'Search Engine Optimization')->first();

        // 2. Update Category
        $this->actingAs($this->admin)->put(route('admin.categories.update', $cat), [
            'name' => 'Advanced SEO Strategies',
            'description' => 'Technical and on-page SEO',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => 'CourseCategory',
            'resource_label' => 'Advanced SEO Strategies',
        ]);

        // 3. Delete Category
        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $cat));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => 'CourseCategory',
            'resource_label' => 'Advanced SEO Strategies',
        ]);
    }

    // -------------------------------------------------------------
    // Sensitive Data Protection
    // -------------------------------------------------------------

    public function test_sensitive_fields_are_never_persisted_in_audit_logs(): void
    {
        $dirtyPayload = [
            'title' => 'Safe Course Title',
            'password' => 'SuperSecret123!',
            'password_confirmation' => 'SuperSecret123!',
            'remember_token' => 'random_token_val',
            'api_key' => 'live_ak_9999999',
            'razorpay_secret' => 'rzp_sec_xyz',
            'card_number' => '4111111111111111',
            'cvv' => '123',
        ];

        $log = AuditLogger::log(
            action: 'updated',
            auditable: 'Course',
            description: 'Testing sensitive sanitization',
            oldValues: $dirtyPayload,
            newValues: $dirtyPayload,
            actor: $this->admin
        );

        $savedLog = AuditLog::find($log->id);

        $this->assertArrayNotHasKey('password', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('password_confirmation', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('api_key', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('razorpay_secret', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('card_number', $savedLog->old_values ?? []);
        $this->assertArrayNotHasKey('cvv', $savedLog->old_values ?? []);

        $this->assertArrayHasKey('title', $savedLog->old_values ?? []);
        $this->assertSame('Safe Course Title', $savedLog->old_values['title']);
    }

    // -------------------------------------------------------------
    // Transaction Safety
    // -------------------------------------------------------------

    public function test_failed_transactions_do_not_leave_false_audit_records(): void
    {
        $initialCount = AuditLog::count();

        try {
            DB::transaction(function () {
                $course = $this->createCourse(['title' => 'Rollback Course']);
                AuditLogger::log('created', $course, 'This should roll back');
                throw new \RuntimeException('Database failure simulation');
            });
        } catch (\RuntimeException $e) {
            // Expected simulation
        }

        $this->assertSame($initialCount, AuditLog::count());
        $this->assertDatabaseMissing('audit_logs', [
            'description' => 'This should roll back',
        ]);
    }

    // -------------------------------------------------------------
    // Search, Filters & Sorting
    // -------------------------------------------------------------

    public function test_audit_logs_can_be_searched_by_keyword(): void
    {
        AuditLogger::log('created', 'Course', 'Created course: Unique Alpha Search Key');
        AuditLogger::log('created', 'Course', 'Created course: Beta Different Key');

        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['search' => 'Unique Alpha']));
        $response->assertOk();
        $response->assertSee('Unique Alpha Search Key');
        $response->assertDontSee('Beta Different Key');
    }

    public function test_audit_logs_can_be_filtered_by_action_and_resource(): void
    {
        AuditLogger::log('created', 'Course', 'Created Course Record');
        AuditLogger::log('deleted', 'CourseCategory', 'Deleted Category Record');

        // Filter by action = deleted
        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['action' => 'deleted']));
        $response->assertOk();
        $response->assertSee('Deleted Category Record');
        $response->assertDontSee('Created Course Record');

        // Filter by resource = Course
        $resResponse = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['resource' => 'Course']));
        $resResponse->assertOk();
        $resResponse->assertSee('Created Course Record');
        $resResponse->assertDontSee('Deleted Category Record');
    }

    public function test_audit_logs_can_be_sorted_by_date(): void
    {
        $firstLog = AuditLogger::log('created', 'Course', 'First Created Event');
        $firstLog->update(['created_at' => now()->subDays(5)]);

        $secondLog = AuditLogger::log('created', 'Course', 'Second Created Event');
        $secondLog->update(['created_at' => now()->subDay()]);

        // Default: newest first
        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['sort' => 'newest']));
        $response->assertOk();
        $response->assertSeeInOrder(['Second Created Event', 'First Created Event']);

        // Oldest first
        $oldestResponse = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['sort' => 'oldest']));
        $oldestResponse->assertOk();
        $oldestResponse->assertSeeInOrder(['First Created Event', 'Second Created Event']);
    }

    public function test_empty_state_renders_when_no_records_match_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit_logs.index', ['search' => 'NonexistentSearchPhrase999']));
        $response->assertOk();
        $response->assertSee('No audit records match your current search or filters.');
    }
}