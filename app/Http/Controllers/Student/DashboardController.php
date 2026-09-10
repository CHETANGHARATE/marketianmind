<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the enhanced Student Portal Dashboard overview.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $certificatesByCourseId = Certificate::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('course_id');

        // 1. Retrieve all published enrolled courses for the student with eager loading
        $enrolledCoursesRaw = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount('modules')
            ->get();

        $enrolledCourses = [];
        $inProgressCount = 0;
        $completedCount = 0;
        $totalPublishedLessons = 0;
        $totalCompletedLessons = 0;
        $continueLearningCourse = null;

        foreach ($enrolledCoursesRaw as $course) {
            $progress = $course->progressFor($user);
            $nextLesson = $course->nextLessonFor($user);
            $isCompleted = $progress['is_completed'] || ($progress['percentage'] === 100 && $progress['total'] > 0);

            if ($isCompleted) {
                $completedCount++;
            } elseif ($progress['percentage'] > 0) {
                $inProgressCount++;
            }

            $totalPublishedLessons += $progress['total'];
            $totalCompletedLessons += $progress['completed'];

            $actionUrl = $nextLesson
                ? route('student.courses.lessons.show', [$course, $nextLesson])
                : route('student.courses.show', $course);

            $actionLabel = $isCompleted
                ? 'Review Course'
                : ($progress['percentage'] > 0 ? 'Continue Learning' : 'Start Learning');

            $certificate = $certificatesByCourseId->get($course->id);

            $courseData = [
                'model' => $course,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnailUrl(),
                'category' => $course->category?->name,
                'instructor' => $course->instructor_name ?? 'Marketian Mind Faculty',
                'duration' => $course->estimated_duration ?? 'Self-paced',
                'modules_count' => $course->modules_count,
                'progress' => $progress,
                'is_completed' => $isCompleted,
                'certificate' => $certificate,
                'completed_at' => $course->pivot?->completed_at,
                'next_lesson' => $nextLesson,
                'actionUrl' => $actionUrl,
                'actionLabel' => $actionLabel,
            ];

            $enrolledCourses[] = $courseData;

            // Prioritize the first in-progress course for Continue Learning, or first non-completed
            if (! $continueLearningCourse && ! $isCompleted) {
                $continueLearningCourse = $courseData;
            }
        }

        // If no incomplete course was found, fall back to the first enrolled course if any
        if (! $continueLearningCourse && count($enrolledCourses) > 0) {
            $continueLearningCourse = $enrolledCourses[0];
        }

        $enrolledCount = count($enrolledCourses);

        $overallProgress = $totalPublishedLessons > 0
            ? (int) min(100, max(0, round(($totalCompletedLessons / $totalPublishedLessons) * 100)))
            : 0;

        $stats = [
            'enrolled_courses' => $enrolledCount,
            'in_progress' => $inProgressCount,
            'completed' => $completedCount,
            'overall_progress' => $overallProgress,
            'lessons_completed' => $totalCompletedLessons,
        ];

        // 2. Retrieve recent orders strictly belonging to this authenticated student (max 4)
        $recentOrders = $user->orders()
            ->with(['course'])
            ->latest()
            ->take(4)
            ->get();

        return view('student.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'enrolledCourses' => $enrolledCourses,
            'continueLearningCourse' => $continueLearningCourse,
            'recentOrders' => $recentOrders,
            'headerTitle' => 'Student Dashboard',
        ]);
    }
}