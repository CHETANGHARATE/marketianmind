<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminCourseAccessService
{
    /**
     * Grant or extend course access for a student enrollment.
     *
     * Invariants & Guarantees:
     * 1. DB transaction with pessimistic locking on the enrollment record.
     * 2. Strict protection for legacy lifetime enrollments (denies modification).
     * 3. Continuous extension for active enrollments (starts at current expires_at).
     * 4. Fresh grant starting at current time for expired or non-continuous enrollments.
     * 5. Preserves curriculum progress, completion state, and existing certificates.
     * 6. Creates a CourseAccessPeriod with period_type = 'admin_grant' and order_id = null.
     * 7. Comprehensive audit trail logged with old and new values, admin reason, and actor.
     * 8. Idempotency protection against rapid double-clicks.
     *
     * @param Enrollment $enrollment The target enrollment model
     * @param int $days Number of days to grant or extend (1 to 3650)
     * @param string $reason Mandatory administrative justification (5 to 500 chars)
     * @param User|null $admin The administrator executing the change
     * @param string|null $idempotencyKey Optional unique submission token
     * @return CourseAccessPeriod The newly created access period
     *
     * @throws InvalidArgumentException When days or reason fail validation
     * @throws DomainException When attempting to alter a legacy lifetime enrollment
     */
    public function grantOrExtendAccess(
        Enrollment $enrollment,
        int $days,
        string $reason,
        ?User $admin = null,
        ?string $idempotencyKey = null
    ): CourseAccessPeriod {
        $trimmedReason = trim($reason);

        if ($days < 1 || $days > 3650) {
            throw new InvalidArgumentException('Access extension duration must be between 1 and 3650 days.');
        }

        if (mb_strlen($trimmedReason) < 5) {
            throw new InvalidArgumentException('An administrative reason of at least 5 characters is required.');
        }

        if (mb_strlen($trimmedReason) > 500) {
            throw new InvalidArgumentException('Administrative reason cannot exceed 500 characters.');
        }

        // Idempotency token check
        if (! empty($idempotencyKey)) {
            $cachedPeriodId = Cache::get("admin_access_idempotency:{$idempotencyKey}");
            if ($cachedPeriodId) {
                $existing = CourseAccessPeriod::find($cachedPeriodId);
                if ($existing) {
                    return $existing;
                }
            }
        }

        return DB::transaction(function () use ($enrollment, $days, $trimmedReason, $admin, $idempotencyKey) {
            // Lock enrollment record for update
            /** @var Enrollment $lockedEnrollment */
            $lockedEnrollment = Enrollment::with(['user', 'course'])
                ->where('id', $enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Safeguard legacy lifetime enrollments against accidental alteration
            if ($lockedEnrollment->isLegacyLifetimeAccess()) {
                throw new DomainException(
                    'Cannot modify or limit access for legacy lifetime enrollments. Lifetime access is protected.'
                );
            }

            // Rapid double-click protection (same enrollment, admin grant, within 10 seconds)
            $recentPeriod = CourseAccessPeriod::where('enrollment_id', $lockedEnrollment->id)
                ->where('period_type', 'admin_grant')
                ->where('created_at', '>=', now()->subSeconds(10))
                ->latest('id')
                ->first();

            if ($recentPeriod) {
                return $recentPeriod;
            }

            $oldValues = [
                'status' => $lockedEnrollment->status?->value ?? $lockedEnrollment->status,
                'starts_at' => $lockedEnrollment->starts_at?->toIso8601String(),
                'expires_at' => $lockedEnrollment->expires_at?->toIso8601String(),
            ];

            $now = now();
            $hasActiveUnexpiredAccess = $lockedEnrollment->hasActiveAccess()
                && $lockedEnrollment->expires_at !== null
                && $lockedEnrollment->expires_at->isFuture();

            if ($hasActiveUnexpiredAccess) {
                // Continuous extension: starts seamlessly from the current expiration date
                $periodStartsAt = $lockedEnrollment->expires_at->copy();
                $periodExpiresAt = $periodStartsAt->copy()->addDays($days);

                $enrollmentStartsAt = $lockedEnrollment->starts_at ?? $now;
                $enrollmentExpiresAt = $periodExpiresAt;
            } else {
                // Post-expiry or fresh access grant starting immediately
                $periodStartsAt = $now;
                $periodExpiresAt = $periodStartsAt->copy()->addDays($days);

                $enrollmentStartsAt = $periodStartsAt;
                $enrollmentExpiresAt = $periodExpiresAt;
            }

            // Create explicit CourseAccessPeriod record
            $accessPeriod = CourseAccessPeriod::create([
                'enrollment_id' => $lockedEnrollment->id,
                'order_id' => null, // Null indicates administrative grant, never forge order/payment
                'period_type' => 'admin_grant',
                'starts_at' => $periodStartsAt,
                'expires_at' => $periodExpiresAt,
            ]);

            // Target status: preserve completed status if already completed
            $isAlreadyCompleted = ($lockedEnrollment->status === EnrollmentStatus::COMPLETED)
                || ($lockedEnrollment->completed_at !== null);

            $targetStatus = $isAlreadyCompleted ? EnrollmentStatus::COMPLETED : EnrollmentStatus::ACTIVE;

            $lockedEnrollment->update([
                'status' => $targetStatus,
                'starts_at' => $enrollmentStartsAt,
                'expires_at' => $enrollmentExpiresAt,
            ]);

            // Cache idempotency token if provided
            if (! empty($idempotencyKey)) {
                Cache::put("admin_access_idempotency:{$idempotencyKey}", $accessPeriod->id, now()->addMinutes(15));
            }

            // Record audit log
            $studentName = $lockedEnrollment->user?->name ?? "Student #{$lockedEnrollment->user_id}";
            $courseTitle = $lockedEnrollment->course?->title ?? "Course #{$lockedEnrollment->course_id}";

            AuditLogger::log(
                action: 'updated',
                auditable: $lockedEnrollment,
                description: "Admin granted/extended {$days} days of course access. Reason: {$trimmedReason}",
                oldValues: $oldValues,
                newValues: [
                    'status' => $lockedEnrollment->status?->value ?? $lockedEnrollment->status,
                    'starts_at' => $lockedEnrollment->starts_at?->toIso8601String(),
                    'expires_at' => $lockedEnrollment->expires_at?->toIso8601String(),
                    'access_period_id' => $accessPeriod->id,
                    'period_type' => 'admin_grant',
                    'days_added' => $days,
                    'reason' => $trimmedReason,
                ],
                actor: $admin ?? auth()->user(),
                resourceLabel: "Enrollment #{$lockedEnrollment->id} ({$studentName} - {$courseTitle})"
            );

            return $accessPeriod;
        });
    }
}
