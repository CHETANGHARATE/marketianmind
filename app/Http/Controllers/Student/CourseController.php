<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
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

        $certificatesByCourseId = Certificate::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('course_id');

        $enrollmentsByCourseId = $user->enrollments()
            ->get()
            ->keyBy('course_id');

        $allEnrolledCourses = $user->enrolledCourses()
            ->published()
            ->with(['category'])
            ->withCount(['modules'])
            ->get()
            ->map(function (Course $course) use ($user, $certificatesByCourseId, $enrollmentsByCourseId) {
                $progress = $course->progressFor($user);
                $nextLesson = $course->nextLessonFor($user);
                $certificate = $certificatesByCourseId->get($course->id);
                $enrollment = $enrollmentsByCourseId->get($course->id);

                $accessState = $enrollment ? $enrollment->getAccessState() : 'active';
                $remainingDaysText = $enrollment ? $enrollment->getRemainingDaysText() : null;
                $formattedExpiry = $enrollment ? $enrollment->getFormattedExpiryDate('d M Y') : null;
                $canRenew = $enrollment ? $enrollment->canRenew() : false;
                $renewalLabel = $enrollment ? $enrollment->getRenewalCtaLabel() : 'Renew Access';
                $badgeDetails = $enrollment ? $enrollment->getAccessBadgeDetails() : ['label' => 'Access Active', 'color' => 'emerald', 'state' => 'active'];

                $isCompleted = $progress['is_completed'];

                $actionUrl = $nextLesson
                    ? route('student.courses.lessons.show', [$course, $nextLesson])
                    : route('student.courses.show', $course);

                $actionLabel = $isCompleted ? 'Review Course' : 'Continue Learning';

                return [
                    'model' => $course,
                    'course' => $course,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->short_description ?? $course->description,
                    'thumbnail' => $course->thumbnailUrl(),
                    'category' => $course->category?->name,
                    'modules' => $course->modules_count,
                    'duration' => $course->estimated_duration ?? 'Self-paced',
                    'progress' => $progress,
                    'is_completed' => $isCompleted,
                    'certificate' => $certificate,
                    'next_lesson' => $nextLesson,
                    'actionUrl' => $actionUrl,
                    'actionLabel' => $actionLabel,
                    'enrollment' => $enrollment,
                    'access_state' => $accessState,
                    'remaining_days_text' => $remainingDaysText,
                    'formatted_expiry' => $formattedExpiry,
                    'can_renew' => $canRenew,
                    'renewal_label' => $renewalLabel,
                    'badge_details' => $badgeDetails,
                ];
            });

        // Compute tab counts across all enrolled courses
        $filterCounts = [
            'all' => $allEnrolledCourses->count(),
            'active' => $allEnrolledCourses->filter(fn ($c) => in_array($c['access_state'], ['active', 'lifetime'], true))->count(),
            'expiring' => $allEnrolledCourses->filter(fn ($c) => $c['access_state'] === 'expiring')->count(),
            'expired' => $allEnrolledCourses->filter(fn ($c) => $c['access_state'] === 'expired')->count(),
            'completed' => $allEnrolledCourses->filter(fn ($c) => $c['is_completed'])->count(),
        ];

        $currentFilter = $request->query('status', 'all');

        $filteredCourses = match ($currentFilter) {
            'active' => $allEnrolledCourses->filter(fn ($c) => in_array($c['access_state'], ['active', 'lifetime'], true)),
            'expiring' => $allEnrolledCourses->filter(fn ($c) => $c['access_state'] === 'expiring'),
            'expired' => $allEnrolledCourses->filter(fn ($c) => $c['access_state'] === 'expired'),
            'completed' => $allEnrolledCourses->filter(fn ($c) => $c['is_completed']),
            default => $allEnrolledCourses,
        };

        return view('student.courses', [
            'user' => $user,
            'enrolledCourses' => $filteredCourses->values(),
            'filterCounts' => $filterCounts,
            'currentFilter' => $currentFilter,
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

        if (! $user->hasActiveAccessTo($course)) {
            abort(403, 'You do not have active enrollment access to this course.');
        }

        $nextLesson = $course->nextLessonFor($user);

        if ($nextLesson) {
            return redirect()->route('student.courses.lessons.show', [$course, $nextLesson]);
        }

        $progress = $course->progressFor($user);

        $modules = $course->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => function ($query) {
                $query->where('status', LessonStatus::PUBLISHED->value)
                    ->orderBy('sort_order');
            }])
            ->get();

        $firstLesson = $modules->first()?->lessons?->first();

        return view('student.courses.learn', [
            'course' => $course,
            'currentLesson' => null,
            'modules' => $modules,
            'progress' => $progress,
            'isCompleted' => false,
            'previousLesson' => null,
            'nextLesson' => null,
            'completedLessonIds' => [],
            'firstLesson' => $firstLesson,
        ]);
    }
}