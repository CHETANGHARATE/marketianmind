<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminCourseAccessService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdminCourseAccessAndRenewalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Jane Student',
            'email' => 'jane@learner.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Growth Marketing',
            'slug' => 'growth-marketing',
        ]);

        $this->course = Course::create([
            'title' => 'Advanced Growth Engineering',
            'slug' => 'advanced-growth-engineering',
            'short_description' => 'Master growth engineering strategies.',
            'course_category_id' => $this->category->id,
            'price' => 4999.00,
            'access_validity_days' => 365,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test A: Guest and Student cannot access admin enrollment endpoints.
     */
    public function test_guest_and_student_are_unauthorized_for_admin_enrollment_routes(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
            'enrolled_at' => now(),
        ]);

        // Guests
        $this->get(route('admin.enrollments.index'))->assertRedirect(route('login'));
        $this->get(route('admin.enrollments.show', $enrollment))->assertRedirect(route('login'));
        $this->post(route('admin.enrollments.extend-access', $enrollment), [
            'days' => 30,
            'reason' => 'Test reason for guest',
        ])->assertRedirect(route('login'));

        // Students
        $this->actingAs($this->student)
            ->get(route('admin.enrollments.index'))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('admin.enrollments.show', $enrollment))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->post(route('admin.enrollments.extend-access', $enrollment), [
                'days' => 30,
                'reason' => 'Test reason for student',
            ])->assertForbidden();
    }

    /**
     * Test B: Admin can view enrollment index with all filter tabs.
     */
    public function test_admin_can_view_enrollment_index_with_filter_tabs(): void
    {
        $now = now();

        // 1. Active enrollment
        $activeEnr = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now->copy()->subDays(10),
            'expires_at' => $now->copy()->addDays(355),
            'enrolled_at' => $now->copy()->subDays(10),
        ]);

        // 2. Expiring soon enrollment
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $expiringEnr = Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now->copy()->subDays(350),
            'expires_at' => $now->copy()->addDays(15),
            'enrolled_at' => $now->copy()->subDays(350),
        ]);

        // 3. Expired enrollment
        $student3 = User::factory()->create(['role' => UserRole::STUDENT]);
        $expiredEnr = Enrollment::create([
            'user_id' => $student3->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => $now->copy()->subDays(400),
            'expires_at' => $now->copy()->subDays(35),
            'enrolled_at' => $now->copy()->subDays(400),
        ]);

        // 4. Legacy lifetime enrollment
        $student4 = User::factory()->create(['role' => UserRole::STUDENT]);
        $lifetimeEnr = Enrollment::create([
            'user_id' => $student4->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => $now->copy()->subYears(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.index'));
        $response->assertOk();
        $response->assertSee('Course Access & Renewals', false);
        $response->assertSee('Active Access');
        $response->assertSee('Expiring Soon');
        $response->assertSee('Expired');
        $response->assertSee('Legacy Lifetime');

        // Test filtering by active
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertDontSee($student3->name);

        // Test filtering by expiring
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['status' => 'expiring']))
            ->assertOk()
            ->assertSee($student2->name)
            ->assertDontSee($student3->name);

        // Test filtering by expired
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSee($student3->name)
            ->assertDontSee($student4->name);

        // Test filtering by lifetime
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['status' => 'lifetime']))
            ->assertOk()
            ->assertSee($student4->name)
            ->assertDontSee($this->student->name);
    }

    /**
     * Test C: Search functionality across student name, email, and course title.
     */
    public function test_admin_can_search_enrollments_by_student_or_course(): void
    {
        $otherCourse = Course::create([
            'title' => 'Python Analytics Mastery',
            'slug' => 'python-analytics-mastery',
            'short_description' => 'Master data analytics with Python.',
            'course_category_id' => $this->category->id,
            'price' => 2999.00,
            'access_validity_days' => 365,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $studentAlice = User::factory()->create([
            'name' => 'Alice UniqueName',
            'email' => 'alice.unique@somedomain.org',
            'role' => UserRole::STUDENT,
        ]);

        $enr1 = Enrollment::create([
            'user_id' => $studentAlice->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
            'enrolled_at' => now(),
        ]);

        $enr2 = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $otherCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
            'enrolled_at' => now(),
        ]);

        // Search by student name
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['search' => 'Alice UniqueName']))
            ->assertOk()
            ->assertSee('Alice UniqueName')
            ->assertDontSee($this->student->email);

        // Search by student email
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['search' => 'alice.unique@somedomain.org']))
            ->assertOk()
            ->assertSee('Alice UniqueName')
            ->assertDontSee($this->student->email);

        // Search by course title
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['search' => 'Python Analytics Mastery']))
            ->assertOk()
            ->assertSee($this->student->email)
            ->assertDontSee('alice.unique@somedomain.org');
    }

    /**
     * Test D: Sorting by expiring soonest.
     */
    public function test_admin_can_sort_enrollments_by_expiring_soonest(): void
    {
        $now = now();

        $enrLater = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(200),
            'enrolled_at' => $now,
        ]);

        $studentSoon = User::factory()->create(['role' => UserRole::STUDENT]);
        $enrSoon = Enrollment::create([
            'user_id' => $studentSoon->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(5),
            'enrolled_at' => $now,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index', ['sort' => 'expiring_soon']));

        $response->assertOk();
        // enrSoon should appear before enrLater
        $content = $response->getContent();
        $posSoon = strpos($content, $studentSoon->name);
        $posLater = strpos($content, $this->student->name);
        $this->assertTrue($posSoon < $posLater, 'Expiring soonest enrollment must be listed first.');
    }

    /**
     * Test E: Enrollment show inspector displays full access lifecycle details, orders, and audit logs.
     */
    public function test_admin_can_inspect_enrollment_details_with_access_history_and_orders(): void
    {
        $now = now();
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now->copy()->subDays(60),
            'expires_at' => $now->copy()->addDays(305),
            'enrolled_at' => $now->copy()->subDays(60),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-998877',
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'subtotal' => 499900,
            'discount' => 0,
            'tax' => 0,
            'amount' => 499900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(60),
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(60),
            'expires_at' => $now->copy()->addDays(305),
        ]);

        $auditLog = AuditLog::create([
            'user_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'action' => 'updated',
            'auditable_type' => 'Enrollment',
            'auditable_id' => $enrollment->id,
            'resource_label' => "Enrollment #{$enrollment->id}",
            'description' => 'Initial test log entry',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'active'],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.enrollments.show', $enrollment));
        $response->assertOk();
        $response->assertSee("Enrollment Record #{$enrollment->id}");
        $response->assertSee('ORD-TEST-998877');
        $response->assertSee('Access Period History');
        $response->assertSee('Initial test log entry');
        $response->assertSee('Grant / Extend Access');
    }

    /**
     * Test F: AdminCourseAccessService successfully grants access to an expired enrollment.
     */
    public function test_admin_can_grant_access_to_an_expired_enrollment(): void
    {
        $past = now()->subDays(400);
        $expiredAt = now()->subDays(35);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => $past,
            'expires_at' => $expiredAt,
            'enrolled_at' => $past,
        ]);

        $service = app(AdminCourseAccessService::class);
        $accessPeriod = $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 365,
            reason: 'Student requested reactivation following extended sick leave.',
            admin: $this->admin
        );

        $enrollment->refresh();

        // Must update status to active
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        $this->assertTrue($enrollment->hasActiveAccess());

        // Must have created an access period
        $this->assertEquals('admin_grant', $accessPeriod->period_type);
        $this->assertNull($accessPeriod->order_id);
        $this->assertTrue($accessPeriod->starts_at->isToday());
        $this->assertTrue($accessPeriod->expires_at->isAfter(now()->addDays(360)));

        // Enrollment dates updated in sync
        $this->assertEquals($accessPeriod->expires_at->toDateTimeString(), $enrollment->expires_at->toDateTimeString());

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'Enrollment',
            'auditable_id' => $enrollment->id,
            'action' => 'updated',
        ]);
    }

    /**
     * Test G: AdminCourseAccessService seamlessly extends an active unexpired enrollment (early extension).
     */
    public function test_admin_can_extend_active_unexpired_enrollment_from_current_expiry(): void
    {
        $now = now();
        $originalStartsAt = $now->copy()->subDays(100);
        $originalExpiresAt = $now->copy()->addDays(265);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $originalStartsAt,
            'expires_at' => $originalExpiresAt,
            'enrolled_at' => $originalStartsAt,
        ]);

        $service = app(AdminCourseAccessService::class);
        $accessPeriod = $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 90,
            reason: 'Courtesy bonus extension for top community performer.',
            admin: $this->admin
        );

        $enrollment->refresh();

        // New access period starts when current access was set to expire
        $this->assertEquals($originalExpiresAt->toDateTimeString(), $accessPeriod->starts_at->toDateTimeString());
        $expectedNewExpiry = $originalExpiresAt->copy()->addDays(90);
        $this->assertEquals($expectedNewExpiry->toDateTimeString(), $accessPeriod->expires_at->toDateTimeString());

        // Enrollment starts_at is preserved
        $this->assertEquals($originalStartsAt->toDateTimeString(), $enrollment->starts_at->toDateTimeString());
        // Enrollment expires_at is extended
        $this->assertEquals($expectedNewExpiry->toDateTimeString(), $enrollment->expires_at->toDateTimeString());
        $this->assertEquals('admin_grant', $accessPeriod->period_type);
    }

    /**
     * Test H: Completed status and certificates are strictly preserved on access grant.
     */
    public function test_granting_access_preserves_completed_status_and_certificate(): void
    {
        $past = now()->subDays(500);
        $completedAt = now()->subDays(200);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => $past,
            'expires_at' => now()->subDays(10), // Expired
            'enrolled_at' => $past,
            'completed_at' => $completedAt,
        ]);

        $cert = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'certificate_number' => 'CERT-GROWTH-777',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => $completedAt,
            'issued_at' => $completedAt,
        ]);

        $service = app(AdminCourseAccessService::class);
        $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 180,
            reason: 'Alumni refresher access granted for updated modules.',
            admin: $this->admin
        );

        $enrollment->refresh();

        // Status remains COMPLETED, completed_at preserved
        $this->assertEquals(EnrollmentStatus::COMPLETED, $enrollment->status);
        $this->assertEquals($completedAt->toDateTimeString(), $enrollment->completed_at->toDateTimeString());
        // Certificate remains linked and intact
        $this->assertNotNull($enrollment->certificate);
        $this->assertEquals('CERT-GROWTH-777', $enrollment->certificate->certificate_number);
    }

    /**
     * Test I: Legacy lifetime enrollments cannot be altered to finite access.
     */
    public function test_legacy_lifetime_enrollment_is_strictly_protected_from_modification(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => now()->subYears(3),
        ]);

        $this->assertTrue($enrollment->isLegacyLifetimeAccess());

        $service = app(AdminCourseAccessService::class);

        $this->expectException(DomainException::class);
        $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 365,
            reason: 'Accidental attempt to modify lifetime enrollment',
            admin: $this->admin
        );

        // Ensure no periods were created and dates remain null
        $enrollment->refresh();
        $this->assertNull($enrollment->starts_at);
        $this->assertNull($enrollment->expires_at);
        $this->assertEquals(0, CourseAccessPeriod::where('enrollment_id', $enrollment->id)->count());
    }

    /**
     * Test J: POST extend-access endpoint enforces validation and legacy protection.
     */
    public function test_post_extend_access_endpoint_validates_inputs_and_protects_lifetime(): void
    {
        $now = now();
        $finiteEnr = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(30),
            'enrolled_at' => $now,
        ]);

        $lifetimeStudent = User::factory()->create(['role' => UserRole::STUDENT]);
        $lifetimeEnr = Enrollment::create([
            'user_id' => $lifetimeStudent->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => $now->subYears(1),
        ]);

        // 1. Validation failure: days <= 0
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.extend-access', $finiteEnr), [
                'days' => 0,
                'reason' => 'Valid justification reason',
            ])
            ->assertSessionHasErrors(['days']);

        // 2. Validation failure: reason too short
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.extend-access', $finiteEnr), [
                'days' => 30,
                'reason' => 'abc',
            ])
            ->assertSessionHasErrors(['reason']);

        // 3. Attempting to extend lifetime enrollment through HTTP POST returns error flash
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.extend-access', $lifetimeEnr), [
                'days' => 365,
                'reason' => 'Attempting to change lifetime user',
            ])
            ->assertSessionHas('error');

        // Verify lifetime enrollment was not modified
        $lifetimeEnr->refresh();
        $this->assertNull($lifetimeEnr->starts_at);
        $this->assertNull($lifetimeEnr->expires_at);

        // 4. Valid extension succeeds and redirects with success
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.extend-access', $finiteEnr), [
                'days' => 60,
                'reason' => 'Extension authorized by admin support.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $finiteEnr))
            ->assertSessionHas('success');

        $finiteEnr->refresh();
        $this->assertTrue($finiteEnr->expires_at->isAfter($now->copy()->addDays(80)));
    }

    /**
     * Test K: Idempotency double-click protection.
     */
    public function test_rapid_subsequent_grants_do_not_duplicate_periods(): void
    {
        $now = now();
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(30),
            'enrolled_at' => $now,
        ]);

        $service = app(AdminCourseAccessService::class);

        $period1 = $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 30,
            reason: 'Double click test reason',
            admin: $this->admin
        );

        $period2 = $service->grantOrExtendAccess(
            enrollment: $enrollment,
            days: 30,
            reason: 'Double click test reason',
            admin: $this->admin
        );

        // Second grant returns existing period within the 10-second double-click window
        $this->assertEquals($period1->id, $period2->id);
        $this->assertEquals(1, CourseAccessPeriod::where('enrollment_id', $enrollment->id)->count());
    }

    /**
     * Test L: Student show page displays access badge and manage access link.
     */
    public function test_student_show_page_displays_access_status_and_manage_link(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $this->student));
        $response->assertOk();
        $response->assertSee('Access Lifecycle');
        $response->assertSee('Manage Access');
        $response->assertSee(route('admin.enrollments.show', $enrollment));
    }
}
