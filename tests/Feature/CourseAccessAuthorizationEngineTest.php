<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseAccessAuthorizationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected Course $course;
    protected CourseModule $module;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Advanced SEO Masterclass',
            'slug' => 'advanced-seo-masterclass',
            'short_description' => 'Master SEO from scratch.',
            'description' => 'Comprehensive SEO course for serious marketers.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Keyword Research & Strategy',
            'sort_order' => 1,
        ]);

        $this->lesson = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Search Intent Analysis',
            'slug' => 'search-intent-analysis',
            'content' => 'Deep dive into transactional and informational keywords.',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Unit / Engine Checks: Enrollment::hasActiveAccess()
    |--------------------------------------------------------------------------
    */

    public function test_active_enrollment_within_date_window_has_active_access(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonths(3),
            'expires_at' => now()->addMonths(9),
        ]);

        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertFalse($enrollment->isExpired());
        $this->assertFalse($enrollment->isAccessExpired());
    }

    public function test_active_enrollment_with_future_starts_at_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->addDays(5),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
    }

    public function test_active_enrollment_with_past_expires_at_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());
        $this->assertTrue($enrollment->isAccessExpired());
    }

    public function test_active_enrollment_exactly_at_expires_at_is_denied(): void
    {
        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $frozenNow->copy()->subYear(),
            'expires_at' => $frozenNow,
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());

        Carbon::setTestNow();
    }

    public function test_active_enrollment_one_second_before_expires_at_is_allowed(): void
    {
        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $frozenNow->copy()->subYear(),
            'expires_at' => $frozenNow->copy()->addSecond(),
        ]);

        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertFalse($enrollment->isExpired());

        Carbon::setTestNow();
    }

    public function test_active_enrollment_one_second_after_expires_at_is_denied(): void
    {
        $frozenNow = Carbon::create(2026, 9, 14, 12, 0, 0);
        Carbon::setTestNow($frozenNow);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $frozenNow->copy()->subYear(),
            'expires_at' => $frozenNow->copy()->subSecond(),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());

        Carbon::setTestNow();
    }

    public function test_completed_enrollment_within_date_window_has_active_access(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => now()->subMonths(6),
            'expires_at' => now()->addMonths(6),
            'completed_at' => now()->subMonth(),
        ]);

        $this->assertTrue($enrollment->hasActiveAccess());
    }

    public function test_completed_enrollment_with_past_expires_at_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => now()->subYears(2),
            'expires_at' => now()->subYear(),
            'completed_at' => now()->subMonths(18),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());
    }

    public function test_cancelled_enrollment_even_with_valid_dates_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
    }

    public function test_expired_status_enrollment_even_with_future_dates_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());
    }

    public function test_legacy_enrollment_with_both_null_dates_is_allowed(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isLegacyLifetimeAccess());
        $this->assertFalse($enrollment->isExpired());
    }

    public function test_legacy_completed_enrollment_with_both_null_dates_is_allowed(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => null,
            'expires_at' => null,
            'completed_at' => now()->subDays(10),
        ]);

        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isLegacyLifetimeAccess());
    }

    public function test_partial_null_dates_starts_at_set_expires_at_null_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonth(),
            'expires_at' => null,
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());
    }

    public function test_partial_null_dates_starts_at_null_expires_at_set_is_denied(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => now()->addYear(),
        ]);

        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertTrue($enrollment->isExpired());
    }

    public function test_access_check_is_read_only_and_does_not_mutate_database(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYears(2),
            'expires_at' => now()->subYear(),
        ]);

        $initialUpdatedAt = $enrollment->updated_at;

        // Perform check
        $hasAccess = $enrollment->hasActiveAccess();

        $this->assertFalse($hasAccess);
        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $fresh->status);
        $this->assertEquals($initialUpdatedAt->toDateTimeString(), $fresh->updated_at->toDateTimeString());
    }

    /*
    |--------------------------------------------------------------------------
    | User & Course Model Access Checks
    |--------------------------------------------------------------------------
    */

    public function test_user_has_active_access_to_returns_true_for_valid_enrollment(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(11),
        ]);

        $this->assertTrue($this->student->hasActiveAccessTo($this->course));
        $this->assertTrue($this->course->hasActiveAccessFor($this->student));
    }

    public function test_user_has_active_access_to_returns_false_for_expired_enrollment(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->student->hasActiveAccessTo($this->course));
        $this->assertFalse($this->course->hasActiveAccessFor($this->student));
    }

    public function test_user_without_enrollment_returns_false(): void
    {
        $this->assertFalse($this->student->hasActiveAccessTo($this->course));
        $this->assertFalse($this->course->hasActiveAccessFor($this->student));
    }

    public function test_course_has_active_access_for_returns_false_for_null_user(): void
    {
        $this->assertFalse($this->course->hasActiveAccessFor(null));
    }

    public function test_user_access_respects_preloaded_enrollments_relationship(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(11),
        ]);

        $freshUser = User::with('enrollments')->find($this->student->id);
        $this->assertTrue($freshUser->hasActiveAccessTo($this->course));
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP Route Authorization: Lessons & Course Player
    |--------------------------------------------------------------------------
    */

    public function test_enrolled_student_with_active_access_can_view_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(11),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [$this->course, $this->lesson]));

        $response->assertStatus(200);
        $response->assertSee('Search Intent Analysis');
    }

    public function test_enrolled_student_with_expired_access_is_forbidden_from_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [$this->course, $this->lesson]));

        $response->assertStatus(403);
    }

    public function test_student_without_enrollment_is_forbidden_from_lesson(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [$this->course, $this->lesson]));

        $response->assertStatus(403);
    }

    public function test_legacy_enrolled_student_with_null_dates_can_view_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [$this->course, $this->lesson]));

        $response->assertStatus(200);
    }

    public function test_course_player_show_allows_active_access(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(11),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.show', $this->course));

        // show redirects to the next lesson or returns 200
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_course_player_show_denies_expired_access(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.show', $this->course));

        $response->assertStatus(403);
    }

    public function test_expired_student_cannot_complete_lesson(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson]));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_expired_student_cannot_download_lesson_pdf(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.pdf', [$this->course, $this->lesson]));

        $response->assertStatus(403);
    }

    public function test_expired_student_cannot_download_lesson_resource(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'SEO Checklist',
            'file_path' => 'resources/seo-checklist.pdf',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $resource]));

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Progress & Certificate Non-Regression
    |--------------------------------------------------------------------------
    */

    public function test_existing_progress_is_preserved_when_access_expires(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
            'completed_at' => now()->subMonths(6),
        ]);

        // Access is denied
        $response = $this->actingAs($this->student)
            ->get(route('student.courses.lessons.show', [$this->course, $this->lesson]));

        $response->assertStatus(403);

        // Progress record remains intact
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => 1,
        ]);
    }

    public function test_earned_certificate_remains_accessible_after_course_access_expires(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => now()->subYears(2),
            'expires_at' => now()->subYear(),
            'completed_at' => now()->subMonths(18),
        ]);

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-CERT-2026-TEST01',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => now()->subMonths(18),
            'issued_at' => now()->subMonths(18),
        ]);

        // Student visits their certificate page
        $response = $this->actingAs($this->student)
            ->get(route('student.certificates.show', $certificate));

        $response->assertStatus(200);
        $response->assertSee('MM-CERT-2026-TEST01');
    }
}