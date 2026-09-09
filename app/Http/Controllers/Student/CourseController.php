<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display enrolled courses for the student (My Courses).
     */
    public function index(Request $request): View
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
     * Access a specific enrolled course player.
     */
    public function show(Request $request, Course $course): View|RedirectResponse
    {
        $user = $request->user();

        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        if (! $user->isEnrolledIn($course)) {
            abort(403, 'You are not enrolled in this course.');
        }

        $nextLesson = $course->nextLessonFor($user);

        if ($nextLesson) {
            return redirect()->route('student.courses.lessons.show', [$course, $nextLesson]);
        }

        $progress = $course->progressFor($user);

        return view('student.courses.learn', [
            'course' => $course,
            'currentLesson' => null,
            'modules' => $course->modules()->with('lessons')->get(),
            'progress' => $progress,
            'isCompleted' => false,
            'previousLesson' => null,
            'nextLesson' => null,
            'completedLessonIds' => [],
        ]);
    }
}