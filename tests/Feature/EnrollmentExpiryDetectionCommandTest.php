<?php

namespace Tests\Feature;

use App\Console\Commands\CheckEnrollmentExpiryCommand;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Events\EnrollmentExpired;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EnrollmentExpiryDetectionCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
            'short_description' => 'Comprehensive marketing mastery.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 1: CORE EXPIRY SYNCHRONIZATION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_active_enrollment_with_past_expires_at_is_synchronized_to_expired(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        Event::fake([EnrollmentExpired::class]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'), // Expired 1 day ago
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Checked 1 eligible active enrollments.')
            ->expectsOutputToContain('Successfully synchronized 1 enrollments to EXPIRED status.')
            ->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);

        Event::assertDispatched(EnrollmentExpired::class, function ($event) use ($enrollment) {
            return $event->enrollment->id === $enrollment->id;
        });
    }

    public function test_active_enrollment_with_future_expires_at_remains_active(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        Event::fake([EnrollmentExpired::class]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'), // 11 months in future
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Checked 0 eligible active enrollments.')
            ->expectsOutputToContain('Successfully synchronized 0 enrollments to EXPIRED status.')
            ->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        Event::assertNotDispatched(EnrollmentExpired::class);
    }

    public function test_enrollment_expiring_exactly_at_now_is_not_prematurely_synchronized(): void
    {
        // Boundary rule: expires_at < now()
        // At exactly expires_at == now(), the strict inequality is not satisfied yet
        $now = Carbon::parse('2027-09-14 10:00:00');
        Carbon::setTestNow($now);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => $now, // Exactly equal to now
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);

        // 1 second later, now > expires_at, so it is synchronized
        Carbon::setTestNow($now->copy()->addSecond());

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 2: ACCESS SECURITY INDEPENDENCE
    |--------------------------------------------------------------------------
    */

    public function test_access_authorization_denies_access_before_expiry_command_runs(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE, // Still marked active before scheduler
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'), // Expired yesterday
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        // CRITICAL INVARIANT: Authorization engine denies access independently of scheduler execution
        $this->assertFalse($enrollment->hasActiveAccess());
        $this->assertFalse($this->student->hasActiveAccessTo($this->course));
        $this->assertTrue($enrollment->isAccessExpired());

        // Now run scheduler
        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);
        $this->assertFalse($enrollment->hasActiveAccess());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 3: INVARIANT PRESERVATION TESTS
    |--------------------------------------------------------------------------
    */

    public function test_dates_are_strictly_preserved_during_expiry_synchronization(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $startsAt = Carbon::parse('2026-09-14 10:00:00');
        $expiresAt = Carbon::parse('2027-09-14 10:00:00');
        $enrolledAt = Carbon::parse('2026-09-14 10:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'enrolled_at' => $enrolledAt,
        ]);

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);

        // All dates must remain 100% untouched
        $this->assertEquals($startsAt->format('Y-m-d H:i:s'), $enrollment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $enrollment->expires_at->format('Y-m-d H:i:s'));
        $this->assertEquals($enrolledAt->format('Y-m-d H:i:s'), $enrollment->enrolled_at->format('Y-m-d H:i:s'));
    }

    public function test_completed_enrollments_retain_completed_status_progress_and_certificate(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $completedAt = Carbon::parse('2026-12-01 12:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'), // Expired yesterday
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
            'completed_at' => $completedAt,
        ]);

        $progress = LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => $completedAt,
            'last_watched_at' => $completedAt,
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'certificate_number' => 'CERT-EXP-001',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => $completedAt,
            'issued_at' => $completedAt,
        ]);

        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Preserved 1 completed enrollments with expired access')
            ->assertSuccessful();

        $enrollment->refresh();

        // 1. Completion status and timestamp are preserved
        $this->assertEquals(EnrollmentStatus::COMPLETED, $enrollment->status);
        $this->assertEquals($completedAt->format('Y-m-d H:i:s'), $enrollment->completed_at->format('Y-m-d H:i:s'));

        // 2. Learning access is safely blocked because now >= expires_at
        $this->assertFalse($enrollment->hasActiveAccess());

        // 3. Progress and Certificate preserved
        $progress->refresh();
        $this->assertTrue($progress->completed);

        $certificate->refresh();
        $this->assertEquals('CERT-EXP-001', $certificate->certificate_number);
    }

    public function test_cancelled_enrollments_remain_cancelled(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::CANCELLED, $enrollment->status);
    }

    public function test_legacy_lifetime_records_remain_untouched(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $legacyEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => Carbon::parse('2024-01-01 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $legacyEnrollment->refresh();
        $this->assertEquals(EnrollmentStatus::ACTIVE, $legacyEnrollment->status);
        $this->assertNull($legacyEnrollment->starts_at);
        $this->assertNull($legacyEnrollment->expires_at);
        $this->assertTrue($legacyEnrollment->isLegacyLifetimeAccess());
    }

    public function test_partial_null_date_records_are_skipped_without_mutation(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        // Record 1: starts_at null, expires_at set
        $enr1 = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
            'enrolled_at' => now(),
        ]);

        // Record 2: starts_at set, expires_at null
        $course2 = Course::create([
            'title' => 'Another Course',
            'slug' => 'another-course',
            'short_description' => 'Desc',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $enr2 = Enrollment::create([
            'user_id' => $otherStudent->id,
            'course_id' => $course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => null,
            'enrolled_at' => now(),
        ]);

        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Skipped 2 incomplete/partial-null date enrollments.')
            ->assertSuccessful();

        $enr1->refresh();
        $enr2->refresh();

        $this->assertEquals(EnrollmentStatus::ACTIVE, $enr1->status);
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enr2->status);
    }

    public function test_course_access_periods_remain_completely_unmodified(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-HIST-001',
            'amount' => 499900,
            'status' => \App\Enums\OrderStatus::PAID,
            'paid_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $this->assertEquals(1, CourseAccessPeriod::count());
        $accessPeriod->refresh();
        $this->assertEquals($order->id, $accessPeriod->order_id);
        $this->assertEquals('initial', $accessPeriod->period_type);
        $this->assertEquals('2026-09-14 10:00:00', $accessPeriod->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-09-14 10:00:00', $accessPeriod->expires_at->format('Y-m-d H:i:s'));

        // Zero orders or payments created
        $this->assertEquals(1, Order::count());
        $this->assertEquals(0, Payment::count());
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 4: IDEMPOTENCY & DRY-RUN TESTS
    |--------------------------------------------------------------------------
    */

    public function test_command_is_strictly_idempotent_when_run_multiple_times(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        // Run 1: Synchronizes 1 enrollment
        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Successfully synchronized 1 enrollments to EXPIRED status.')
            ->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);

        // Run 2: Immediately safe, 0 additional enrollments
        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Checked 0 eligible active enrollments.')
            ->expectsOutputToContain('Successfully synchronized 0 enrollments to EXPIRED status.')
            ->assertSuccessful();

        // Run 3: Idempotent
        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Checked 0 eligible active enrollments.')
            ->assertSuccessful();

        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);
    }

    public function test_dry_run_simulates_without_mutating_database(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        Event::fake([EnrollmentExpired::class]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
            'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
            'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
        ]);

        $this->artisan('enrollments:check-expiry', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN: 1 enrollments would be synchronized to EXPIRED.')
            ->assertSuccessful();

        $enrollment->refresh();
        // Still ACTIVE in database
        $this->assertEquals(EnrollmentStatus::ACTIVE, $enrollment->status);
        Event::assertNotDispatched(EnrollmentExpired::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 5: SCHEDULER REGISTRATION TEST
    |--------------------------------------------------------------------------
    */

    public function test_expiry_command_is_registered_in_console_schedule(): void
    {
        $schedule = app(Schedule::class);

        $events = collect($schedule->events());

        $hasExpiryCommand = $events->contains(function ($event) {
            return str_contains($event->command, 'enrollments:check-expiry')
                && $event->expression === '0 0 * * *'; // daily at midnight
        });

        $this->assertTrue($hasExpiryCommand, 'Command enrollments:check-expiry is not registered as daily in routes/console.php');
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 6: CHUNKING & BATCH PROCESSING TEST
    |--------------------------------------------------------------------------
    */

    public function test_command_processes_large_dataset_in_chunks_safely(): void
    {
        Carbon::setTestNow('2027-09-15 10:00:00');

        // Create 25 students with past expires_at
        $users = User::factory()->count(25)->create(['role' => UserRole::STUDENT]);

        foreach ($users as $user) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $this->course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'starts_at' => Carbon::parse('2026-09-14 10:00:00'),
                'expires_at' => Carbon::parse('2027-09-14 10:00:00'),
                'enrolled_at' => Carbon::parse('2026-09-14 10:00:00'),
            ]);
        }

        $this->artisan('enrollments:check-expiry')
            ->expectsOutputToContain('Checked 25 eligible active enrollments.')
            ->expectsOutputToContain('Successfully synchronized 25 enrollments to EXPIRED status.')
            ->assertSuccessful();

        $this->assertEquals(25, Enrollment::where('status', EnrollmentStatus::EXPIRED->value)->count());
        $this->assertEquals(0, Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count());
    }
}
