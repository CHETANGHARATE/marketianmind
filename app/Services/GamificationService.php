<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\PointTransaction;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Models\UserAchievement;
use App\Notifications\AchievementUnlockedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Record a qualifying learning activity for the student on today's calendar date.
     */
    public function recordLearningActivity(User $user, string $activityType = 'lesson_completed'): StudentLearningDay
    {
        $today = Carbon::today()->toDateString();

        return StudentLearningDay::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'activity_date' => $today,
            ],
            [
                'activity_type' => $activityType,
            ]
        );
    }

    /**
     * Calculate the student's current streak, longest streak, and last activity date.
     *
     * Rules:
     * - A day counts if student has at least one qualifying activity.
     * - Current streak is the number of consecutive qualifying days ending on today or yesterday.
     * - Longest streak is the historical maximum consecutive qualifying days.
     *
     * @return array{current: int, longest: int, last_activity_date: ?string}
     */
    public function calculateStreaks(User $user): array
    {
        $dates = StudentLearningDay::query()
            ->where('user_id', $user->id)
            ->orderBy('activity_date', 'asc')
            ->pluck('activity_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();

        if (empty($dates)) {
            return [
                'current' => 0,
                'longest' => 0,
                'last_activity_date' => null,
                'has_learned_today' => false,
                'current_streak' => 0,
                'longest_streak' => 0,
            ];
        }

        // 1. Calculate historical longest streak
        $longest = 1;
        $temp = 1;

        for ($i = 1; $i < count($dates); $i++) {
            $prev = Carbon::parse($dates[$i - 1]);
            $curr = Carbon::parse($dates[$i]);

            if ($prev->copy()->addDay()->toDateString() === $curr->toDateString()) {
                $temp++;
                if ($temp > $longest) {
                    $longest = $temp;
                }
            } else {
                $temp = 1;
            }
        }

        // 2. Calculate current streak
        $todayStr = Carbon::today()->toDateString();
        $yesterdayStr = Carbon::yesterday()->toDateString();
        $lastDate = end($dates);

        $current = 0;
        // Current streak is alive only if the last activity was today or yesterday
        if ($lastDate === $todayStr || $lastDate === $yesterdayStr) {
            $current = 1;
            $dateSet = array_flip($dates);
            $cursor = Carbon::parse($lastDate)->subDay();

            while (isset($dateSet[$cursor->toDateString()])) {
                $current++;
                $cursor->subDay();
            }
        }

        return [
            'current' => $current,
            'longest' => max($longest, $current),
            'last_activity_date' => $lastDate,
            'has_learned_today' => ($lastDate === $todayStr),
            'current_streak' => $current,
            'longest_streak' => max($longest, $current),
        ];
    }

    /**
     * Award +10 points for completing a lesson (idempotent), record activity, and evaluate achievements.
     */
    public function awardLessonCompletion(User $user, Lesson $lesson): ?PointTransaction
    {
        return DB::transaction(function () use ($user, $lesson) {
            // Record distinct calendar learning day
            $this->recordLearningActivity($user, 'lesson_completed');

            // Idempotent point award
            $transaction = PointTransaction::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'event_type' => 'lesson_completed',
                    'reference_type' => Lesson::class,
                    'reference_id' => $lesson->id,
                ],
                [
                    'points' => 10,
                    'description' => "Completed Lesson: {$lesson->title}",
                ]
            );

            // Evaluate milestones
            $this->evaluateAchievements($user);

            return $transaction;
        });
    }

    /**
     * Award +100 points for completing a course (idempotent), record activity, and evaluate achievements.
     */
    public function awardCourseCompletion(User $user, Course $course): ?PointTransaction
    {
        return DB::transaction(function () use ($user, $course) {
            // Record distinct calendar learning day
            $this->recordLearningActivity($user, 'course_completed');

            // Idempotent point award
            $transaction = PointTransaction::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'event_type' => 'course_completed',
                    'reference_type' => Course::class,
                    'reference_id' => $course->id,
                ],
                [
                    'points' => 100,
                    'description' => "Completed Course: {$course->title}",
                ]
            );

            // Evaluate milestones
            $this->evaluateAchievements($user);

            return $transaction;
        });
    }

    /**
     * Evaluate and award any eligible achievements for the student idempotently.
     *
     * @return array<int, Achievement> List of newly awarded achievements
     */
    public function evaluateAchievements(User $user): array
    {
        $streaks = $this->calculateStreaks($user);
        $streakValue = max($streaks['current'], $streaks['longest']);

        $completedLessonsCount = $user->lessonProgress()
            ->where('completed', true)
            ->count();

        $completedCoursesCount = $user->enrollments()
            ->where('status', 'completed')
            ->count();

        // Get all active achievements the user has not yet earned
        $earnedAchievementIds = UserAchievement::query()
            ->where('user_id', $user->id)
            ->pluck('achievement_id')
            ->all();

        $unearnedAchievements = Achievement::query()
            ->where('is_active', true)
            ->whereNotIn('id', $earnedAchievementIds)
            ->get();

        $newlyEarned = [];

        foreach ($unearnedAchievements as $achievement) {
            $eligible = match ($achievement->requirement_type) {
                'lessons_completed' => $completedLessonsCount >= $achievement->requirement_value,
                'courses_completed' => $completedCoursesCount >= $achievement->requirement_value,
                'streak_days' => $streakValue >= $achievement->requirement_value,
                default => false,
            };

            if ($eligible) {
                $userAchievement = UserAchievement::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                    ],
                    [
                        'earned_at' => now(),
                    ]
                );

                if ($userAchievement->wasRecentlyCreated) {
                    $newlyEarned[] = $achievement;

                    // Award bonus points for achievement unlock
                    if ($achievement->points > 0) {
                        PointTransaction::query()->firstOrCreate(
                            [
                                'user_id' => $user->id,
                                'event_type' => 'achievement_unlocked',
                                'reference_type' => Achievement::class,
                                'reference_id' => $achievement->id,
                            ],
                            [
                                'points' => $achievement->points,
                                'description' => "Achievement Unlocked: {$achievement->name}",
                            ]
                        );
                    }

                    // Dispatch notification
                    try {
                        $user->notify(new AchievementUnlockedNotification($achievement));
                    } catch (\Throwable) {
                        // Keep transaction intact if notification queue or dispatch encounters an error
                    }
                }
            }
        }

        return $newlyEarned;
    }

    /**
     * Get a comprehensive gamification overview for the student.
     *
     * @return array<string, mixed>
     */
    public function getGamificationSummary(User $user): array
    {
        $streaks = $this->calculateStreaks($user);

        $pointsBalance = (int) PointTransaction::query()
            ->where('user_id', $user->id)
            ->sum('points');

        $pointsToday = (int) PointTransaction::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->sum('points');

        $earnedUserAchievements = UserAchievement::query()
            ->where('user_id', $user->id)
            ->with(['achievement'])
            ->orderByDesc('earned_at')
            ->get();

        $earnedAchievementIds = $earnedUserAchievements->pluck('achievement_id')->flip()->all();

        $allAchievements = Achievement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $completedLessonsCount = $user->lessonProgress()->where('completed', true)->count();
        $completedCoursesCount = $user->enrollments()->where('status', 'completed')->count();
        $streakValue = max($streaks['current'], $streaks['longest']);

        $achievementsWithStatus = $allAchievements->map(function ($ach) use ($earnedAchievementIds, $completedLessonsCount, $completedCoursesCount, $streakValue) {
            $isEarned = isset($earnedAchievementIds[$ach->id]);

            $currentProgress = match ($ach->requirement_type) {
                'lessons_completed' => min($ach->requirement_value, $completedLessonsCount),
                'courses_completed' => min($ach->requirement_value, $completedCoursesCount),
                'streak_days' => min($ach->requirement_value, $streakValue),
                default => 0,
            };

            $progressPercentage = $ach->requirement_value > 0
                ? min(100, (int) round(($currentProgress / $ach->requirement_value) * 100))
                : 0;

            return [
                'model' => $ach,
                'id' => $ach->id,
                'slug' => $ach->slug,
                'name' => $ach->name,
                'description' => $ach->description,
                'icon' => $ach->icon,
                'badge_color' => $ach->badge_color,
                'points' => $ach->points,
                'requirement_type' => $ach->requirement_type,
                'requirement_value' => $ach->requirement_value,
                'current_progress' => $currentProgress,
                'progress_percentage' => $progressPercentage,
                'is_earned' => $isEarned,
            ];
        });

        $recentPoints = PointTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return [
            'streaks' => $streaks,
            'points_balance' => $pointsBalance,
            'points_today' => $pointsToday,
            'points' => [
                'total' => $pointsBalance,
                'today' => $pointsToday,
                'history' => $recentPoints,
            ],
            'earned_count' => count($earnedAchievementIds),
            'total_count' => $allAchievements->count(),
            'achievements' => $achievementsWithStatus,
            'earned_achievements' => $achievementsWithStatus->where('is_earned', true)->values(),
            'recent_points' => $recentPoints,
        ];
    }
}
