<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;
    protected Enrollment $enrollmentA;
    protected Enrollment $enrollmentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Certificate Admin',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->studentA = User::factory()->create([
            'name' => 'Sophia Scholar',
            'email' => 'sophia@scholar.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->studentB = User::factory()->create([
            'name' => 'Marcus Founder',
            'email' => 'marcus@founder.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Growth Hacking',
            'slug' => 'growth-hacking',
        ]);

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Viral Funnels for Startups',
            'slug' => 'viral-funnels-startups',
            'short_description' => 'Build high-converting organic funnels.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Direct Response Copywriting',
            'slug' => 'direct-response-copywriting',
            'short_description' => 'Write copy that sells predictably.',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->enrollmentA = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(10),
            'completed_at' => now()->subDays(2),
        ]);

        $this->enrollmentB = Enrollment::create([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(15),
            'completed_at' => now()->subDays(5),
        ]);
    }

    /**
     * Helper to create a test certificate with snapshot fields.
     */
    protected function createCertificate(array $overrides = []): Certificate
    {
        return Certificate::create(array_merge([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'enrollment_id' => $this->enrollmentA->id,
            'certificate_number' => 'MM-2026-TESTCERT1',
            'course_title' => 'Viral Funnels for Startups (Snapshot Title)',
            'student_name' => 'Sophia Scholar (Snapshot Name)',
            'instructor_name' => 'Dr. Marketing Faculty',
            'course_completion_date' => now()->subDays(2),
            'issued_at' => now()->subDay(),
            'metadata' => [
                'total_lessons' => 12,
                'duration' => '4 hours',
            ],
        ], $overrides));
    }

    /**
     * Test 1: Guest cannot access admin certificate index or show routes.
     */
    public function test_guest_is_redirected_from_admin_certificate_routes(): void
    {
        $cert = $this->createCertificate();

        $responseIndex = $this->get(route('admin.certificates.index'));
        $responseIndex->assertRedirect(route('login'));

        $responseShow = $this->get(route('admin.certificates.show', $cert));
        $responseShow->assertRedirect(route('login'));
    }

    /**
     * Test 2: Student receives 403 Forbidden on admin certificate routes.
     */
    public function test_student_cannot_access_admin_certificate_routes(): void
    {
        $cert = $this->createCertificate();

        $responseIndex = $this->actingAs($this->studentA)->get(route('admin.certificates.index'));
        $responseIndex->assertForbidden();

        $responseShow = $this->actingAs($this->studentA)->get(route('admin.certificates.show', $cert));
        $responseShow->assertForbidden();
    }

    /**
     * Test 3: Authorized admin can view certificate index page.
     */
    public function test_authorized_admin_can_view_certificate_index(): void
    {
        $this->createCertificate();

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index'));
        $response->assertOk();
        $response->assertSeeText('Issued Certificates');
        $response->assertSee('MM-2026-TESTCERT1');
        $response->assertSee('Sophia Scholar (Snapshot Name)');
        $response->assertSee('Viral Funnels for Startups (Snapshot Title)');
    }

    /**
     * Test 4: Authorized admin can view certificate show page with full snapshot details.
     */
    public function test_authorized_admin_can_view_certificate_details(): void
    {
        $cert = $this->createCertificate();

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.show', $cert));
        $response->assertOk();
        $response->assertSee('MM-2026-TESTCERT1');
        $response->assertSee('Sophia Scholar (Snapshot Name)');
        $response->assertSee('Viral Funnels for Startups (Snapshot Title)');
        $response->assertSee('Dr. Marketing Faculty');
        $response->assertSee('12 Lessons Completed');
        $response->assertSee('sophia@scholar.com');
        $response->assertSee(route('admin.students.show', $this->studentA));
        $response->assertSee(route('admin.courses.edit', $this->course1));
        $response->assertSee(route('admin.enrollments.show', $this->enrollmentA));
    }

    /**
     * Test 5: Search by certificate number works.
     */
    public function test_search_by_certificate_number_works(): void
    {
        $this->createCertificate([
            'certificate_number' => 'MM-2026-ALPHA111',
            'student_name' => 'Sophia Scholar',
        ]);

        $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-BETA222',
            'student_name' => 'Marcus Founder',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['search' => 'ALPHA111']));
        $response->assertOk();
        $response->assertSee('MM-2026-ALPHA111');
        $response->assertDontSee('MM-2026-BETA222');
    }

    /**
     * Test 6: Search by student name and email works.
     */
    public function test_search_by_student_name_and_email_works(): void
    {
        $this->createCertificate([
            'certificate_number' => 'MM-2026-CERT-A',
            'student_name' => 'Sophia Scholar',
        ]);

        $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-CERT-B',
            'student_name' => 'Marcus Founder',
        ]);

        // Search by name
        $responseName = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['search' => 'Marcus Founder']));
        $responseName->assertOk();
        $responseName->assertSee('MM-2026-CERT-B');
        $responseName->assertDontSee('MM-2026-CERT-A');

        // Search by email
        $responseEmail = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['search' => 'sophia@scholar.com']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('MM-2026-CERT-A');
        $responseEmail->assertDontSee('MM-2026-CERT-B');
    }

    /**
     * Test 7: Search by course title works.
     */
    public function test_search_by_course_title_works(): void
    {
        $this->createCertificate([
            'certificate_number' => 'MM-2026-COURSE-1',
            'course_title' => 'Viral Funnels for Startups',
        ]);

        $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-COURSE-2',
            'course_title' => 'Direct Response Copywriting',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['search' => 'Copywriting']));
        $response->assertOk();
        $response->assertSee('MM-2026-COURSE-2');
        $response->assertDontSee('MM-2026-COURSE-1');
    }

    /**
     * Test 8: Filter by course slug works.
     */
    public function test_filter_by_course_works(): void
    {
        $this->createCertificate([
            'certificate_number' => 'MM-2026-FLTR-1',
            'student_name' => 'Sophia Scholar',
        ]);

        $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-FLTR-2',
            'student_name' => 'Marcus Founder',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['course' => $this->course1->slug]));
        $response->assertOk();
        $response->assertSee('MM-2026-FLTR-1');
        $response->assertDontSee('MM-2026-FLTR-2');
        $this->assertCount(1, $response->viewData('certificates'));
    }

    /**
     * Test 9: Date filter on issued_at works.
     */
    public function test_date_filter_works(): void
    {
        $todayCert = $this->createCertificate([
            'certificate_number' => 'MM-2026-TODAY',
            'issued_at' => now(),
        ]);

        $pastCert = $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-PAST',
        ]);
        $pastCert->issued_at = now()->subMonths(3);
        $pastCert->save();

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['date' => 'today']));
        $response->assertOk();
        $response->assertSee('MM-2026-TODAY');
        $response->assertDontSee('MM-2026-PAST');
    }

    /**
     * Test 10: Sorting works and sanitizes malicious input.
     */
    public function test_sorting_works_and_sanitizes_input(): void
    {
        $this->createCertificate([
            'certificate_number' => 'MM-2026-SORT-A',
            'student_name' => 'Alice Zenith',
            'issued_at' => now()->subDays(5),
        ]);

        $this->createCertificate([
            'user_id' => $this->studentB->id,
            'course_id' => $this->course2->id,
            'enrollment_id' => $this->enrollmentB->id,
            'certificate_number' => 'MM-2026-SORT-B',
            'student_name' => 'Zara Alpha',
            'issued_at' => now(),
        ]);

        // Sort: student_asc
        $responseAsc = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['sort' => 'student_asc']));
        $responseAsc->assertOk();
        $this->assertEquals('MM-2026-SORT-A', $responseAsc->viewData('certificates')->first()->certificate_number);

        // Sort: student_desc
        $responseDesc = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['sort' => 'student_desc']));
        $responseDesc->assertOk();
        $this->assertEquals('MM-2026-SORT-B', $responseDesc->viewData('certificates')->first()->certificate_number);

        // SQL injection payload does not crash
        $responseMalicious = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['sort' => 'id; DROP TABLE certificates;--']));
        $responseMalicious->assertOk();
    }

    /**
     * Test 11: Pagination works and preserves query parameters.
     */
    public function test_pagination_works_and_preserves_query_parameters(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $user = User::factory()->create(['role' => UserRole::STUDENT]);
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $this->course1->id,
                'status' => EnrollmentStatus::COMPLETED,
            ]);

            Certificate::create([
                'user_id' => $user->id,
                'course_id' => $this->course1->id,
                'enrollment_id' => $enrollment->id,
                'certificate_number' => sprintf('MM-PAGE-%03d', $i),
                'course_title' => $this->course1->title,
                'student_name' => $user->name,
                'course_completion_date' => now(),
                'issued_at' => now(),
            ]);
        }

        $responsePage2 = $this->actingAs($this->admin)->get(route('admin.certificates.index', [
            'search' => 'MM-PAGE',
            'page' => 2,
        ]));
        $responsePage2->assertOk();
        $this->assertCount(5, $responsePage2->viewData('certificates'));
        $responsePage2->assertSee('search=MM-PAGE');
    }

    /**
     * Test 12: Historical certificate snapshot fields are preserved when user or course names change.
     */
    public function test_historical_certificate_snapshot_fields_are_preserved(): void
    {
        $cert = $this->createCertificate([
            'student_name' => 'Original Historical Name',
            'course_title' => 'Original Historical Course Title',
        ]);

        // User updates their profile name later
        $this->studentA->update(['name' => 'Brand New Name 2027']);

        // Course title updated in catalog
        $this->course1->update(['title' => 'Completely Renamed Course 2027']);

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.show', $cert));
        $response->assertOk();

        // Historical snapshot values must be preserved
        $response->assertSee('Original Historical Name');
        $response->assertSee('Original Historical Course Title');

        // Current catalog/profile context also displayed
        $response->assertSee('Brand New Name 2027');
        $response->assertSee('Completely Renamed Course 2027');
    }

    /**
     * Test 13: Missing certificate returns proper 404 response.
     */
    public function test_non_existent_certificate_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.certificates.show', 99999));
        $response->assertNotFound();
    }

    /**
     * Test 14: Certificate admin routes are strictly read-only.
     */
    public function test_certificate_admin_routes_are_strictly_read_only(): void
    {
        $cert = $this->createCertificate();

        $responsePut = $this->actingAs($this->admin)->put("/admin/certificates/{$cert->id}", ['student_name' => 'Hacked']);
        $responsePut->assertStatus(405);

        $responseDelete = $this->actingAs($this->admin)->delete("/admin/certificates/{$cert->id}");
        $responseDelete->assertStatus(405);
    }

    /**
     * Test 15: Viewing admin pages does NOT create duplicate certificate records.
     */
    public function test_viewing_admin_pages_does_not_create_duplicate_records(): void
    {
        $cert = $this->createCertificate();
        $initialCount = Certificate::count();

        $this->actingAs($this->admin)->get(route('admin.certificates.index'));
        $this->actingAs($this->admin)->get(route('admin.certificates.show', $cert));

        $this->assertEquals($initialCount, Certificate::count());
    }

    /**
     * Test 16: Existing student certificate viewer remains fully operational.
     */
    public function test_existing_student_certificate_viewer_remains_operational(): void
    {
        $cert = $this->createCertificate();

        $response = $this->actingAs($this->studentA)->get(route('student.certificates.show', $cert));
        $response->assertOk();
        $response->assertSee($cert->certificate_number);
    }
}