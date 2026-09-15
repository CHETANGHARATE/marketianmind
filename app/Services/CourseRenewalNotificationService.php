<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\EngagementLog;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CourseAccessExpiredNotification;
use App\Notifications\CourseExpiringSoonNotification;
use App\Notifications\CourseRenewalSuccessNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class CourseRenewalNotificationService
{
    public function __construct(
        protected TransactionalMailService $mailService,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Scan eligible enrollments and process upcoming expiration reminders.
     *
     * Invariants:
     * - Only considers enrollments with non-null access dates.
     * - Excludes legacy lifetime enrollments (both dates null).
     * - Excludes partial-null dates.
     * - Excludes CANCELLED enrollments.
     * - Preserves progress, certificates, and completion status.
     * - Uses idempotent access-period-scoped tracking via engagement_logs.
     *
     * @return array<string, int>
     */
    public function processUpcomingExpirations(bool $dryRun = false): array
    {
        $stats = [
            'checked' => 0,
            'sent_30' => 0,
            'sent_7' => 0,
            'sent_1' => 0,
            'skipped_idempotent' => 0,
            'skipped_ineligible' => 0,
        ];

        $now = now();
        $thirtyDaysAhead = $now->copy()->addDays(30);

        // Find active and completed enrollments whose access expires within 30 days and is in the future
        $query = Enrollment::query()
            ->with(['user', 'course', 'latestAccessPeriod'])
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $thirtyDaysAhead);

        $query->chunkById(200, function ($enrollments) use (&$stats, $dryRun, $now) {
            foreach ($enrollments as $enrollment) {
                $stats['checked']++;

                if (! $enrollment->user || ! $enrollment->course) {
                    $stats['skipped_ineligible']++;
                    continue;
                }

                // Calculate whole/ceiling days remaining until expiration
                $diffInSeconds = $now->diffInSeconds($enrollment->expires_at, false);
                if ($diffInSeconds <= 0) {
                    $stats['skipped_ineligible']++;
                    continue;
                }

                $daysRemaining = (int) ceil($diffInSeconds / 86400);

                // Determine appropriate reminder milestone
                $milestone = match (true) {
                    $daysRemaining <= 1 => 1,
                    $daysRemaining <= 7 => 7,
                    $daysRemaining <= 30 => 30,
                    default => null,
                };

                if ($milestone === null) {
                    $stats['skipped_ineligible']++;
                    continue;
                }

                $sent = $this->sendExpiringSoon($enrollment, $milestone, $dryRun);

                if ($sent) {
                    $stats["sent_{$milestone}"]++;
                } else {
                    $stats['skipped_idempotent']++;
                }
            }
        });

        return $stats;
    }

    /**
     * Send expiring soon notification for a specific milestone (30, 7, 1 day).
     */
    public function sendExpiringSoon(Enrollment $enrollment, int $milestone, bool $dryRun = false): bool
    {
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (! $user || ! $course || ! $enrollment->expires_at) {
            return false;
        }

        // Access period scope for idempotency
        $accessPeriod = $enrollment->latestAccessPeriod;
        $periodId = $accessPeriod?->id ?? $enrollment->id;
        $idempotencyKey = "expiry_{$milestone}_{$periodId}";

        // Check if already sent
        $alreadySent = EngagementLog::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('type', $idempotencyKey)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        // Multi-channel delivery with failure isolation
        $this->safelyDeliverDatabaseNotification($user, new CourseExpiringSoonNotification(
            $course,
            $milestone,
            $enrollment->expires_at,
            $accessPeriod
        ));

        $this->safelyDeliverMail(fn () => $this->mailService->sendCourseExpiringSoon(
            $user,
            $course,
            $milestone,
            $enrollment->expires_at,
            $accessPeriod
        ));

        $this->safelyDeliverWhatsAppMessage(
            $user,
            'course_expiring_soon',
            [
                'course_title' => $course->title,
                'days_remaining' => (string) $milestone,
                'expiry_date' => $enrollment->expires_at->format('M d, Y'),
                'renewal_url' => route('student.courses.show', $course),
            ],
            $idempotencyKey
        );

        // Record deterministic engagement log
        try {
            EngagementLog::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'type' => $idempotencyKey,
                'channel' => 'multi',
                'metadata' => [
                    'milestone_days' => $milestone,
                    'access_period_id' => $periodId,
                    'expires_at' => $enrollment->expires_at->toISOString(),
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record expiring soon engagement log', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
        }

        app(\App\Services\ConversionTrackingService::class)->track(
            \App\Enums\ConversionEventName::COURSE_ACCESS_EXPIRING,
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'metadata' => [
                    'milestone_days' => $milestone,
                    'access_period_id' => $periodId,
                    'expires_at' => $enrollment->expires_at->toISOString(),
                ],
            ]
        );

        app(\App\Services\ConversionTrackingService::class)->track(
            \App\Enums\ConversionEventName::RENEWAL_NOTIFICATION_SENT,
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'metadata' => [
                    'milestone_days' => $milestone,
                    'access_period_id' => $periodId,
                    'channel' => 'multi',
                ],
            ]
        );

        app(\App\Services\RenewalAnalyticsService::class)->logCrmRenewalActivity(
            $user,
            $course,
            'renewal_reminder_sent',
            "Renewal reminder sent ({$milestone} days remaining) for course: {$course->title}",
            [
                'milestone_days' => $milestone,
                'expires_at' => $enrollment->expires_at->toIso8601String(),
                'access_period_id' => $periodId,
            ]
        );

        return true;
    }

    /**
     * Send access expired notification when an enrollment expires.
     */
    public function sendAccessExpired(Enrollment $enrollment, bool $dryRun = false): bool
    {
        // Must have valid dates and not be cancelled
        if ($enrollment->status === EnrollmentStatus::CANCELLED) {
            return false;
        }

        if ($enrollment->starts_at === null || $enrollment->expires_at === null) {
            return false;
        }

        $user = $enrollment->user;
        $course = $enrollment->course;

        if (! $user || ! $course) {
            return false;
        }

        $accessPeriod = $enrollment->latestAccessPeriod;
        $periodId = $accessPeriod?->id ?? $enrollment->id;
        $idempotencyKey = "access_expired_{$periodId}";

        $alreadySent = EngagementLog::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('type', $idempotencyKey)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        // Multi-channel delivery with failure isolation
        $this->safelyDeliverDatabaseNotification($user, new CourseAccessExpiredNotification(
            $course,
            $accessPeriod
        ));

        $this->safelyDeliverMail(fn () => $this->mailService->sendCourseAccessExpired(
            $user,
            $course,
            $accessPeriod
        ));

        $this->safelyDeliverWhatsAppMessage(
            $user,
            'course_access_expired',
            [
                'course_title' => $course->title,
                'renewal_url' => route('student.courses.show', $course),
            ],
            $idempotencyKey
        );

        try {
            EngagementLog::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'type' => $idempotencyKey,
                'channel' => 'multi',
                'metadata' => [
                    'access_period_id' => $periodId,
                    'expired_at' => $enrollment->expires_at->toISOString(),
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record access expired engagement log', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
        }

        app(\App\Services\ConversionTrackingService::class)->track(
            \App\Enums\ConversionEventName::COURSE_ACCESS_EXPIRED,
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'metadata' => [
                    'access_period_id' => $periodId,
                    'expired_at' => $enrollment->expires_at->toISOString(),
                ],
            ]
        );

        app(\App\Services\RenewalAnalyticsService::class)->logCrmRenewalActivity(
            $user,
            $course,
            'course_access_expired',
            "Course access expired for: {$course->title}",
            [
                'access_period_id' => $periodId,
                'expired_at' => $enrollment->expires_at->toIso8601String(),
            ]
        );

        return true;
    }

    /**
     * Send renewal success notification upon successful renewal order fulfillment.
     */
    public function sendRenewalSuccess(User $user, Course $course, CourseAccessPeriod $period, ?Order $order = null): bool
    {
        $idempotencyKey = "renewal_success_{$period->id}";

        $alreadySent = EngagementLog::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('type', $idempotencyKey)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        // Multi-channel delivery with failure isolation
        $this->safelyDeliverDatabaseNotification($user, new CourseRenewalSuccessNotification(
            $course,
            $period,
            $order
        ));

        $this->safelyDeliverMail(fn () => $this->mailService->sendCourseRenewalSuccess(
            $user,
            $course,
            $period,
            $order
        ));

        $this->safelyDeliverWhatsAppMessage(
            $user,
            'course_renewal_success',
            [
                'course_title' => $course->title,
                'starts_at' => $period->starts_at?->format('M d, Y') ?? '',
                'expires_at' => $period->expires_at?->format('M d, Y') ?? '',
                'course_url' => route('student.courses.show', $course),
            ],
            $idempotencyKey
        );

        try {
            EngagementLog::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'type' => $idempotencyKey,
                'channel' => 'multi',
                'metadata' => [
                    'access_period_id' => $period->id,
                    'order_id' => $order?->id,
                    'starts_at' => $period->starts_at?->toISOString(),
                    'expires_at' => $period->expires_at?->toISOString(),
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record renewal success engagement log', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Safely dispatch database notification with error handling.
     */
    protected function safelyDeliverDatabaseNotification(User $user, object $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Database notification delivery failed', [
                'user_id' => $user->id,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Safely dispatch WhatsApp message if enabled with error handling.
     */
    protected function safelyDeliverWhatsAppMessage(
        User $user,
        string $templateSlug,
        array $context,
        string $idempotencyKey
    ): void {
        if (! config('whatsapp.enabled') || empty($user->phone)) {
            return;
        }

        try {
            $this->whatsAppService->sendTemplateMessage(
                $user,
                $templateSlug,
                $context,
                $idempotencyKey
            );
        } catch (Throwable $e) {
            Log::warning('WhatsApp message delivery failed', [
                'user_id' => $user->id,
                'template' => $templateSlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Safely dispatch transactional mail with error handling.
     */
    protected function safelyDeliverMail(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning('Transactional mail delivery exception caught in renewal service', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
