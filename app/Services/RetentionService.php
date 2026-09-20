<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\EngagementLog;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\MarketingUnsubscribe;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Notifications\StudentLearningSupportNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RetentionService
{
    public const SUPPORT_KICKSTART = 'kickstart';
    public const SUPPORT_REENGAGEMENT = 'reengagement';
    public const SUPPORT_COMPLETION_PUSH = 'completion_push';
    public const SUPPORT_RENEWAL_REMINDER = 'renewal_reminder';

    public function __construct(
        protected TransactionalMailService $mailService,
        protected RenewalAnalyticsService $renewalAnalyticsService
    ) {}

    /**
     * Compute comprehensive student retention and learning support summary KPIs.
     *
     * @return array<string, mixed>
     */
    public function getRetentionSummary(): array
    {
        $now = now();
        $threeDaysAgo = $now->copy()->subDays(3);
        $fourteenDaysAgo = $now->copy()->subDays(14);
        $thirtyDaysAhead = $now->copy()->addDays(30);

        // 1. Total Enrolled Students & Enrollments
        $totalEnrolledStudents = User::query()
            ->where('role', UserRole::STUDENT->value)
            ->whereHas('enrollments')
            ->count();

        $totalEnrollments = Enrollment::count();
        $activeEnrollmentsCount = Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count();
        $completedEnrollmentsCount = Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count();

        // 2. Active Learners in last 14 days (qualifying learning activity)
        $activeLearners14d = User::query()
            ->where('role', UserRole::STUDENT->value)
            ->where(function ($q) use ($fourteenDaysAgo) {
                $q->whereHas('learningDays', fn ($sq) => $sq->where('activity_date', '>=', $fourteenDaysAgo->toDateString()))
                  ->orWhereHas('lessonProgress', fn ($sq) => $sq->where('completed_at', '>=', $fourteenDaysAgo));
            })
            ->count();

        // 3. Not Started: Enrolled >= 3 days ago, 0 lessons completed
        $notStartedCount = Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->where('enrolled_at', '<=', $threeDaysAgo)
            ->whereDoesntHave('user.lessonProgress', function ($q) {
                $q->where('completed', true)
                  ->whereHas('lesson.module', function ($mq) {
                      $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                  });
            })
            ->count();

        // 4. Inactive Learners: Has >= 1 completed lesson, but no activity in last 14 days
        $inactiveLearnersCount = Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->whereHas('user.lessonProgress', function ($q) {
                $q->where('completed', true)
                  ->whereHas('lesson.module', function ($mq) {
                      $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                  });
            })
            ->whereDoesntHave('user.lessonProgress', function ($q) use ($fourteenDaysAgo) {
                $q->where('completed_at', '>=', $fourteenDaysAgo)
                  ->whereHas('lesson.module', function ($mq) {
                      $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                  });
            })
            ->count();

        // 5. Approaching Completion: >= 80% and < 100% completed
        // Sample in-progress active enrollments to determine count
        $approachingCompletionCount = 0;
        Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->with(['course', 'user'])
            ->chunk(100, function ($enrollments) use (&$approachingCompletionCount) {
                foreach ($enrollments as $enrollment) {
                    if (! $enrollment->user || ! $enrollment->course) {
                        continue;
                    }
                    $progress = $enrollment->course->progressFor($enrollment->user);
                    if ($progress['percentage'] >= 80 && $progress['percentage'] < 100) {
                        $approachingCompletionCount++;
                    }
                }
            });

        // 6. Expiring Soon: Active access expiring in <= 30 days
        $expiringSoonCount = Enrollment::query()
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $thirtyDaysAhead)
            ->count();

        // 7. Expired Access Count
        $expiredCount = Enrollment::query()
            ->where(function ($q) use ($now) {
                $q->where('status', EnrollmentStatus::EXPIRED->value)
                  ->orWhere(function ($sq) use ($now) {
                      $sq->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now);
                  });
            })
            ->count();

        // 8. Renewed Students Count
        $renewedEnrollmentsCount = CourseAccessPeriod::query()
            ->where('period_type', 'renewal')
            ->distinct('enrollment_id')
            ->count('enrollment_id');

        // 9. Health & Retention Rates
        $retentionRate = $activeEnrollmentsCount > 0
            ? round(($activeLearners14d / max(1, $activeEnrollmentsCount)) * 100, 1)
            : 0.0;

        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollmentsCount / $totalEnrollments) * 100, 1)
            : 0.0;

        $renewalSummary = $this->renewalAnalyticsService->getRenewalSummary();

        return [
            'total_enrolled_students' => $totalEnrolledStudents,
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollmentsCount,
            'active_learners_14d' => $activeLearners14d,
            'not_started_count' => $notStartedCount,
            'inactive_learners_count' => $inactiveLearnersCount,
            'approaching_completion_count' => $approachingCompletionCount,
            'completed_count' => $completedEnrollmentsCount,
            'expiring_soon_count' => $expiringSoonCount,
            'expired_count' => $expiredCount,
            'renewed_count' => $renewedEnrollmentsCount,
            'retention_rate' => $retentionRate,
            'completion_rate' => $completionRate,
            'renewal_rate' => $renewalSummary['renewal_rate'] ?? 0.0,
            'early_renewals' => $renewalSummary['early_renewals'] ?? 0,
            'post_expiry_renewals' => $renewalSummary['post_expiry_renewals'] ?? 0,
        ];
    }

    /**
     * Fetch paginated student records needing learning or retention support.
     *
     * @return LengthAwarePaginator
     */
    public function getStudentsNeedingSupport(
        string $cohort = 'all',
        ?string $search = null,
        ?int $courseId = null,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        $now = now();
        $threeDaysAgo = $now->copy()->subDays(3);
        $fourteenDaysAgo = $now->copy()->subDays(14);
        $thirtyDaysAhead = $now->copy()->addDays(30);

        $query = Enrollment::query()
            ->with(['user', 'course.category', 'latestAccessPeriod'])
            ->whereHas('user', function ($q) {
                $q->where('role', UserRole::STUDENT->value);
            })
            ->whereHas('course', function ($q) {
                $q->where('status', 'published');
            });

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply cohort scoping
        switch ($cohort) {
            case 'not_started':
                $query->where('status', EnrollmentStatus::ACTIVE->value)
                    ->where('enrolled_at', '<=', $threeDaysAgo)
                    ->whereDoesntHave('user.lessonProgress', function ($q) {
                        $q->where('completed', true)
                          ->whereHas('lesson.module', function ($mq) {
                              $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                          });
                    });
                break;

            case 'inactive':
                $query->where('status', EnrollmentStatus::ACTIVE->value)
                    ->whereHas('user.lessonProgress', function ($q) {
                        $q->where('completed', true)
                          ->whereHas('lesson.module', function ($mq) {
                              $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                          });
                    })
                    ->whereDoesntHave('user.lessonProgress', function ($q) use ($fourteenDaysAgo) {
                        $q->where('completed_at', '>=', $fourteenDaysAgo)
                          ->whereHas('lesson.module', function ($mq) {
                              $mq->whereColumn('course_modules.course_id', 'enrollments.course_id');
                          });
                    });
                break;

            case 'expiring_soon':
                $query->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                    ->whereNotNull('starts_at')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '>', $now)
                    ->where('expires_at', '<=', $thirtyDaysAhead);
                break;

            case 'expired':
                $query->where(function ($q) use ($now) {
                    $q->where('status', EnrollmentStatus::EXPIRED->value)
                      ->orWhere(function ($sq) use ($now) {
                          $sq->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $now);
                      });
                });
                break;

            case 'completed':
                $query->where('status', EnrollmentStatus::COMPLETED->value);
                break;

            case 'renewed':
                $query->whereHas('accessPeriods', function ($q) {
                    $q->where('period_type', 'renewal');
                });
                break;

            case 'approaching_completion':
                // Will be filtered in memory after fetching or via progress subquery
                $query->where('status', EnrollmentStatus::ACTIVE->value);
                break;
        }

        // For approaching_completion, we evaluate progress percentage
        if ($cohort === 'approaching_completion') {
            $allCandidates = $query->latest('enrolled_at')->get();
            $filtered = $allCandidates->filter(function ($enrollment) {
                if (! $enrollment->user || ! $enrollment->course) {
                    return false;
                }
                $progress = $enrollment->course->progressFor($enrollment->user);
                return $progress['percentage'] >= 80 && $progress['percentage'] < 100;
            });

            $total = $filtered->count();
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

            $annotated = $this->annotateEnrollments($items);

            return new PaginatorInstance($annotated, $total, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $paginator = $query->latest('enrolled_at')->paginate($perPage, ['*'], 'page', $page);
        $annotatedItems = $this->annotateEnrollments(collect($paginator->items()));

        return new PaginatorInstance(
            $annotatedItems,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Annotate each enrollment with calculated progress, activity date, retention signal and suggested action.
     */
    protected function annotateEnrollments(Collection $enrollments): Collection
    {
        $now = now();

        return $enrollments->map(function (Enrollment $enrollment) use ($now) {
            $user = $enrollment->user;
            $course = $enrollment->course;

            if (! $user || ! $course) {
                return $enrollment;
            }

            $progress = $course->progressFor($user);
            $nextLesson = $course->nextLessonFor($user);
            $accessState = $enrollment->getAccessState();

            // Find last activity
            $lastProgressDate = LessonProgress::query()
                ->where('user_id', $user->id)
                ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $course->id))
                ->latest('completed_at')
                ->value('completed_at');

            $daysSinceActivity = null;
            if ($lastProgressDate) {
                $daysSinceActivity = (int) Carbon::parse($lastProgressDate)->diffInDays($now);
            }

            // Determine retention signal and suggested support action
            $daysEnrolled = $enrollment->enrolled_at ? (int) $enrollment->enrolled_at->diffInDays($now) : 0;
            $hasRenewed = $enrollment->accessPeriods()->where('period_type', 'renewal')->exists();

            $signal = 'Active Learner';
            $signalColor = 'emerald';
            $suggestedAction = null;

            if ($accessState === 'expired') {
                $signal = $progress['is_completed'] ? 'Expired (Completed)' : 'Expired (Incomplete)';
                $signalColor = 'rose';
                $suggestedAction = self::SUPPORT_RENEWAL_REMINDER;
            } elseif ($accessState === 'expiring') {
                $signal = 'Expiring Soon (' . $enrollment->getRemainingDaysText() . ')';
                $signalColor = 'amber';
                $suggestedAction = self::SUPPORT_RENEWAL_REMINDER;
            } elseif ($progress['is_completed']) {
                $signal = $hasRenewed ? 'Completed & Renewed' : 'Completed Curriculum';
                $signalColor = 'indigo';
                $suggestedAction = null;
            } elseif ($progress['percentage'] >= 80) {
                $signal = 'Approaching Completion (' . $progress['percentage'] . '%)';
                $signalColor = 'teal';
                $suggestedAction = self::SUPPORT_COMPLETION_PUSH;
            } elseif ($progress['completed'] > 0 && ($daysSinceActivity === null || $daysSinceActivity >= 14)) {
                $signal = 'Inactive (' . ($daysSinceActivity ?? '14+') . 'd)';
                $signalColor = 'orange';
                $suggestedAction = self::SUPPORT_REENGAGEMENT;
            } elseif ($progress['completed'] === 0 && $daysEnrolled >= 3) {
                $signal = 'Not Started (' . $daysEnrolled . 'd enrolled)';
                $signalColor = 'purple';
                $suggestedAction = self::SUPPORT_KICKSTART;
            } elseif ($progress['percentage'] > 0) {
                $signal = 'In Progress (' . $progress['percentage'] . '%)';
                $signalColor = 'sky';
            }

            // Check if recent support was already sent (within 7 days)
            $recentSupport = EngagementLog::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('type', 'like', 'support_%')
                ->latest('sent_at')
                ->first();

            $enrollment->calculated_progress = $progress;
            $enrollment->next_lesson = $nextLesson;
            $enrollment->last_activity_at = $lastProgressDate;
            $enrollment->days_since_activity = $daysSinceActivity;
            $enrollment->days_enrolled = $daysEnrolled;
            $enrollment->retention_signal = $signal;
            $enrollment->retention_signal_color = $signalColor;
            $enrollment->suggested_action = $suggestedAction;
            $enrollment->recent_support = $recentSupport;
            $enrollment->has_renewed = $hasRenewed;

            return $enrollment;
        });
    }

    /**
     * Dispatch an idempotent learning support communication to a student.
     *
     * @return array{sent: bool, reason?: string, type?: string}
     */
    public function sendLearningSupport(User $student, Course $course, string $supportType, bool $dryRun = false): array
    {
        // 1. Verify student role
        if (! $student->isStudent()) {
            return ['sent' => false, 'reason' => 'User is not a student'];
        }

        // 2. Check marketing unsubscribe / communication preferences
        if (MarketingUnsubscribe::isUnsubscribed($student->email)) {
            return ['sent' => false, 'reason' => 'Student has unsubscribed from promotional and re-engagement communications'];
        }

        // 3. Cooldown check: 7-day cooldown per support type & course
        $cooldownDate = now()->subDays(7);
        $idempotencyKey = "support_{$supportType}_{$course->id}";

        $alreadySentRecently = EngagementLog::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('type', $idempotencyKey)
            ->where('sent_at', '>=', $cooldownDate)
            ->exists();

        if ($alreadySentRecently) {
            return ['sent' => false, 'reason' => 'A support message of this type was already sent within the 7-day cooldown window'];
        }

        if ($dryRun) {
            return ['sent' => true, 'type' => $supportType, 'reason' => 'Dry run simulation succeeded'];
        }

        // 4. Resolve next lesson and progress details
        $nextLesson = $course->nextLessonFor($student);
        $progress = $course->progressFor($student);

        // 5. Multi-channel delivery with failure isolation
        try {
            $student->notify(new StudentLearningSupportNotification(
                $course,
                $supportType,
                $nextLesson,
                [
                    'percentage' => $progress['percentage'],
                    'completed' => $progress['completed'],
                    'total' => $progress['total'],
                ]
            ));
        } catch (Throwable $e) {
            Log::warning('In-app learning support notification failed', [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Optional email delivery for inactivity/reengagement
        if (in_array($supportType, [self::SUPPORT_REENGAGEMENT, self::SUPPORT_KICKSTART], true)) {
            try {
                $this->mailService->sendStudentInactivity($student, $course, $nextLesson);
            } catch (Throwable $e) {
                Log::warning('Learning support email failed', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 6. Record deterministic EngagementLog
        try {
            EngagementLog::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'type' => $idempotencyKey,
                'channel' => 'multi',
                'metadata' => [
                    'support_type' => $supportType,
                    'progress_percentage' => $progress['percentage'],
                    'sent_at' => now()->toISOString(),
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record learning support engagement log', [
                'key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
        }

        // 7. Log CRM activity for student timeline
        $this->renewalAnalyticsService->logCrmRenewalActivity(
            $student,
            $course,
            'learning_support_sent',
            "Dispatched [{$supportType}] support communication for course: {$course->title}",
            [
                'support_type' => $supportType,
                'progress_percentage' => $progress['percentage'],
            ]
        );

        return ['sent' => true, 'type' => $supportType];
    }

    /**
     * Process automated learning support across active cohorts with strict cooldown.
     *
     * @return array<string, int>
     */
    public function processAutomatedSupport(bool $dryRun = false): array
    {
        $stats = [
            'kickstart_sent' => 0,
            'reengagement_sent' => 0,
            'completion_push_sent' => 0,
            'skipped_cooldown' => 0,
            'skipped_unsubscribed' => 0,
        ];

        $now = now();
        $threeDaysAgo = $now->copy()->subDays(3);
        $fourteenDaysAgo = $now->copy()->subDays(14);

        // 1. Kickstart Candidates (enrolled >= 3 days, 0 lessons completed)
        Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->where('enrolled_at', '<=', $threeDaysAgo)
            ->with(['user', 'course'])
            ->chunkById(100, function ($enrollments) use (&$stats, $dryRun) {
                foreach ($enrollments as $enrollment) {
                    if (! $enrollment->user || ! $enrollment->course) {
                        continue;
                    }
                    $progress = $enrollment->course->progressFor($enrollment->user);
                    if ($progress['completed'] === 0) {
                        $result = $this->sendLearningSupport(
                            $enrollment->user,
                            $enrollment->course,
                            self::SUPPORT_KICKSTART,
                            $dryRun
                        );
                        if ($result['sent']) {
                            $stats['kickstart_sent']++;
                        } elseif (($result['reason'] ?? '') === 'unsubscribed') {
                            $stats['skipped_unsubscribed']++;
                        } else {
                            $stats['skipped_cooldown']++;
                        }
                    }
                }
            });

        // 2. Re-engagement Candidates (>= 1 lesson completed, inactive >= 14 days)
        Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->with(['user', 'course'])
            ->chunkById(100, function ($enrollments) use (&$stats, $fourteenDaysAgo, $dryRun) {
                foreach ($enrollments as $enrollment) {
                    if (! $enrollment->user || ! $enrollment->course) {
                        continue;
                    }
                    $progress = $enrollment->course->progressFor($enrollment->user);
                    if ($progress['completed'] > 0 && ! $progress['is_completed']) {
                        $lastCompleted = LessonProgress::query()
                            ->where('user_id', $enrollment->user_id)
                            ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $enrollment->course_id))
                            ->latest('completed_at')
                            ->value('completed_at');

                        if (! $lastCompleted || Carbon::parse($lastCompleted)->lt($fourteenDaysAgo)) {
                            $result = $this->sendLearningSupport(
                                $enrollment->user,
                                $enrollment->course,
                                self::SUPPORT_REENGAGEMENT,
                                $dryRun
                            );
                            if ($result['sent']) {
                                $stats['reengagement_sent']++;
                            } elseif (($result['reason'] ?? '') === 'unsubscribed') {
                                $stats['skipped_unsubscribed']++;
                            } else {
                                $stats['skipped_cooldown']++;
                            }
                        }
                    }
                }
            });

        // 3. Completion Push Candidates (>= 80% and < 100%)
        Enrollment::query()
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->with(['user', 'course'])
            ->chunkById(100, function ($enrollments) use (&$stats, $dryRun) {
                foreach ($enrollments as $enrollment) {
                    if (! $enrollment->user || ! $enrollment->course) {
                        continue;
                    }
                    $progress = $enrollment->course->progressFor($enrollment->user);
                    if ($progress['percentage'] >= 80 && $progress['percentage'] < 100) {
                        $result = $this->sendLearningSupport(
                            $enrollment->user,
                            $enrollment->course,
                            self::SUPPORT_COMPLETION_PUSH,
                            $dryRun
                        );
                        if ($result['sent']) {
                            $stats['completion_push_sent']++;
                        } elseif (($result['reason'] ?? '') === 'unsubscribed') {
                            $stats['skipped_unsubscribed']++;
                        } else {
                            $stats['skipped_cooldown']++;
                        }
                    }
                }
            });

        return $stats;
    }

    /**
     * Stream CSV Export of Student Retention and Support Cohorts.
     */
    public function streamRetentionCsv(string $cohort = 'all', ?int $courseId = null): StreamedResponse
    {
        $filename = 'student-retention-' . $cohort . '-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($cohort, $courseId) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Student ID',
                'Student Name',
                'Student Email',
                'Course Title',
                'Enrollment Status',
                'Access State',
                'Enrolled At',
                'Expires At',
                'Days Remaining / Since Expiry',
                'Progress Percentage',
                'Lessons Completed',
                'Total Lessons',
                'Retention Signal',
                'Days Inactive',
                'Has Renewed',
            ]);

            // Page through records to protect memory
            $page = 1;
            do {
                $paginated = $this->getStudentsNeedingSupport($cohort, null, $courseId, 200, $page);
                foreach ($paginated->items() as $item) {
                    $prog = $item->calculated_progress ?? ['percentage' => 0, 'completed' => 0, 'total' => 0];

                    fputcsv($handle, [
                        $item->user?->id ?? '',
                        $item->user?->name ?? '',
                        $item->user?->email ?? '',
                        $item->course?->title ?? '',
                        $item->status?->value ?? (string) $item->status,
                        $item->getAccessState(),
                        $item->enrolled_at?->format('Y-m-d') ?? '',
                        $item->expires_at?->format('Y-m-d') ?? 'Lifetime',
                        $item->getRemainingDaysText() ?? '',
                        $prog['percentage'] . '%',
                        $prog['completed'],
                        $prog['total'],
                        $item->retention_signal ?? '',
                        $item->days_since_activity ?? '',
                        $item->has_renewed ? 'Yes' : 'No',
                    ]);
                }
                $page++;
            } while ($paginated->hasMorePages());

            fclose($handle);
        }, 200, $headers);
    }
}
