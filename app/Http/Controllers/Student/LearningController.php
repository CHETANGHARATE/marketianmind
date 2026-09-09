<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
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
     * Display learning progress metrics and completed modules.
     */
    public function progress(Request $request): View
    {
        $user = $request->user();

        $completedLessons = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->count();

        $inProgressCourses = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->count();

        $completedCourses = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentStatus::COMPLETED->value)
            ->count();

        $progressMetrics = [
            'total_learning_hours' => round($completedLessons * 0.25, 1),
            'completed_lessons' => $completedLessons,
            'courses_in_progress' => $inProgressCourses,
            'certificates_earned' => $completedCourses,
        ];

        return view('student.progress', [
            'user' => $user,
            'metrics' => $progressMetrics,
            'headerTitle' => 'Learning Progress',
        ]);
    }
}