<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\EngagementLog;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MarketingUnsubscribe;
use App\Models\Order;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Services\CourseRenewalNotificationService;
use App\Services\OrderFulfillmentService;
use App\Services\RetentionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionAndRenewalOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $admin;
    protected Course $course;
    protected CourseModule $module;
    protected array $lessons = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'name' => 'Active Learner',
            'email' => 'learner@marketianmind.test',
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'name' => 'Admin User',
            'email' => 'admin@marketianmind.test',
        ]);

        $this->course = Course::create([
            'title' => 'Practical Local Business Marketing',
            'slug' => 'practical-local-business-marketing',
            'short_description' => 'Systematic customer acquisition for local service businesses.',
            'description' => 'A comprehensive 365-day access marketing education framework.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Foundations of Local Acquisition',
            'slug' => 'foundations-of-local-acquisition',
            'sort_order' => 1,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->lessons[] = Lesson::create([
                'course_module_id' => $this->module->id,
                'title' => "Lesson {$i}: Strategic Channel {$i}",
                'slug' => "lesson-{$i}-strategic-channel-{$i}",
                'content' => "Practical tactical breakdown for lesson {$i}.",
                'status' => LessonStatus::PUBLISHED,
                'sort_order' => $i,
                'is_preview' => false,
            ]);
        }
    }

    public function test_student_dashboard_displays_immediate_access_and_start_course_cta(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
            'starts_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Access Active');
        $response->assertSee('365 days remaining');
        $response->assertSee('Start Course');
        $response->assertSee(route('student.courses.lessons.show', [$this->course, $this->lessons[0]]));
    }

    public function test_student_dashboard_displays_continue_learning_cta_with_in_progress_status(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(10),
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(355),
        ]);

        // Complete first 2 lessons (40% progress)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessons[0]->id,
            'completed' => true,
            'completed_at' => now()->subDays(5),
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessons[1]->id,
            'completed' => true,
            'completed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Continue Learning');
        $response->assertSee('40%');
        $response->assertSee('355 days remaining');
        // Directs to lesson 3
        $response->assertSee(route('student.courses.lessons.show', [$this->course, $this->lessons[2]]));
    }

    public function test_student_dashboard_displays_review_course_and_certificate_for_completed_courses(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subDays(60),
            'starts_at' => now()->subDays(60),
            'expires_at' => now()->addDays(305),
            'completed_at' => now()->subDays(5),
        ]);

        foreach ($this->lessons as $lesson) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
                'completed' => true,
                'completed_at' => now()->subDays(5),
            ]);
        }

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'instructor_name' => 'Marketian Mind Team',
            'course_completion_date' => now()->subDays(5),
            'certificate_number' => 'MM-CERT-2026-TEST',
            'issued_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Review Course');
        $response->assertSee('100%');
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $certificate));
    }

    public function test_student_dashboard_displays_expiring_soon_with_renew_early_cta(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(345),
            'starts_at' => now()->subDays(345),
            'expires_at' => now()->addDays(20),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessons[0]->id,
            'completed' => true,
            'completed_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Expiring Soon');
        $response->assertSee('20 days remaining');
        $response->assertSee('Renew Early');
        $response->assertSee('Continue Learning');
        $response->assertSee(route('student.courses.purchase', $this->course));
    }

    public function test_student_dashboard_preserves_progress_and_displays_renew_access_for_expired_course(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => now()->subDays(400),
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
        ]);

        // Completed 3 of 5 lessons (60% progress)
        for ($i = 0; $i < 3; $i++) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $this->lessons[$i]->id,
                'completed' => true,
                'completed_at' => now()->subDays(50),
            ]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Access Expired');
        $response->assertSee('60%');
        $response->assertSee('Renew Access');
        $response->assertSee('Your learning progress (60%) and certificate records are permanently preserved.');
        $response->assertSee(route('student.courses.purchase', $this->course));
    }

    public function test_expired_course_player_denies_access_with_403(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => now()->subDays(400),
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.show', $this->course));

        $response->assertStatus(403);
    }

    public function test_expired_student_cannot_access_lesson_content(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => now()->subDays(400),
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lessons[0]]));

        $response->assertStatus(403);
    }

    public function test_early_renewal_stacks_full_365_days_on_top_of_remaining_validity(): void
    {
        $currentExpiry = now()->addDays(45);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(320),
            'starts_at' => now()->subDays(320),
            'expires_at' => $currentExpiry,
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(320),
            'expires_at' => $currentExpiry,
        ]);

        // Complete 2 lessons
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessons[0]->id,
            'completed' => true,
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lessons[1]->id,
            'completed' => true,
        ]);

        $renewalOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-RENEW-101',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => [
                'purchase_type' => 'renewal',
            ],
        ]);

        $fulfillmentService = app(OrderFulfillmentService::class);
        $period = $fulfillmentService->fulfillCourseAccess($this->student, $this->course, $renewalOrder);

        $enrollment->refresh();

        $this->assertNotNull($period);
        $this->assertEquals('renewal', $period->period_type);
        $this->assertTrue($period->isEarlyRenewal());

        // Period starts exactly when the current access expires
        $this->assertEquals($currentExpiry->format('Y-m-d H:i'), $period->starts_at->format('Y-m-d H:i'));

        // Period expires 365 days after current expiry
        $expectedNewExpiry = $currentExpiry->copy()->addDays(365);
        $this->assertEquals($expectedNewExpiry->format('Y-m-d H:i'), $period->expires_at->format('Y-m-d H:i'));
        $this->assertEquals($expectedNewExpiry->format('Y-m-d H:i'), $enrollment->expires_at->format('Y-m-d H:i'));

        // Student still has active access and progress is preserved
        $this->assertTrue($this->student->hasActiveAccessTo($this->course));
        $this->assertEquals(40, $this->course->progressFor($this->student)['percentage']);
    }

    public function test_post_expiry_renewal_starts_at_renewal_date_and_preserves_completion_records(): void
    {
        $expiredDate = now()->subDays(30);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => now()->subDays(395),
            'starts_at' => now()->subDays(395),
            'expires_at' => $expiredDate,
            'completed_at' => now()->subDays(100),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(395),
            'expires_at' => $expiredDate,
        ]);

        foreach ($this->lessons as $lesson) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
                'completed' => true,
            ]);
        }

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'instructor_name' => 'Marketian Mind Team',
            'course_completion_date' => now()->subDays(100),
            'certificate_number' => 'MM-CERT-RENEW-TEST',
            'issued_at' => now()->subDays(100),
        ]);

        $renewalOrder = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-POSTEXP-102',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        $fulfillmentService = app(OrderFulfillmentService::class);
        $period = $fulfillmentService->fulfillCourseAccess($this->student, $this->course, $renewalOrder);

        $enrollment->refresh();

        $this->assertNotNull($period);
        $this->assertEquals('renewal', $period->period_type);
        $this->assertFalse($period->isEarlyRenewal());

        // Access starts at now and expires now + 365 days
        $this->assertEquals(now()->format('Y-m-d'), $enrollment->starts_at->format('Y-m-d'));
        $this->assertEquals(now()->addDays(365)->format('Y-m-d'), $enrollment->expires_at->format('Y-m-d'));

        // Completion status and certificate are preserved
        $this->assertEquals(EnrollmentStatus::COMPLETED, $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertDatabaseHas('certificates', ['id' => $certificate->id]);
    }

    public function test_renewal_notifications_are_idempotent_and_suppressed_if_already_renewed(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(340),
            'starts_at' => now()->subDays(340),
            'expires_at' => now()->addDays(25),
        ]);

        $initialPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now()->subDays(340),
            'expires_at' => now()->addDays(25),
        ]);

        $service = app(CourseRenewalNotificationService::class);

        // First run: sends 30-day milestone notification
        $stats1 = $service->processUpcomingExpirations();
        $this->assertEquals(1, $stats1['sent_30']);
        $this->assertEquals(0, $stats1['skipped_idempotent']);

        // Check engagement log created
        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "expiry_30_{$initialPeriod->id}",
        ]);

        // Second run: idempotent, skipped
        $stats2 = $service->processUpcomingExpirations();
        $this->assertEquals(0, $stats2['sent_30']);
        $this->assertEquals(1, $stats2['skipped_idempotent']);

        // Student renews early: expires_at moves to now + 25 + 365 = 390 days ahead
        $enrollment->update([
            'expires_at' => now()->addDays(390),
        ]);

        // Third run: student is no longer in the expiring soon window (expires_at > 30 days ahead)
        $stats3 = $service->processUpcomingExpirations();
        $this->assertEquals(0, $stats3['checked']);
    }

    public function test_retention_service_identifies_learning_support_signals_correctly(): void
    {
        $retentionService = app(RetentionService::class);

        // Student 1: Purchased 5 days ago, 0 lessons completed (Not Started)
        $studentNotStarted = User::factory()->create(['role' => UserRole::STUDENT]);
        Enrollment::create([
            'user_id' => $studentNotStarted->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        // Student 2: Enrolled 30 days ago, completed 1 lesson 20 days ago (Inactive)
        $studentInactive = User::factory()->create(['role' => UserRole::STUDENT]);
        Enrollment::create([
            'user_id' => $studentInactive->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(30),
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays(335),
        ]);
        LessonProgress::create([
            'user_id' => $studentInactive->id,
            'lesson_id' => $this->lessons[0]->id,
            'completed' => true,
            'completed_at' => now()->subDays(20),
        ]);

        // Student 3: Completed 4 of 5 lessons = 80% (Approaching Completion)
        $studentNearing = User::factory()->create(['role' => UserRole::STUDENT]);
        Enrollment::create([
            'user_id' => $studentNearing->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->addDays(345),
        ]);
        for ($i = 0; $i < 4; $i++) {
            LessonProgress::create([
                'user_id' => $studentNearing->id,
                'lesson_id' => $this->lessons[$i]->id,
                'completed' => true,
                'completed_at' => now()->subDays(2),
            ]);
        }

        $summary = $retentionService->getRetentionSummary();

        $this->assertEquals(1, $summary['not_started_count']);
        $this->assertEquals(1, $summary['inactive_learners_count']);
        $this->assertEquals(1, $summary['approaching_completion_count']);

        // Check cohort queries
        $notStartedCohort = $retentionService->getStudentsNeedingSupport('not_started');
        $this->assertCount(1, $notStartedCohort->items());
        $this->assertEquals($studentNotStarted->id, $notStartedCohort->items()[0]->user_id);
        $this->assertEquals('kickstart', $notStartedCohort->items()[0]->suggested_action);

        $inactiveCohort = $retentionService->getStudentsNeedingSupport('inactive');
        $this->assertCount(1, $inactiveCohort->items());
        $this->assertEquals($studentInactive->id, $inactiveCohort->items()[0]->user_id);
        $this->assertEquals('reengagement', $inactiveCohort->items()[0]->suggested_action);

        $nearingCohort = $retentionService->getStudentsNeedingSupport('approaching_completion');
        $this->assertCount(1, $nearingCohort->items());
        $this->assertEquals($studentNearing->id, $nearingCohort->items()[0]->user_id);
        $this->assertEquals('completion_push', $nearingCohort->items()[0]->suggested_action);
    }

    public function test_retention_service_learning_support_dispatch_is_idempotent_and_respects_cooldown(): void
    {
        $retentionService = app(RetentionService::class);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        // First dispatch: success
        $res1 = $retentionService->sendLearningSupport($this->student, $this->course, 'kickstart');
        $this->assertTrue($res1['sent']);

        // Check notification created
        $this->assertEquals(1, $this->student->notifications()->count());
        $this->assertEquals('learning_support_kickstart', $this->student->notifications()->first()->data['type']);

        // Check engagement log
        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "support_kickstart_{$this->course->id}",
        ]);

        // Second dispatch within 7 days: blocked by cooldown
        $res2 = $retentionService->sendLearningSupport($this->student, $this->course, 'kickstart');
        $this->assertFalse($res2['sent']);
        $this->assertStringContainsString('cooldown', $res2['reason']);
        $this->assertEquals(1, $this->student->notifications()->count());
    }

    public function test_retention_service_suppresses_support_for_unsubscribed_students(): void
    {
        $retentionService = app(RetentionService::class);

        MarketingUnsubscribe::create([
            'email' => $this->student->email,
            'reason' => 'user_request',
            'unsubscribed_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        $res = $retentionService->sendLearningSupport($this->student, $this->course, 'kickstart');
        $this->assertFalse($res['sent']);
        $this->assertStringContainsString('unsubscribed', $res['reason']);
        $this->assertEquals(0, $this->student->notifications()->count());
    }

    public function test_admin_retention_workspace_displays_kpis_and_cohort_filtering(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.retention.index'));

        $response->assertStatus(200);
        $response->assertSee('Retention &amp; Renewal Optimization', false);
        $response->assertSee('Active (14d)');
        $response->assertSee('Not Started (3d+)');
        $response->assertSee('Renewal Rate');
        $response->assertSee($this->student->name);
        $response->assertSee('Send Kick-start');

        // Filter by cohort
        $cohortResponse = $this->actingAs($this->admin)->get(route('admin.retention.index', ['cohort' => 'not_started']));
        $cohortResponse->assertStatus(200);
        $cohortResponse->assertSee($this->student->name);
    }

    public function test_admin_can_send_manual_support_from_retention_workspace(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.retention.support', [
            'student' => $this->student->id,
            'course' => $this->course->id,
        ]), [
            'support_type' => 'kickstart',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(1, $this->student->notifications()->count());
    }

    public function test_admin_can_export_retention_cohorts_as_csv(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.retention.export', ['cohort' => 'all']));

        $response->assertStatus(200);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_artisan_retention_process_support_command_runs_successfully(): void
    {
        // Set up student needing kickstart
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(5),
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(360),
        ]);

        $this->artisan('retention:process-support', ['--dry-run' => true])
            ->expectsOutputToContain('Running in DRY-RUN mode')
            ->expectsOutputToContain('Retention support processing completed successfully')
            ->assertExitCode(0);
    }
}
