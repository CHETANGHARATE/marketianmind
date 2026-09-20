<?php

namespace App\Services;

use App\Enums\CustomerLifecycleStage;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\StudentLearningDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerLifecycleService
{
    /**
     * Authoritatively resolve a student user's current customer lifecycle stage.
     */
    public function resolveLifecycleStage(User $user): CustomerLifecycleStage
    {
        // 1. Returning Customer: >= 2 paid orders or has completed a renewal order
        $paidOrdersCount = $user->orders()->where('status', OrderStatus::PAID->value)->count();
        $hasRenewalOrder = $user->orders()
            ->where('status', OrderStatus::PAID->value)
            ->whereNotNull('course_id')
            ->whereJsonContains('metadata->purchase_type', 'renewal')
            ->exists();

        if ($paidOrdersCount >= 2 || $hasRenewalOrder) {
            return CustomerLifecycleStage::RETURNING_CUSTOMER;
        }

        // 2. Course Completer: completed 100% of at least one course or earned a certificate
        $hasCompletedCourse = $user->enrollments()->where('status', EnrollmentStatus::COMPLETED->value)->exists()
            || $user->certificates()->exists();

        if ($hasCompletedCourse) {
            return CustomerLifecycleStage::COURSE_COMPLETER;
        }

        // Check active course access
        $activeEnrollments = $user->enrollments()
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        $hasActiveAccess = $activeEnrollments->isNotEmpty();

        if ($hasActiveAccess) {
            // 3. Access Expiring: active access ends within 30 days and no other access with >30 days
            $maxExpiry = $activeEnrollments->max('expires_at');
            if ($maxExpiry && Carbon::parse($maxExpiry)->lte(now()->addDays(30))) {
                return CustomerLifecycleStage::ACCESS_EXPIRING;
            }

            // 4. Engaged Learner: has recorded learning activity within the last 30 days
            $recentLessonActivity = $user->lessonProgress()
                ->where('updated_at', '>=', now()->subDays(30))
                ->exists();

            $recentLearningDay = StudentLearningDay::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if ($recentLessonActivity || $recentLearningDay) {
                return CustomerLifecycleStage::ENGAGED_LEARNER;
            }

            // 5. First-Time Buyer: exactly 1 paid order, but has not completed any lessons yet
            $completedLessonsCount = $user->lessonProgress()->where('completed', true)->count();
            if ($paidOrdersCount === 1 && $completedLessonsCount === 0) {
                return CustomerLifecycleStage::FIRST_TIME_BUYER;
            }

            // 6. Active Student: has valid access
            return CustomerLifecycleStage::ACTIVE_STUDENT;
        }

        // 7. Expired Student: past enrollments exist, but all access periods have expired
        $totalEnrollmentsCount = $user->enrollments()->count();
        if ($totalEnrollmentsCount > 0) {
            return CustomerLifecycleStage::EXPIRED_STUDENT;
        }

        // 8. Interested Prospect: registered user without purchases or enrollments
        return CustomerLifecycleStage::INTERESTED_PROSPECT;
    }

    /**
     * Resolve lifecycle stage for an inbound CRM lead.
     */
    public function resolveLeadLifecycleStage(Lead $lead): CustomerLifecycleStage
    {
        if ($lead->isConverted() && $lead->convertedUser) {
            return $this->resolveLifecycleStage($lead->convertedUser);
        }

        $matchedUser = $lead->matchedUser();
        if ($matchedUser) {
            return $this->resolveLifecycleStage($matchedUser);
        }

        if (in_array($lead->status, [LeadStatus::QUALIFIED, LeadStatus::INTERESTED], true)) {
            return CustomerLifecycleStage::INTERESTED_PROSPECT;
        }

        return CustomerLifecycleStage::LEAD;
    }

    /**
     * Resolve lifecycle stage for a specific user-course pairing.
     */
    public function resolveCourseLifecycleStage(User $user, Course $course): CustomerLifecycleStage
    {
        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();

        if ($enrollment) {
            if ($enrollment->isCompleted() || $user->certificates()->where('course_id', $course->id)->exists()) {
                return CustomerLifecycleStage::COURSE_COMPLETER;
            }

            if ($enrollment->hasActiveAccess()) {
                if ($enrollment->isExpiringSoon(30)) {
                    return CustomerLifecycleStage::ACCESS_EXPIRING;
                }

                $hasRecentActivity = $user->lessonProgress()
                    ->whereHas('lesson', fn($q) => $q->where('course_id', $course->id))
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->exists();

                if ($hasRecentActivity) {
                    return CustomerLifecycleStage::ENGAGED_LEARNER;
                }

                return CustomerLifecycleStage::ACTIVE_STUDENT;
            }

            return CustomerLifecycleStage::EXPIRED_STUDENT;
        }

        $hasPaidOrder = $user->orders()
            ->where('course_id', $course->id)
            ->where('status', OrderStatus::PAID->value)
            ->exists();

        if ($hasPaidOrder) {
            return CustomerLifecycleStage::FIRST_TIME_BUYER;
        }

        return CustomerLifecycleStage::INTERESTED_PROSPECT;
    }

    /**
     * Get aggregate lifecycle stage distribution across all registered students.
     */
    public function getLifecycleDistribution(): array
    {
        $students = User::where('role', \App\Enums\UserRole::STUDENT->value)
            ->with(['enrollments', 'orders', 'certificates'])
            ->get();

        $distribution = [];
        foreach (CustomerLifecycleStage::cases() as $case) {
            $distribution[$case->value] = 0;
        }

        // Add inbound unconverted leads count
        $distribution[CustomerLifecycleStage::LEAD->value] = Lead::whereNull('converted_user_id')
            ->whereNotIn('status', [LeadStatus::CONVERTED->value, LeadStatus::LOST->value, LeadStatus::CLOSED->value])
            ->count();

        foreach ($students as $student) {
            $stage = $this->resolveLifecycleStage($student);
            $distribution[$stage->value]++;
        }

        return $distribution;
    }

    /**
     * Generate a consolidated chronological lifecycle relationship timeline for a student.
     */
    public function getLifecycleAuditHistory(User $user): Collection
    {
        $timeline = collect();

        // 1. Inbound Leads / Inquiries
        $leads = Lead::where('email', strtolower($user->email))
            ->orWhere('converted_user_id', $user->id)
            ->get();

        foreach ($leads as $lead) {
            $timeline->push([
                'type' => 'inquiry',
                'title' => 'Inbound Lead Captured',
                'description' => "Captured from source '{$lead->source}': " . ($lead->subject ?: 'Course Inquiry'),
                'timestamp' => $lead->created_at,
                'metadata' => ['lead_id' => $lead->id, 'source' => $lead->source],
                'badge' => 'Lead Inquiry',
                'badge_classes' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            ]);
        }

        // 2. Account Registration
        $timeline->push([
            'type' => 'registration',
            'title' => 'Student Account Registered',
            'description' => "Account created and verified on Marketian Mind ({$user->email}).",
            'timestamp' => $user->created_at,
            'metadata' => ['user_id' => $user->id],
            'badge' => 'Registered',
            'badge_classes' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        ]);

        // 3. Orders & Purchases
        $orders = $user->orders()->with(['course', 'bundle'])->get();
        foreach ($orders as $order) {
            $isPaid = $order->status === OrderStatus::PAID;
            $timeline->push([
                'type' => 'order',
                'title' => $isPaid ? 'Course Purchase Completed' : 'Order Placed (' . $order->status->label() . ')',
                'description' => "Order #{$order->order_number} for {$order->productTitle()} ({$order->formattedAmount()}).",
                'timestamp' => $order->created_at,
                'metadata' => ['order_id' => $order->id, 'amount' => $order->amount, 'status' => $order->status->value],
                'badge' => $order->status->label(),
                'badge_classes' => $order->status->badgeClasses(),
            ]);
        }

        // 4. Enrollments & Access Periods
        $enrollments = $user->enrollments()->with(['course', 'accessPeriods'])->get();
        foreach ($enrollments as $enrollment) {
            $courseTitle = $enrollment->course?->title ?? 'Course #' . $enrollment->course_id;

            $timeline->push([
                'type' => 'enrollment',
                'title' => 'Enrolled in ' . $courseTitle,
                'description' => "Granted 365-day access starting {$enrollment->starts_at?->format('M d, Y')}, valid until {$enrollment->expires_at?->format('M d, Y')}.",
                'timestamp' => $enrollment->enrolled_at ?? $enrollment->created_at,
                'metadata' => ['enrollment_id' => $enrollment->id, 'status' => $enrollment->status->value],
                'badge' => $enrollment->status->label(),
                'badge_classes' => $enrollment->status->badgeClasses(),
            ]);

            // Additional renewal periods
            foreach ($enrollment->accessPeriods as $period) {
                if ($period->period_type === 'renewal') {
                    $timeline->push([
                        'type' => 'renewal',
                        'title' => 'Access Period Extended: ' . $courseTitle,
                        'description' => "Renewed validity stacked from {$period->starts_at?->format('M d, Y')} through {$period->expires_at?->format('M d, Y')}.",
                        'timestamp' => $period->created_at,
                        'metadata' => ['period_id' => $period->id],
                        'badge' => 'Renewed Access',
                        'badge_classes' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                    ]);
                }
            }
        }

        // 5. Certificates
        $certificates = $user->certificates()->with('course')->get();
        foreach ($certificates as $cert) {
            $timeline->push([
                'type' => 'certificate',
                'title' => 'Certificate Issued: ' . ($cert->course?->title ?? 'Course'),
                'description' => "Awarded certificate #{$cert->certificate_number} upon 100% course completion.",
                'timestamp' => $cert->issued_at ?? $cert->created_at,
                'metadata' => ['certificate_number' => $cert->certificate_number],
                'badge' => 'Course Completer',
                'badge_classes' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            ]);
        }

        return $timeline->sortByDesc('timestamp')->values();
    }

    /**
     * Apply an efficient lifecycle stage filter to a User query.
     */
    public function applyLifecycleFilter(Builder $query, string $stage): Builder
    {
        return match ($stage) {
            CustomerLifecycleStage::RETURNING_CUSTOMER->value => $query->whereHas('orders', function ($q) {
                $q->where('status', OrderStatus::PAID->value);
            }, '>=', 2),

            CustomerLifecycleStage::COURSE_COMPLETER->value => $query->where(function ($q) {
                $q->whereHas('enrollments', fn($sub) => $sub->where('status', EnrollmentStatus::COMPLETED->value))
                    ->orWhereHas('certificates');
            }),

            CustomerLifecycleStage::ACCESS_EXPIRING->value => $query->whereHas('enrollments', function ($q) {
                $q->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays(30));
            }),

            CustomerLifecycleStage::ENGAGED_LEARNER->value => $query->whereHas('enrollments', function ($q) {
                $q->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                    ->where(fn($sub) => $sub->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            })->whereHas('lessonProgress', function ($q) {
                $q->where('updated_at', '>=', now()->subDays(30));
            }),

            CustomerLifecycleStage::ACTIVE_STUDENT->value => $query->whereHas('enrollments', function ($q) {
                $q->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                    ->where(fn($sub) => $sub->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            }),

            CustomerLifecycleStage::EXPIRED_STUDENT->value => $query->whereHas('enrollments')
                ->whereDoesntHave('enrollments', function ($q) {
                    $q->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                        ->where(fn($sub) => $sub->whereNull('expires_at')->orWhere('expires_at', '>', now()));
                }),

            CustomerLifecycleStage::FIRST_TIME_BUYER->value => $query->whereHas('orders', function ($q) {
                $q->where('status', OrderStatus::PAID->value);
            }, '=', 1),

            CustomerLifecycleStage::INTERESTED_PROSPECT->value => $query->doesntHave('orders')
                ->doesntHave('enrollments'),

            default => $query,
        };
    }
}
