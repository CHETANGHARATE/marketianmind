<?php

namespace App\Http\Controllers\Student;

use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonController extends Controller
{
    /**
     * Display the learning interface for a specific lesson.
     */
    public function show(Request $request, Course $course, Lesson $lesson): View
    {
        $user = $request->user();

        $this->authorizeLessonAccess($user, $course, $lesson);

        // Track last watched / accessed time
        LessonProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'last_watched_at' => now(),
            ]
        );

        $modules = $course->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => function ($query) {
                $query->where('status', LessonStatus::PUBLISHED->value)
                    ->orderBy('sort_order');
            }])
            ->get();

        $completedLessonIds = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->flip()
            ->all();

        $isCompleted = isset($completedLessonIds[$lesson->id]);
        $progress = $course->progressFor($user);
        $previousLesson = $lesson->previousLesson();
        $nextLesson = $lesson->nextLesson();
        $firstLesson = $modules->first()?->lessons?->first();

        $certificate = null;
        if ($progress['is_completed']) {
            $certificate = Certificate::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
        }

        return view('student.courses.learn', [
            'course' => $course,
            'currentLesson' => $lesson,
            'modules' => $modules,
            'progress' => $progress,
            'isCompleted' => $isCompleted,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'completedLessonIds' => $completedLessonIds,
            'firstLesson' => $firstLesson,
            'certificate' => $certificate,
        ]);
    }

    /**
     * Mark a lesson as completed for the authenticated student.
     */
    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeLessonAccess($user, $course, $lesson);

        LessonProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'completed' => true,
                'completed_at' => now(),
                'last_watched_at' => now(),
            ]
        );

        // Recalculate course completion
        $progress = $course->progressFor($user);

        if ($progress['is_completed']) {
            $enrollment = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($enrollment) {
                if (! $enrollment->isCompleted()) {
                    $enrollment->markAsCompleted();
                }

                Certificate::issueFor($user, $course, $enrollment);
            }
        }

        $nextLesson = $lesson->nextLesson();

        if ($nextLesson) {
            return redirect()
                ->route('student.courses.lessons.show', [$course, $nextLesson])
                ->with('status', 'Lesson completed! On to the next one.');
        }

        return redirect()
            ->route('student.courses.lessons.show', [$course, $lesson])
            ->with('status', 'Congratulations! You have completed all lessons in this course.');
    }

    /**
     * Safely download or stream protected lesson PDF file.
     */
    public function downloadPdf(Request $request, Course $course, Lesson $lesson): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        $this->authorizeLessonAccess($user, $course, $lesson);

        if (! $lesson->isPdf() || ! $lesson->pdf_url) {
            abort(404, 'PDF resource not found for this lesson.');
        }

        // If external link, redirect
        if (str_starts_with($lesson->pdf_url, 'http://') || str_starts_with($lesson->pdf_url, 'https://')) {
            return redirect()->away($lesson->pdf_url);
        }

        if (Storage::disk('public')->exists($lesson->pdf_url)) {
            return Storage::disk('public')->response($lesson->pdf_url);
        }

        abort(404, 'PDF file does not exist in storage.');
    }

    /**
     * Validate all security and relational hierarchy requirements.
     */
    protected function authorizeLessonAccess($user, Course $course, Lesson $lesson): void
    {
        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        if (! $user->isEnrolledIn($course)) {
            abort(403, 'You must be enrolled in this course to access lessons.');
        }

        if (! $lesson->module || $lesson->module->course_id !== $course->id) {
            abort(404, 'This lesson does not belong to the specified course.');
        }

        if (! $lesson->isPublished()) {
            abort(404, 'This lesson is currently unpublished.');
        }
    }
}