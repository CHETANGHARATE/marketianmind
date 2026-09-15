<?php

namespace Tests\Feature;

use App\Console\Commands\SendCourseExpiryNotificationsCommand;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentExpired;
use App\Mail\CourseAccessExpiredMail;
use App\Mail\CourseExpiringSoonMail;
use App\Mail\CourseRenewalSuccessMail;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\EngagementLog;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CourseAccessExpiredNotification;
use App\Notifications\CourseExpiringSoonNotification;
use App\Notifications\CourseRenewalSuccessNotification;
use App\Services\CourseRenewalNotificationService;
use App\Services\OrderFulfillmentService;
use App\Services\TransactionalMailService;
use App\Services\WhatsAppService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseExpirationAndRenewalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'email' => 'student.learner@example.com',
            'role' => 'student',
            'phone' => '+919876543210',
        ]);

        $this->course = Course::create([
            'title' => 'Advanced Digital Marketing Strategy',
            'slug' => 'advanced-digital-marketing-strategy',
            'short_description' => 'Master digital marketing strategies.',
            'description' => 'Comprehensive digital marketing course with practical assignments.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);
    }

    public function test_active_enrollment_with_30_days_remaining_triggers_30_day_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(335),
            'expires_at' => now()->addDays(30),
            'enrolled_at' => now()->subDays(335),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->student,
            CourseExpiringSoonNotification::class,
            function (CourseExpiringSoonNotification $notification) {
                return $notification->daysRemaining === 30
                    && $notification->course->id === $this->course->id;
            }
        );

        Mail::assertSent(CourseExpiringSoonMail::class, function (CourseExpiringSoonMail $mail) {
            return $mail->daysRemaining === 30
                && $mail->hasTo('student.learner@example.com');
        });

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "expiry_30_{$accessPeriod->id}",
        ]);
    }

    public function test_active_enrollment_with_7_days_remaining_triggers_7_day_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->student,
            CourseExpiringSoonNotification::class,
            function (CourseExpiringSoonNotification $notification) {
                return $notification->daysRemaining === 7;
            }
        );

        Mail::assertSent(CourseExpiringSoonMail::class, function (CourseExpiringSoonMail $mail) {
            return $mail->daysRemaining === 7;
        });

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "expiry_7_{$accessPeriod->id}",
        ]);
    }

    public function test_active_enrollment_with_1_day_remaining_triggers_1_day_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(364),
            'expires_at' => now()->addHours(18),
            'enrolled_at' => now()->subDays(364),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->student,
            CourseExpiringSoonNotification::class,
            function (CourseExpiringSoonNotification $notification) {
                return $notification->daysRemaining === 1;
            }
        );

        Mail::assertSent(CourseExpiringSoonMail::class, function (CourseExpiringSoonMail $mail) {
            return $mail->daysRemaining === 1;
        });

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "expiry_1_{$accessPeriod->id}",
        ]);
    }

    public function test_enrollment_with_more_than_30_days_remaining_does_not_trigger_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(100),
            'expires_at' => now()->addDays(265),
            'enrolled_at' => now()->subDays(100),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')
            ->assertSuccessful();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('engagement_logs');
    }

    public function test_expiring_soon_notification_is_strictly_idempotent_on_repeated_runs(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(335),
            'expires_at' => now()->addDays(30),
            'enrolled_at' => now()->subDays(335),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        // First run: dispatches notification
        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();
        Notification::assertSentToTimes($this->student, CourseExpiringSoonNotification::class, 1);
        Mail::assertSent(CourseExpiringSoonMail::class, 1);

        // Second run: idempotency skips re-sending
        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();
        Notification::assertSentToTimes($this->student, CourseExpiringSoonNotification::class, 1);
        Mail::assertSent(CourseExpiringSoonMail::class, 1);

        $this->assertEquals(1, EngagementLog::query()->where('type', "expiry_30_{$accessPeriod->id}")->count());
    }

    public function test_early_renewal_invalidates_and_stops_stale_reminders(): void
    {
        Notification::fake();
        Mail::fake();

        // Student has 7 days remaining
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        // Early renewal order is purchased and fulfilled
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-RENEW-001',
            'amount' => 499900,
            'original_amount' => 499900,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $enrollment->refresh();

        // Expiration is now 7 + 365 = 372 days in the future
        $this->assertGreaterThan(365, now()->diffInDays($enrollment->expires_at));

        // When scheduled command runs, no reminder is sent
        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        Notification::assertNotSentTo($this->student, CourseExpiringSoonNotification::class);
        Mail::assertNotSent(CourseExpiringSoonMail::class);
    }

    public function test_access_expired_notification_dispatched_on_expiration_event(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(366),
            'expires_at' => now()->subDay(),
            'enrolled_at' => now()->subDays(366),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        // Background worker synchronizes expired status and fires event
        $this->artisan('enrollments:check-expiry')->assertSuccessful();

        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::EXPIRED, $enrollment->status);

        Notification::assertSentTo(
            $this->student,
            CourseAccessExpiredNotification::class,
            function (CourseAccessExpiredNotification $notification) {
                return $notification->course->id === $this->course->id;
            }
        );

        Mail::assertSent(CourseAccessExpiredMail::class, function (CourseAccessExpiredMail $mail) {
            return $mail->hasTo('student.learner@example.com')
                && $mail->course->id === $this->course->id;
        });

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "access_expired_{$accessPeriod->id}",
        ]);
    }

    public function test_access_expired_notification_is_idempotent(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => now()->subDays(366),
            'expires_at' => now()->subDay(),
            'enrolled_at' => now()->subDays(366),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $service = app(CourseRenewalNotificationService::class);

        // First call sends notification
        $firstResult = $service->sendAccessExpired($enrollment);
        $this->assertTrue($firstResult);
        Notification::assertSentToTimes($this->student, CourseAccessExpiredNotification::class, 1);
        Mail::assertSent(CourseAccessExpiredMail::class, 1);

        // Second call skips
        $secondResult = $service->sendAccessExpired($enrollment);
        $this->assertFalse($secondResult);
        Notification::assertSentToTimes($this->student, CourseAccessExpiredNotification::class, 1);
        Mail::assertSent(CourseAccessExpiredMail::class, 1);

        $this->assertEquals(1, EngagementLog::query()->where('type', "access_expired_{$accessPeriod->id}")->count());
    }

    public function test_renewal_success_notification_dispatched_on_renewal_fulfillment(): void
    {
        Notification::fake();
        Mail::fake();

        // Existing expired enrollment
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED,
            'starts_at' => now()->subDays(400),
            'expires_at' => now()->subDays(35),
            'enrolled_at' => now()->subDays(400),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        // Renewal order
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-RENEW-002',
            'amount' => 499900,
            'original_amount' => 499900,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(OrderFulfillmentService::class)->fulfillOrder($order);

        $renewalPeriod = CourseAccessPeriod::where('order_id', $order->id)->first();
        $this->assertNotNull($renewalPeriod);
        $this->assertEquals('renewal', $renewalPeriod->period_type);

        Notification::assertSentTo(
            $this->student,
            CourseRenewalSuccessNotification::class,
            function (CourseRenewalSuccessNotification $notification) use ($renewalPeriod) {
                return $notification->course->id === $this->course->id
                    && $notification->accessPeriod->id === $renewalPeriod->id;
            }
        );

        Mail::assertSent(CourseRenewalSuccessMail::class, function (CourseRenewalSuccessMail $mail) use ($renewalPeriod) {
            return $mail->hasTo('student.learner@example.com')
                && $mail->accessPeriod->id === $renewalPeriod->id;
        });

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => "renewal_success_{$renewalPeriod->id}",
        ]);
    }

    public function test_legacy_lifetime_enrollments_are_skipped(): void
    {
        Notification::fake();
        Mail::fake();

        // Legacy enrollment with null access dates
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => null,
            'expires_at' => null,
            'enrolled_at' => now()->subMonths(6),
        ]);

        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('engagement_logs');
    }

    public function test_partial_null_dates_are_skipped(): void
    {
        Notification::fake();
        Mail::fake();

        // Starts at is present but expires at is null
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(10),
            'expires_at' => null,
            'enrolled_at' => now()->subDays(10),
        ]);

        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('engagement_logs');
    }

    public function test_cancelled_enrollments_are_skipped(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::CANCELLED,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('engagement_logs');
    }

    public function test_completed_enrollments_receive_expiry_reminders_while_preserving_completed_status(): void
    {
        Notification::fake();
        Mail::fake();

        $completedAt = now()->subDays(50);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
            'completed_at' => $completedAt,
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        // Notification received
        Notification::assertSentTo($this->student, CourseExpiringSoonNotification::class);
        Mail::assertSent(CourseExpiringSoonMail::class);

        // Invariants: completed status and completed_at remain intact
        $enrollment->refresh();
        $this->assertEquals(EnrollmentStatus::COMPLETED, $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertEquals($completedAt->timestamp, $enrollment->completed_at->timestamp);
    }

    public function test_dry_run_option_simulates_without_sending_or_writing_logs(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications --dry-run')
            ->expectsOutputToContain('(Simulated)')
            ->assertSuccessful();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('engagement_logs');
    }

    public function test_channel_failure_isolation_does_not_abort_processing(): void
    {
        // Mock mail service to throw an exception
        $mockMailService = $this->createMock(TransactionalMailService::class);
        $mockMailService->method('sendCourseExpiringSoon')->willThrowException(new \RuntimeException('SMTP Connection Timeout'));

        $this->app->instance(TransactionalMailService::class, $mockMailService);

        Notification::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        $accessPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        // Command executes and finishes successfully despite mail failure
        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();

        // Database notification was still delivered
        Notification::assertSentTo($this->student, CourseExpiringSoonNotification::class);
    }

    public function test_whatsapp_notification_is_dispatched_when_enabled(): void
    {
        config(['whatsapp.enabled' => true]);

        $mockWhatsApp = $this->createMock(WhatsAppService::class);
        $mockWhatsApp->expects($this->once())
            ->method('sendTemplateMessage')
            ->with(
                $this->callback(fn ($u) => $u->id === $this->student->id),
                $this->equalTo('course_expiring_soon'),
                $this->callback(function ($context) {
                    return $context['days_remaining'] === '7'
                        && $context['course_title'] === $this->course->title;
                }),
                $this->stringStartsWith('expiry_7_')
            );

        $this->app->instance(WhatsAppService::class, $mockWhatsApp);

        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'starts_at' => now()->subDays(358),
            'expires_at' => now()->addDays(7),
            'enrolled_at' => now()->subDays(358),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $enrollment->starts_at,
            'expires_at' => $enrollment->expires_at,
        ]);

        $this->artisan('enrollments:send-expiry-notifications')->assertSuccessful();
    }

    public function test_scheduler_registers_send_expiry_notifications_command(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $hasCommand = $events->contains(function ($event) {
            return str_contains($event->command ?? '', 'enrollments:send-expiry-notifications');
        });

        $this->assertTrue($hasCommand, 'Schedule does not contain enrollments:send-expiry-notifications');
    }
}
