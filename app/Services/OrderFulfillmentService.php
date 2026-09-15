<?php

namespace App\Services;

use App\Enums\AutomationTrigger;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Authoritatively fulfill an order by creating or extending course enrollment access.
     *
     * Invariants:
     * - Strictly preserves single enrollment per user and course (unique: user_id + course_id).
     * - Records every granted purchase as a historical CourseAccessPeriod.
     * - Early renewal preserves remaining access (newStartsAt = existing expires_at).
     * - Post-expiry renewal starts immediately at current server time without gap backfill.
     * - Cancelled enrollments are never silently reactivated.
     * - Fully idempotent: re-processing an order never creates duplicate access periods or grants extra days.
     * - Completed course progress and certificates remain permanently untouched.
     */
    public function fulfillOrder(Order $order): void
    {
        $user = $order->user;

        if (! $user) {
            Log::error('Cannot fulfill order without associated user', ['order_id' => $order->id]);
            return;
        }

        if ($order->isBundleOrder() && $order->bundle) {
            $bundleCourses = $order->bundle->publishedCourses;

            foreach ($bundleCourses as $bCourse) {
                $period = $this->fulfillCourseAccess($user, $bCourse, $order);

                if ($period && $period->period_type === 'renewal') {
                    try {
                        app(CourseRenewalNotificationService::class)->sendRenewalSuccess($user, $bCourse, $period, $order);
                    } catch (\Throwable $e) {
                        Log::warning('Renewal notification failed during bundle fulfillment', [
                            'order_id' => $order->id,
                            'course_id' => $bCourse->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                app(EngagementService::class)->handleEnrollment($user, $bCourse, true);
            }

            app(MarketingAutomationService::class)->dispatchTrigger(
                AutomationTrigger::BUNDLE_PURCHASED,
                $user,
                ['bundle_id' => $order->bundle_id],
                'order_bundle_' . $order->id
            );
        } elseif ($order->course_id && $order->course) {
            $period = $this->fulfillCourseAccess($user, $order->course, $order);

            if ($period && $period->period_type === 'renewal') {
                app(\App\Services\ConversionTrackingService::class)->track(
                    \App\Enums\ConversionEventName::RENEWAL_FULFILLED,
                    [
                        'course_id' => $order->course_id,
                        'user_id' => $user->id,
                        'metadata' => [
                            'order_id' => $order->id,
                            'access_period_id' => $period->id,
                            'is_early_renewal' => $period->isEarlyRenewal(),
                            'starts_at' => $period->starts_at?->toISOString(),
                            'expires_at' => $period->expires_at?->toISOString(),
                            'amount' => $order->amount,
                        ],
                    ]
                );

                app(\App\Services\ConversionTrackingService::class)->track(
                    \App\Enums\ConversionEventName::COURSE_ACCESS_RENEWED,
                    [
                        'course_id' => $order->course_id,
                        'user_id' => $user->id,
                        'metadata' => [
                            'order_id' => $order->id,
                            'access_period_id' => $period->id,
                            'is_early_renewal' => $period->isEarlyRenewal(),
                            'starts_at' => $period->starts_at?->toISOString(),
                            'expires_at' => $period->expires_at?->toISOString(),
                        ],
                    ]
                );

                app(\App\Services\RenewalAnalyticsService::class)->logCrmRenewalActivity(
                    $user,
                    $order->course,
                    'course_renewed',
                    "Student renewed access to course: {$order->course->title}",
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => round(((int) $order->amount) / 100, 2),
                        'access_period_id' => $period->id,
                        'is_early_renewal' => $period->isEarlyRenewal(),
                        'starts_at' => $period->starts_at?->toIso8601String(),
                        'expires_at' => $period->expires_at?->toIso8601String(),
                    ]
                );

                try {
                    app(CourseRenewalNotificationService::class)->sendRenewalSuccess($user, $order->course, $period, $order);
                } catch (\Throwable $e) {
                    Log::warning('Renewal notification failed during fulfillment', [
                        'order_id' => $order->id,
                        'course_id' => $order->course_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            app(EngagementService::class)->handleEnrollment($user, $order->course, true);

            app(MarketingAutomationService::class)->dispatchTrigger(
                AutomationTrigger::COURSE_ENROLLED,
                $user,
                ['course_id' => $order->course_id],
                'order_course_' . $order->id
            );
        }

        $metadata = $order->metadata ?? [];
        if (! isset($metadata['fulfilled_at'])) {
            $metadata['fulfilled_at'] = now()->toISOString();
            $order->update(['metadata' => $metadata]);
        }
    }

    /**
     * Fulfill course access for a single course and order.
     */
    public function fulfillCourseAccess(User $user, Course $course, Order $order): ?CourseAccessPeriod
    {
        return DB::transaction(function () use ($user, $course, $order) {
            // Concurrency protection: lock existing enrollment record
            $enrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            // Safety Guard: Cancelled enrollments require administrative intervention
            if ($enrollment && $enrollment->status === EnrollmentStatus::CANCELLED) {
                Log::warning('Fulfillment skipped: user enrollment in course is cancelled', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ]);
                return null;
            }

            // Idempotency check: verify if an access period was already issued for this order
            if ($enrollment) {
                $existingPeriod = CourseAccessPeriod::query()
                    ->where('order_id', $order->id)
                    ->where('enrollment_id', $enrollment->id)
                    ->first();

                if ($existingPeriod) {
                    return $existingPeriod;
                }
            }

            $validityDays = $course->getAccessValidityDays();
            $now = now();

            if (! $enrollment) {
                // SCENARIO A: INITIAL PURCHASE
                $startsAt = $now;
                $expiresAt = $startsAt->copy()->addDays($validityDays);

                $enrollment = Enrollment::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'status' => EnrollmentStatus::ACTIVE,
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                        'enrolled_at' => $now,
                    ]
                );

                return CourseAccessPeriod::firstOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'order_id' => $order->id,
                    ],
                    [
                        'period_type' => 'initial',
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                    ]
                );
            }

            // SCENARIOS B & C: RENEWAL / EXTENSION
            $hasActiveUnexpiredAccess = $enrollment->starts_at !== null
                && $enrollment->expires_at !== null
                && $enrollment->expires_at->gt($now);

            if ($hasActiveUnexpiredAccess) {
                // SCENARIO B: EARLY RENEWAL
                // New period starts seamlessly when the current paid access expires
                $periodStartsAt = $enrollment->expires_at->copy();
                $periodExpiresAt = $periodStartsAt->copy()->addDays($validityDays);

                // Enrollment starts_at preserves current access without disruption
                $enrollmentStartsAt = min($enrollment->starts_at, $now);
                $enrollmentExpiresAt = $periodExpiresAt;
            } else {
                // SCENARIO C: POST-EXPIRY RENEWAL OR LEGACY LIFETIME TRANSITION
                // Access starts immediately at current server time
                $periodStartsAt = $now;
                $periodExpiresAt = $periodStartsAt->copy()->addDays($validityDays);

                $enrollmentStartsAt = $periodStartsAt;
                $enrollmentExpiresAt = $periodExpiresAt;
            }

            $accessPeriod = CourseAccessPeriod::firstOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'order_id' => $order->id,
                ],
                [
                    'period_type' => 'renewal',
                    'starts_at' => $periodStartsAt,
                    'expires_at' => $periodExpiresAt,
                ]
            );

            // Preserve completed status if course was already completed
            $targetStatus = ($enrollment->status === EnrollmentStatus::COMPLETED || $enrollment->completed_at !== null)
                ? EnrollmentStatus::COMPLETED
                : EnrollmentStatus::ACTIVE;

            $enrollment->update([
                'status' => $targetStatus,
                'starts_at' => $enrollmentStartsAt,
                'expires_at' => $enrollmentExpiresAt,
            ]);

            return $accessPeriod;
        });
    }
}