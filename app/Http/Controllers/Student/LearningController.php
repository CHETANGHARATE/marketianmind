<?php

namespace App\Http\Controllers\Student;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    /**
     * Display enrolled courses for the student.
     */
    public function myLearning(Request $request): View
    {
        $user = $request->user();

        $enrolledCourses = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount('modules')
            ->get()
            ->map(function (Course $course) use ($user) {
                $progress = $course->progressFor($user);

                return [
                    'model' => $course,
                    'title' => $course->title,
                    'description' => $course->short_description ?? $course->description,
                    'modules' => $course->modules_count,
                    'duration' => $course->estimated_duration ?? 'Self-paced',
                    'actionUrl' => route('student.courses.show', $course),
                    'progress' => $progress,
                ];
            })
            ->all();

        return view('student.my-learning', [
            'user' => $user,
            'enrolledCourses' => $enrolledCourses,
            'headerTitle' => 'My Learning',
        ]);
    }

    /**
     * Browse available marketing courses for the student.
     */
    public function courses(Request $request): View
    {
        $user = $request->user();

        $enrolledCourses = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount(['modules'])
            ->get()
            ->map(function (Course $course) use ($user) {
                $progress = $course->progressFor($user);
                $nextLesson = $course->nextLessonFor($user);

                return [
                    'model' => $course,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->short_description ?? $course->description,
                    'thumbnail' => $course->thumbnailUrl(),
                    'category' => $course->category?->name,
                    'modules' => $course->modules_count,
                    'duration' => $course->estimated_duration ?? 'Self-paced',
                    'progress' => $progress,
                    'is_completed' => $progress['is_completed'],
                    'next_lesson' => $nextLesson,
                    'actionUrl' => $nextLesson
                        ? route('student.courses.lessons.show', [$course, $nextLesson])
                        : route('student.courses.show', $course),
                    'actionLabel' => $progress['is_completed'] ? 'Review Course' : 'Continue Learning',
                ];
            });

        return view('student.courses', [
            'user' => $user,
            'enrolledCourses' => $enrolledCourses,
            'headerTitle' => 'My Courses',
        ]);
    }

    /**
     * Display comprehensive student learning analytics and course progress.
     */
    public function progress(Request $request): View
    {
        $user = $request->user();

        // Retrieve student's earned certificates keyed by course_id
        $certificates = Certificate::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('course_id');

        // Retrieve all published enrolled courses for the student
        $enrolledCoursesRaw = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount('modules')
            ->get();

        $courseAnalytics = [];
        $totalPublishedLessons = 0;
        $totalCompletedLessons = 0;
        $inProgressCount = 0;
        $completedCount = 0;
        $nextContinueCourse = null;

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

            $certificate = $certificates->get($course->id);

            $actionUrl = $nextLesson
                ? route('student.courses.lessons.show', [$course, $nextLesson])
                : route('student.courses.show', $course);

            $actionLabel = $isCompleted
                ? 'Review Course'
                : ($progress['percentage'] > 0 ? 'Continue Learning' : 'Start Learning');

            $courseItem = [
                'model' => $course,
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnailUrl(),
                'category' => $course->category?->name,
                'instructor' => $course->instructor_name ?? 'Marketian Mind Faculty',
                'duration' => $course->estimated_duration ?? 'Self-paced',
                'modules_count' => $course->modules_count,
                'total_lessons' => $progress['total'],
                'completed_lessons' => $progress['completed'],
                'remaining_lessons' => max(0, $progress['total'] - $progress['completed']),
                'percentage' => $progress['percentage'],
                'is_completed' => $isCompleted,
                'certificate' => $certificate,
                'next_lesson' => $nextLesson,
                'action_url' => $actionUrl,
                'action_label' => $actionLabel,
            ];

            $courseAnalytics[] = $courseItem;

            if (! $nextContinueCourse && ! $isCompleted && $nextLesson) {
                $nextContinueCourse = $courseItem;
            }
        }

        $enrolledCount = count($courseAnalytics);
        $overallProgress = $totalPublishedLessons > 0
            ? (int) min(100, max(0, round(($totalCompletedLessons / $totalPublishedLessons) * 100)))
            : 0;

        $overview = [
            'total_courses' => $enrolledCount,
            'in_progress_courses' => $inProgressCount,
            'completed_courses' => $completedCount,
            'certificates_earned' => $certificates->count(),
            'total_lessons' => $totalPublishedLessons,
            'completed_lessons' => $totalCompletedLessons,
            'overall_progress' => $overallProgress,
            'total_learning_hours' => round($totalCompletedLessons * 0.25, 1),
        ];

        // Retrieve Recent Learning Activity for this student
        $recentActivity = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereHas('lesson', function ($q) {
                $q->where('status', LessonStatus::PUBLISHED->value)
                    ->whereHas('module.course', function ($cq) {
                        $cq->where('status', CourseStatus::PUBLISHED->value);
                    });
            })
            ->with(['lesson.module.course'])
            ->orderByRaw('COALESCE(last_watched_at, completed_at, updated_at) DESC')
            ->limit(8)
            ->get()
            ->map(function (LessonProgress $lp) {
                $lesson = $lp->lesson;
                $module = $lesson?->module;
                $course = $module?->course;

                $activityTimestamp = $lp->last_watched_at ?? $lp->completed_at ?? $lp->updated_at;

                return [
                    'lesson_title' => $lesson?->title ?? 'Lesson',
                    'lesson_type' => $lesson?->lesson_type->value ?? 'video',
                    'module_title' => $module?->title ?? 'Module',
                    'course_title' => $course?->title ?? 'Course',
                    'completed' => (bool) $lp->completed,
                    'activity_at' => $activityTimestamp,
                    'relative_time' => $activityTimestamp ? $activityTimestamp->diffForHumans() : 'Recently',
                    'resume_url' => ($course && $lesson)
                        ? route('student.courses.lessons.show', [$course, $lesson])
                        : null,
                ];
            })
            ->filter(fn ($item) => $item['resume_url'] !== null)
            ->values();

        // Maintain backward compatibility metrics array if existing views or components reference $metrics
        $metrics = [
            'total_learning_hours' => $overview['total_learning_hours'],
            'completed_lessons' => $overview['completed_lessons'],
            'courses_in_progress' => $overview['in_progress_courses'],
            'certificates_earned' => $overview['certificates_earned'],
            'overall_progress' => $overview['overall_progress'],
        ];

        return view('student.progress', [
            'user' => $user,
            'overview' => $overview,
            'metrics' => $metrics,
            'courseAnalytics' => $courseAnalytics,
            'recentActivity' => $recentActivity,
            'nextContinueCourse' => $nextContinueCourse,
            'headerTitle' => 'Learning Analytics',
        ]);
    }
}