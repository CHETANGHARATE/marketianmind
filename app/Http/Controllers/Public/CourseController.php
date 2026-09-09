<?php

namespace App\Http\Controllers\Public;

use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CourseController extends Controller
{
    /**
     * Display the Courses catalog page.
     */
    public function index(): View
    {
        $courses = collect();

        if (Schema::hasTable('courses')) {
            $courses = Course::query()
                ->published()
                ->with(['category'])
                ->withCount(['modules', 'lessons' => function ($query) {
                    $query->where('lessons.status', LessonStatus::PUBLISHED->value);
                }])
                ->latest()
                ->get();
        }

        return view('public.courses.index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Display the Course Details page.
     */
    public function show(Request $request, ?Course $course = null): View
    {
        if (Schema::hasTable('courses')) {
            if (! $course || ! $course->exists) {
                $course = Course::query()
                    ->published()
                    ->where('slug', 'digital-marketing-for-business-owners')
                    ->first()
                    ?? Course::query()->published()->first();
            }

            if ($course && ! $course->isPublished()) {
                abort(404);
            }

            if ($course) {
                $course->load([
                    'category',
                    'modules' => function ($query) {
                        $query->orderBy('sort_order')->with(['lessons' => function ($lq) {
                            $lq->where('lessons.status', LessonStatus::PUBLISHED->value)->orderBy('sort_order');
                        }]);
                    },
                ]);
            }
        }

        $user = $request->user();
        $isEnrolled = $course ? $course->isEnrolledBy($user) : false;

        return view('public.courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'user' => $user,
        ]);
    }
}