<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\EngagementLog;
use App\Models\Enrollment;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Notifications\CertificateAvailableNotification;
use App\Notifications\CourseCompletionNotification;
use App\Notifications\CourseEnrollmentNotification;
use App\Notifications\CourseProgressMilestoneNotification;
use App\Notifications\StudentInactivityReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EngagementService
{
    public function __construct(
        protected TransactionalMailService $mailService
    ) {}

    /**
     * Record an engagement event idempotently.
     */
    public function recordEngagement(
        User $user,
        ?Course $course,
        string $type,
        string $channel = 'database',
        array $metadata = []
    ): EngagementLog {
        return EngagementLog::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course?->id,
                'type' => $type,
            ],
            [
                'channel' => $channel,
                'metadata' => $metadata,
                'sent_at' => now(),
            ]
        );
    }

    /**
     * Handle enrollment communication idempotently.
     */
    public function handleEnrollment(User $user, Course $course, bool $isPaid = false): bool
    {
        $log = $this->recordEngagement($user, $course, 'enrollment', 'both', ['is_paid' => $isPaid]);

        if (! $log->wasRecentlyCreated) {
            return false;
        }

        try {
            $user->notify(new CourseEnrollmentNotification($course));
        } catch (Throwable $e) {
            Log::warning('Enrollment in-app notification failed', ['error' => $e->getMessage()]);
        }

        $this->mailService->sendCourseEnrollment($user, $course);

        return true;
    }

    /**
     * Handle progress milestone notifications (25%, 50%, 75%, 90% near-completion) idempotently.
     */
    public function handleProgressMilestones(User $user, Course $course, int $percentage): ?string
    {
        if ($percentage < 25 || $percentage >= 100) {
            return null;
        }

        $milestone = match (true) {
            $percentage >= 90 => 'near_completion',
            $percentage >= 75 => 'milestone_75',
            $percentage >= 50 => 'milestone_50',
            $percentage >= 25 => 'milestone_25',
            default => null,
        };

        if (! $milestone) {
            return null;
        }

        $pctValue = match ($milestone) {
            'near_completion' => 90,
            'milestone_75' => 75,
            'milestone_50' => 50,
            'milestone_25' => 25,
        };

        $log = $this->recordEngagement($user, $course, $milestone, 'database', ['percentage' => $percentage]);

        if (! $log->wasRecentlyCreated) {
            return null;
        }

        try {
            $user->notify(new CourseProgressMilestoneNotification($course, $milestone, $pctValue));
        } catch (Throwable $e) {
            Log::warning('Progress milestone notification failed', ['error' => $e->getMessage()]);
        }

        return $milestone;
    }

    /**
     * Handle course completion communication idempotently.
     */
    public function handleCourseCompletion(User $user, Course $course): bool
    {
        $log = $this->recordEngagement($user, $course, 'completion', 'both');

        if (! $log->wasRecentlyCreated) {
            return false;
        }

        $recommended = $this->getRecommendedNextCourse($user, $course);

        try {
            $user->notify(new CourseCompletionNotification($course, $recommended));
        } catch (Throwable $e) {
            Log::warning('Course completion notification failed', ['error' => $e->getMessage()]);
        }

        $this->mailService->sendCourseCompletion($user, $course);

        app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
            \App\Enums\AutomationTrigger::COURSE_COMPLETED,
            $user,
            ['course_id' => $course->id],
            'course_completed_' . $user->id . '_' . $course->id
        );

        return true;
    }

    /**
     * Handle certificate availability communication idempotently.
     */
    public function handleCertificateAvailable(User $user, Certificate $certificate): bool
    {
        $log = $this->recordEngagement($user, $certificate->course, 'certificate', 'both', [
            'certificate_id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
        ]);

        if (! $log->wasRecentlyCreated) {
            return false;
        }

        try {
            $user->notify(new CertificateAvailableNotification($certificate));
        } catch (Throwable $e) {
            Log::warning('Certificate notification failed', ['error' => $e->getMessage()]);
        }

        $this->mailService->sendCertificateIssued($certificate);

        return true;
    }

    /**
     * Recommend a relevant next course for the student.
     * Prioritizes same category, published, and not already enrolled.
     */
    public function getRecommendedNextCourse(User $user, ?Course $completedCourse = null): ?Course
    {
        $enrolledIds = $user->enrollments()->pluck('course_id')->all();

        if ($completedCourse && $completedCourse->category_id) {
            $categoryCourse = Course::query()
                ->published()
                ->where('category_id', $completedCourse->category_id)
                ->whereNotIn('id', $enrolledIds)
                ->first();

            if ($categoryCourse) {
                return $categoryCourse;
            }
        }

        return Course::query()
            ->published()
            ->whereNotIn('id', $enrolledIds)
            ->latest('id')
            ->first();
    }

    /**
     * Process inactivity reminders for eligible students.
     * Rules:
     * - Enrolled in an active, incomplete course.
     * - Has prior completed lessons (started learning).
     * - No qualifying activity for at least 7 consecutive days.
     * - 7-day cooldown since last inactivity reminder.
     * - Deterministic course selection (highest progress course).
     */
    public function processInactivityReminders(): int
    {
        $cutoffDate = Carbon::today()->subDays(7)->toDateString();
        $cooldownTime = Carbon::now()->subDays(7);
        $remindersSent = 0;

        // Query active students who have in-progress courses in memory-efficient chunks
        User::query()
            ->where('role', 'student')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', EnrollmentStatus::ACTIVE);
            })
            ->whereHas('lessonProgress', function ($q) {
                $q->where('completed', true);
            })
            ->chunk(50, function ($students) use ($cutoffDate, $cooldownTime, &$remindersSent) {
                foreach ($students as $student) {
                    // Check cooldown: Has student received an inactivity reminder within the last 7 days?
                    $recentReminder = EngagementLog::query()
                        ->where('user_id', $student->id)
                        ->where('type', 'inactivity_reminder')
                        ->where('sent_at', '>=', $cooldownTime)
                        ->exists();

                    if ($recentReminder) {
                        continue;
                    }

                    // Respect notification preferences / marketing unsubscribe
                    if (\App\Models\MarketingUnsubscribe::isUnsubscribed($student->email)) {
                        continue;
                    }

                    // Check last qualifying activity
                    $lastLearningDay = StudentLearningDay::query()
                        ->where('user_id', $student->id)
                        ->latest('activity_date')
                        ->value('activity_date');

                    if ($lastLearningDay) {
                        $lastDateStr = Carbon::parse($lastLearningDay)->toDateString();
                        if ($lastDateStr > $cutoffDate) {
                            // Active within the last 7 days; not inactive
                            continue;
                        }
                    } else {
                        // Fallback to latest completed lesson progress
                        $lastProgress = $student->lessonProgress()
                            ->where('completed', true)
                            ->latest('completed_at')
                            ->value('completed_at');

                        if ($lastProgress && Carbon::parse($lastProgress)->toDateString() > $cutoffDate) {
                            continue;
                        }
                    }

                    // Find all in-progress courses and select deterministically (highest progress)
                    $inProgressCourses = [];
                    $enrolledCourses = $student->enrolledCourses()
                        ->published()
                        ->wherePivot('status', EnrollmentStatus::ACTIVE)
                        ->get();

                    foreach ($enrolledCourses as $c) {
                        // Exclude courses where student access is expired, cancelled, or inactive
                        if (! $student->hasActiveAccessTo($c)) {
                            continue;
                        }

                        $progress = $c->progressFor($student);
                        if ($progress['completed'] > 0 && ! $progress['is_completed']) {
                            $inProgressCourses[] = [
                                'course' => $c,
                                'percentage' => $progress['percentage'],
                                'next_lesson' => $c->nextLessonFor($student),
                            ];
                        }
                    }

                    if (empty($inProgressCourses)) {
                        continue;
                    }

                    // Sort descending by completion percentage
                    usort($inProgressCourses, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
                    $selected = $inProgressCourses[0];

                    $targetCourse = $selected['course'];
                    $nextLesson = $selected['next_lesson'];

                    // Record log and deliver reminder
                    $log = $this->recordEngagement(
                        $student,
                        $targetCourse,
                        'inactivity_reminder',
                        'both',
                        [
                            'days_inactive' => 7,
                            'percentage' => $selected['percentage'],
                        ]
                    );

                    if ($log->wasRecentlyCreated) {
                        try {
                            $student->notify(new StudentInactivityReminderNotification($targetCourse, 7, $nextLesson));
                        } catch (Throwable $e) {
                            Log::warning('Inactivity notification failed', ['error' => $e->getMessage()]);
                        }

                        $this->mailService->sendStudentInactivity($student, $targetCourse, $nextLesson);
                        $remindersSent++;
                    }
                }
            });

        return $remindersSent;
    }
}
