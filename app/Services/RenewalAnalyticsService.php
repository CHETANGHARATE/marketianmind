<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Models\ConversionEvent;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RenewalAnalyticsService
{
    /**
     * Compute Executive Renewal Summary KPIs.
     *
     * @return array<string, mixed>
     */
    public function getRenewalSummary(?Carbon $start = null, ?Carbon $end = null): array
    {
        // 1. Authoritative Paid Renewal Orders & Revenue (stored in paise, converted to INR)
        $ordersQuery = Order::query()
            ->where('status', OrderStatus::PAID->value)
            ->where(function ($q) {
                $q->where('metadata->purchase_type', 'renewal')
                  ->orWhereHas('accessPeriods', fn ($p) => $p->where('period_type', 'renewal'));
            });

        if ($start) {
            $ordersQuery->where(function ($q) use ($start) {
                $q->where('paid_at', '>=', $start)
                  ->orWhere(function ($sq) use ($start) {
                      $sq->whereNull('paid_at')->where('created_at', '>=', $start);
                  });
            });
        }
        if ($end) {
            $ordersQuery->where(function ($q) use ($end) {
                $q->where('paid_at', '<=', $end)
                  ->orWhere(function ($sq) use ($end) {
                      $sq->whereNull('paid_at')->where('created_at', '<=', $end);
                  });
            });
        }

        $renewalRevenuePaise = (int) $ordersQuery->sum('amount');
        $renewalRevenue = round($renewalRevenuePaise / 100, 2);
        $paidRenewalOrdersCount = $ordersQuery->count();

        // 2. Renewal Access Periods (Early vs Post-Expiry breakdown)
        $periodsQuery = CourseAccessPeriod::query()->where('period_type', 'renewal');
        if ($start) {
            $periodsQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $periodsQuery->where('created_at', '<=', $end);
        }

        $renewalPeriods = $periodsQuery->get();
        $totalRenewalsCount = max($paidRenewalOrdersCount, $renewalPeriods->count());

        $earlyRenewals = 0;
        $postExpiryRenewals = 0;

        foreach ($renewalPeriods as $period) {
            if ($period->isEarlyRenewal()) {
                $earlyRenewals++;
            } else {
                $postExpiryRenewals++;
            }
        }

        // 3. Finite vs Lifetime Student Counts
        $finiteEnrollments = Enrollment::query()
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->count();

        $lifetimeEnrollments = Enrollment::query()
            ->whereNull('starts_at')
            ->whereNull('expires_at')
            ->count();

        // 4. Expiry Cohort Status
        $now = now();
        $expiringSoon = Enrollment::query()
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $now->copy()->addDays(30))
            ->count();

        $expiredUnrenewed = Enrollment::query()
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->where('status', EnrollmentStatus::EXPIRED->value)
            ->count();

        // 5. Renewal Rate Calculation
        // Denominator: Finite enrollments that have reached expiration or renewed in the window
        // Numerator: Finite enrollments that completed at least one renewal
        if ($start && $end) {
            $denominator = Enrollment::query()
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [$start, $end])
                ->count();

            $numerator = CourseAccessPeriod::query()
                ->where('period_type', 'renewal')
                ->whereBetween('created_at', [$start, $end])
                ->distinct('enrollment_id')
                ->count('enrollment_id');
        } else {
            $denominator = Enrollment::query()
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->where(function ($q) use ($now) {
                    $q->where('expires_at', '<=', $now)
                      ->orWhereHas('accessPeriods', fn ($p) => $p->where('period_type', 'renewal'));
                })
                ->count();

            $numerator = CourseAccessPeriod::query()
                ->where('period_type', 'renewal')
                ->distinct('enrollment_id')
                ->count('enrollment_id');
        }

        $renewalRate = $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;

        return [
            'total_renewals' => $totalRenewalsCount,
            'paid_renewal_orders' => $paidRenewalOrdersCount,
            'renewal_revenue' => $renewalRevenue,
            'renewal_revenue_paise' => $renewalRevenuePaise,
            'formatted_revenue' => '₹' . number_format($renewalRevenue, 2),
            'early_renewals' => $earlyRenewals,
            'post_expiry_renewals' => $postExpiryRenewals,
            'finite_students' => $finiteEnrollments,
            'lifetime_students' => $lifetimeEnrollments,
            'expiring_soon' => $expiringSoon,
            'expired_unrenewed' => $expiredUnrenewed,
            'renewal_rate' => $renewalRate,
            'cohort_numerator' => $numerator,
            'cohort_denominator' => $denominator,
        ];
    }

    /**
     * Compute per-course renewal metrics breakdown.
     */
    public function getCourseRenewalMetrics(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $courses = Course::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'price', 'is_free', 'access_validity_days']);

        return $courses->map(function ($course) use ($start, $end) {
            // Finite enrollments for this course
            $finiteCount = Enrollment::query()
                ->where('course_id', $course->id)
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->count();

            // Expiring in window
            $expiringQuery = Enrollment::query()
                ->where('course_id', $course->id)
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at');

            if ($start && $end) {
                $expiringQuery->whereBetween('expires_at', [$start, $end]);
            } else {
                $expiringQuery->where('expires_at', '<=', now()->addDays(30));
            }
            $expiringCount = $expiringQuery->count();

            // Paid renewal orders for this course
            $ordersQuery = Order::query()
                ->where('course_id', $course->id)
                ->where('status', OrderStatus::PAID->value)
                ->where(function ($q) {
                    $q->where('metadata->purchase_type', 'renewal')
                      ->orWhereHas('accessPeriods', fn ($p) => $p->where('period_type', 'renewal'));
                });

            if ($start) {
                $ordersQuery->where('created_at', '>=', $start);
            }
            if ($end) {
                $ordersQuery->where('created_at', '<=', $end);
            }

            $revenuePaise = (int) $ordersQuery->sum('amount');
            $revenue = round($revenuePaise / 100, 2);

            // Access periods for this course
            $periodsQuery = CourseAccessPeriod::query()
                ->where('period_type', 'renewal')
                ->whereHas('enrollment', fn ($q) => $q->where('course_id', $course->id));

            if ($start) {
                $periodsQuery->where('created_at', '>=', $start);
            }
            if ($end) {
                $periodsQuery->where('created_at', '<=', $end);
            }

            $periods = $periodsQuery->get();
            $renewalsCount = max($ordersQuery->count(), $periods->count());

            $earlyRenewals = 0;
            $postExpiryRenewals = 0;
            foreach ($periods as $p) {
                if ($p->isEarlyRenewal()) {
                    $earlyRenewals++;
                } else {
                    $postExpiryRenewals++;
                }
            }

            $denominator = max($expiringCount, $renewalsCount);
            $renewalRate = $denominator > 0 ? round(($renewalsCount / $denominator) * 100, 1) : 0.0;

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'price' => $course->price,
                'finite_enrollments' => $finiteCount,
                'expiring_count' => $expiringCount,
                'renewals_count' => $renewalsCount,
                'early_renewals' => $earlyRenewals,
                'post_expiry_renewals' => $postExpiryRenewals,
                'renewal_revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
                'renewal_rate' => $renewalRate,
            ];
        });
    }

    /**
     * Compute Expiry Cohort Analysis grouped by month of expiration.
     *
     * Invariants:
     * - Only includes finite enrollments (starts_at IS NOT NULL AND expires_at IS NOT NULL).
     * - Legacy lifetime enrollments are strictly excluded.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExpiryCohortReport(int $months = 6): array
    {
        $cohorts = [];
        $now = now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $startOfMonth = $monthDate->copy()->startOfMonth();
            $endOfMonth = $monthDate->copy()->endOfMonth();
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = $monthDate->format('M Y');

            // Denominator: finite enrollments that reached expiry in this month
            $expiringEnrollmentIds = Enrollment::query()
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [$startOfMonth, $endOfMonth])
                ->pluck('id');

            $cohortSize = $expiringEnrollmentIds->count();

            // Numerator: how many of those enrollments have a renewal access period
            $renewedCount = 0;
            $renewalRevenuePaise = 0;

            if ($cohortSize > 0) {
                $renewedEnrollmentIds = CourseAccessPeriod::query()
                    ->whereIn('enrollment_id', $expiringEnrollmentIds)
                    ->where('period_type', 'renewal')
                    ->distinct('enrollment_id')
                    ->pluck('enrollment_id');

                $renewedCount = $renewedEnrollmentIds->count();

                $renewalRevenuePaise = (int) Order::query()
                    ->where('status', OrderStatus::PAID->value)
                    ->whereHas('accessPeriods', function ($q) use ($expiringEnrollmentIds) {
                        $q->whereIn('enrollment_id', $expiringEnrollmentIds)
                          ->where('period_type', 'renewal');
                    })
                    ->sum('amount');
            }

            $renewalRate = $cohortSize > 0 ? round(($renewedCount / $cohortSize) * 100, 1) : 0.0;
            $revenue = round($renewalRevenuePaise / 100, 2);

            $cohorts[] = [
                'month_key' => $monthKey,
                'month_label' => $monthLabel,
                'cohort_size' => $cohortSize,
                'renewals_count' => $renewedCount,
                'renewal_rate' => $renewalRate,
                'renewal_revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
            ];
        }

        return $cohorts;
    }

    /**
     * Compute renewal latency metrics (average early lead time and post-expiry delay).
     *
     * @return array{avg_early_lead_days: float, avg_post_expiry_delay_days: float, early_count: int, post_expiry_count: int}
     */
    public function getRenewalLatencyMetrics(?Carbon $start = null, ?Carbon $end = null): array
    {
        $query = CourseAccessPeriod::query()->where('period_type', 'renewal');
        if ($start) {
            $query->where('created_at', '>=', $start);
        }
        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        $periods = $query->get();

        $earlyDays = [];
        $postExpiryDays = [];

        foreach ($periods as $period) {
            $days = $period->getLeadOrDelayDays();
            if ($period->isEarlyRenewal()) {
                $earlyDays[] = $days;
            } else {
                $postExpiryDays[] = $days;
            }
        }

        $avgEarly = count($earlyDays) > 0 ? round(array_sum($earlyDays) / count($earlyDays), 1) : 0.0;
        $avgDelay = count($postExpiryDays) > 0 ? round(array_sum($postExpiryDays) / count($postExpiryDays), 1) : 0.0;

        return [
            'avg_early_lead_days' => $avgEarly,
            'avg_post_expiry_delay_days' => $avgDelay,
            'early_count' => count($earlyDays),
            'post_expiry_count' => count($postExpiryDays),
        ];
    }

    /**
     * Compute renewal conversion funnel metrics.
     *
     * @return array<string, mixed>
     */
    public function getRenewalFunnel(?Carbon $start = null, ?Carbon $end = null): array
    {
        // 1. Eligible cohort (finite access expiring/expired)
        $expiringQuery = Enrollment::query()
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at');
        if ($start && $end) {
            $expiringQuery->whereBetween('expires_at', [$start, $end]);
        } else {
            $expiringQuery->where('expires_at', '<=', now()->addDays(30));
        }
        $eligibleCount = $expiringQuery->count();

        // 2. Renewal notifications sent
        $notifQuery = ConversionEvent::query()->where('event_name', 'renewal_notification_sent');
        if ($start) {
            $notifQuery->where('occurred_at', '>=', $start);
        }
        if ($end) {
            $notifQuery->where('occurred_at', '<=', $end);
        }
        $notificationsSent = $notifQuery->count();

        // 3. Renewal checkouts started
        $checkoutQuery = ConversionEvent::query()->where('event_name', 'renewal_checkout_started');
        if ($start) {
            $checkoutQuery->where('occurred_at', '>=', $start);
        }
        if ($end) {
            $checkoutQuery->where('occurred_at', '<=', $end);
        }
        $checkoutsStarted = $checkoutQuery->count();

        // 4. Renewal payments initiated
        $paymentInitQuery = ConversionEvent::query()->where('event_name', 'renewal_payment_initiated');
        if ($start) {
            $paymentInitQuery->where('occurred_at', '>=', $start);
        }
        if ($end) {
            $paymentInitQuery->where('occurred_at', '<=', $end);
        }
        $paymentsInitiated = $paymentInitQuery->count();

        // 5. Renewal payment success
        $paidOrdersQuery = Order::query()
            ->where('status', OrderStatus::PAID->value)
            ->where(function ($q) {
                $q->where('metadata->purchase_type', 'renewal')
                  ->orWhereHas('accessPeriods', fn ($p) => $p->where('period_type', 'renewal'));
            });
        if ($start) {
            $paidOrdersQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $paidOrdersQuery->where('created_at', '<=', $end);
        }
        $paymentsSuccess = $paidOrdersQuery->count();

        // 6. Renewal fulfilled
        $fulfilledPeriodsQuery = CourseAccessPeriod::query()->where('period_type', 'renewal');
        if ($start) {
            $fulfilledPeriodsQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $fulfilledPeriodsQuery->where('created_at', '<=', $end);
        }
        $renewalsFulfilled = $fulfilledPeriodsQuery->count();

        return [
            'eligible' => $eligibleCount,
            'notifications_sent' => $notificationsSent,
            'checkout_started' => $checkoutsStarted,
            'payments_initiated' => $paymentsInitiated,
            'payment_success' => $paymentsSuccess,
            'fulfilled' => $renewalsFulfilled,
            'checkout_conversion_rate' => $checkoutsStarted > 0 ? round(($paymentsSuccess / $checkoutsStarted) * 100, 1) : 0.0,
            'overall_conversion_rate' => $eligibleCount > 0 ? round(($renewalsFulfilled / $eligibleCount) * 100, 1) : 0.0,
        ];
    }

    /**
     * Get recent renewal activity stream.
     */
    public function getRecentRenewalActivity(int $limit = 10): Collection
    {
        return CourseAccessPeriod::query()
            ->where('period_type', 'renewal')
            ->with(['enrollment.user:id,name,email', 'enrollment.course:id,title,slug', 'order:id,order_number,amount,status,paid_at'])
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function ($period) {
                $user = $period->enrollment?->user;
                $course = $period->enrollment?->course;
                $order = $period->order;

                $amount = $order ? round(((int) $order->amount) / 100, 2) : 0.0;
                $isEarly = $period->isEarlyRenewal();

                return [
                    'id' => $period->id,
                    'student_name' => $user?->name ?? 'Student',
                    'student_email' => $user?->email ?? 'N/A',
                    'course_title' => $course?->title ?? 'Course',
                    'course_slug' => $course?->slug ?? '',
                    'order_number' => $order?->order_number ?? 'N/A',
                    'amount' => $amount,
                    'formatted_amount' => '₹' . number_format($amount, 2),
                    'is_early' => $isEarly,
                    'type_label' => $isEarly ? 'Early Renewal' : 'Post-Expiry Renewal',
                    'starts_at' => $period->starts_at?->format('M d, Y') ?? 'N/A',
                    'expires_at' => $period->expires_at?->format('M d, Y') ?? 'N/A',
                    'created_at' => $period->created_at?->format('M d, Y H:i') ?? 'N/A',
                ];
            });
    }

    /**
     * Log renewal lifecycle activity to any CRM Lead records matched with the user.
     *
     * Invariants:
     * - Never mutates Lead status or overwrites sales pipeline stages.
     * - Appends cleanly to lead activity history.
     */
    public function logCrmRenewalActivity(
        User $user,
        Course $course,
        string $activityType,
        string $description,
        array $properties = []
    ): void {
        try {
            $leads = Lead::query()
                ->where('converted_user_id', $user->id)
                ->orWhere('email', $user->email)
                ->get();

            foreach ($leads as $lead) {
                $payload = array_merge([
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'user_id' => $user->id,
                    'timestamp' => now()->toIso8601String(),
                ], $properties);

                $lead->recordActivity(
                    $activityType,
                    $description,
                    $payload,
                    $user
                );
            }
        } catch (Throwable) {
            // Fail-safe CRM activity logging isolation
        }
    }

    /**
     * Stream CSV export for renewal transactions.
     */
    public function streamRenewalsCsv(?Carbon $start = null, ?Carbon $end = null): StreamedResponse
    {
        $filename = 'renewals-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Period ID',
                'Enrollment ID',
                'Student Name',
                'Student Email',
                'Course Title',
                'Order Number',
                'Amount (INR)',
                'Renewal Type',
                'Starts At',
                'Expires At',
                'Fulfilled At',
            ]);

            $query = CourseAccessPeriod::query()
                ->where('period_type', 'renewal')
                ->with(['enrollment.user:id,name,email', 'enrollment.course:id,title', 'order:id,order_number,amount']);

            if ($start) {
                $query->where('created_at', '>=', $start);
            }
            if ($end) {
                $query->where('created_at', '<=', $end);
            }

            $query->orderByDesc('id')->chunk(100, function ($periods) use ($handle) {
                foreach ($periods as $p) {
                    $user = $p->enrollment?->user;
                    $course = $p->enrollment?->course;
                    $order = $p->order;
                    $amount = $order ? round(((int) $order->amount) / 100, 2) : 0.0;
                    $isEarly = $p->isEarlyRenewal();

                    fputcsv($handle, [
                        $p->id,
                        $p->enrollment_id,
                        $user?->name ?? 'N/A',
                        $user?->email ?? 'N/A',
                        $course?->title ?? 'N/A',
                        $order?->order_number ?? 'N/A',
                        $amount,
                        $isEarly ? 'Early Renewal' : 'Post-Expiry Renewal',
                        $p->starts_at ? $p->starts_at->toDateTimeString() : 'N/A',
                        $p->expires_at ? $p->expires_at->toDateTimeString() : 'N/A',
                        $p->created_at ? $p->created_at->toDateTimeString() : 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
