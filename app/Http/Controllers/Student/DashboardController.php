<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\LessonProgress;
use App\Services\GamificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Student Learning Command Center.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Fetch user certificates keyed by course_id for fast lookup
        $certificatesByCourse = Certificate::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('course_id');

        $certificatesCount = $certificatesByCourse->count();

        // 2. Retrieve all published enrolled courses for the student with eager loading
        $enrolledCoursesRaw = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount('modules')
            ->get();

        $enrolledCourses = [];
        $inProgressCourses = [];
        $completedCourses = [];
        $totalPublishedLessons = 0;
        $totalCompletedLessons = 0;

        foreach ($enrolledCoursesRaw as $course) {
            $progress = $course->progressFor($user);
            $nextLesson = $course->nextLessonFor($user);
            $isCompleted = $progress['is_completed'] || ($progress['percentage'] === 100 && $progress['total'] > 0);

            $totalPublishedLessons += $progress['total'];
            $totalCompletedLessons += $progress['completed'];

            $actionUrl = $nextLesson
                ? route('student.courses.lessons.show', [$course, $nextLesson])
                : route('student.courses.show', $course);

            $actionLabel = $isCompleted
                ? 'Review Course'
                : ($progress['percentage'] > 0 ? 'Continue Learning' : 'Start Course');

            $certificate = $certificatesByCourse->get($course->id);

            $courseData = [
                'model' => $course,
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnailUrl(),
                'category' => $course->category?->name,
                'instructor' => $course->instructorDisplayName(),
                'duration' => $course->estimated_duration ?? 'Self-paced',
                'modules_count' => $course->modules_count,
                'progress' => $progress,
                'is_completed' => $isCompleted,
                'certificate' => $certificate,
                'completed_at' => $course->pivot?->completed_at,
                'enrolled_at' => $course->pivot?->enrolled_at,
                'next_lesson' => $nextLesson,
                'actionUrl' => $actionUrl,
                'actionLabel' => $actionLabel,
            ];

            $enrolledCourses[] = $courseData;

            if ($isCompleted) {
                $completedCourses[] = $courseData;
            } else {
                $inProgressCourses[] = $courseData;
            }
        }

        // Prioritize in-progress courses:
        // 1. Actively in progress (progress > 0%) sorted by higher completion
        // 2. Newly enrolled / not started (0%)
        usort($inProgressCourses, function ($a, $b) {
            $aStarted = $a['progress']['percentage'] > 0 ? 1 : 0;
            $bStarted = $b['progress']['percentage'] > 0 ? 1 : 0;
            if ($aStarted !== $bStarted) {
                return $bStarted <=> $aStarted;
            }
            return $b['progress']['percentage'] <=> $a['progress']['percentage'];
        });

        $continueLearningCourse = $inProgressCourses[0] ?? null;

        // Limit Continue Learning to 4 courses max for dashboard display
        $displayInProgressCourses = array_slice($inProgressCourses, 0, 4);

        // Limit Completed courses to 4
        $displayCompletedCourses = array_slice($completedCourses, 0, 4);

        $enrolledCount = count($enrolledCourses);
        $inProgressCount = count($inProgressCourses);
        $completedCount = count($completedCourses);

        $overallProgress = $totalPublishedLessons > 0
            ? (int) min(100, max(0, round(($totalCompletedLessons / $totalPublishedLessons) * 100)))
            : 0;

        $stats = [
            'enrolled_courses' => $enrolledCount,
            'in_progress' => $inProgressCount,
            'completed' => $completedCount,
            'certificates' => $certificatesCount,
            'overall_progress' => $overallProgress,
            'lessons_completed' => $totalCompletedLessons,
            'total_lessons' => $totalPublishedLessons,
        ];

        // 3. Compact Recent Certificates (take 4)
        $recentCertificates = $user->certificates()
            ->with(['course'])
            ->latest('issued_at')
            ->take(4)
            ->get();

        // 4. Compact Saved Courses / Wishlist (take 4)
        $savedCourses = $user->wishlists()
            ->with(['course' => function ($q) {
                $q->with('category');
            }])
            ->whereHas('course', function ($q) {
                $q->published();
            })
            ->latest()
            ->take(4)
            ->get();

        // 5. Compact Notifications preview (take 5)
        $recentNotifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get();

        $unreadNotificationsCount = $user->unreadNotifications()->count();

        // 6. Compact Recent Learning Activity (authentic completed lessons, take 6)
        $recentActivities = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->with(['lesson.module.course'])
            ->latest('completed_at')
            ->take(6)
            ->get();

        // 7. Recent orders strictly belonging to this authenticated student (max 4)
        $recentOrders = $user->orders()
            ->with(['course'])
            ->latest()
            ->take(4)
            ->get();

        // 8. Gamification summary (streaks, points, badges)
        $gamification = app(GamificationService::class)->getGamificationSummary($user);

        return view('student.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'enrolledCourses' => $enrolledCourses,
            'continueLearningCourse' => $continueLearningCourse,
            'inProgressCourses' => $displayInProgressCourses,
            'completedCourses' => $displayCompletedCourses,
            'recentCertificates' => $recentCertificates,
            'savedCourses' => $savedCourses,
            'recentNotifications' => $recentNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'recentActivities' => $recentActivities,
            'recentOrders' => $recentOrders,
            'gamification' => $gamification,
            'headerTitle' => 'Student Dashboard',
        ]);
    }
}