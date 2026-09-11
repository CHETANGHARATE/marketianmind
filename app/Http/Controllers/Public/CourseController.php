<?php

namespace App\Http\Controllers\Public;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class CourseController extends Controller
{
    /**
     * Display the Courses discovery and catalog page with search, filters, and sorting.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $searchQuery = trim($request->query('q', ''));
        $selectedCategorySlug = $request->query('category');
        $selectedType = $request->query('type'); // 'all', 'free', 'paid'
        $selectedSort = $request->query('sort', 'newest'); // 'newest', 'oldest', 'price_low', 'price_high', 'featured'

        $categories = collect();
        if (Schema::hasTable('course_categories') && Schema::hasTable('courses')) {
            // Fetch active categories with published course counts for the filter menu
            $categories = CourseCategory::query()
                ->active()
                ->withCount(['courses' => function ($q) {
                    $q->published();
                }])
                ->orderBy('name')
                ->get();
        }

        $courses = new LengthAwarePaginator([], 0, 9);
        $selectedCategory = null;

        if (Schema::hasTable('courses')) {
            // Start base query strictly scoped to published courses
            $query = Course::query()
                ->published()
                ->with(['category'])
                ->withAvg('approvedReviews as average_rating', 'rating')
                ->withCount([
                    'approvedReviews as reviews_count',
                    'modules',
                    'lessons' => function ($q) {
                        $q->where('lessons.status', LessonStatus::PUBLISHED->value);
                    }
                ]);

            // 1. Search filter
            if ($searchQuery !== '') {
                $query->search($searchQuery);
            }

            // 2. Category filter
            if ($selectedCategorySlug && Schema::hasTable('course_categories')) {
                $selectedCategory = CourseCategory::query()
                    ->active()
                    ->where('slug', $selectedCategorySlug)
                    ->first();

                if ($selectedCategory) {
                    $query->where('courses.course_category_id', $selectedCategory->id);
                } else {
                    $selectedCategorySlug = null;
                }
            }

            // 3. Free/Paid type filter
            if ($selectedType === 'free') {
                $query->free();
            } elseif ($selectedType === 'paid') {
                $query->paid();
            } else {
                $selectedType = 'all';
            }

            // 4. Sorting with strict whitelist
            switch ($selectedSort) {
                case 'oldest':
                    $query->orderBy('courses.created_at', 'asc');
                    break;
                case 'price_low':
                    $query->orderByRaw('COALESCE(courses.discount_price, courses.price) ASC');
                    break;
                case 'price_high':
                    $query->orderByRaw('COALESCE(courses.discount_price, courses.price) DESC');
                    break;
                case 'featured':
                    $query->orderBy('courses.featured', 'desc')->orderBy('courses.created_at', 'desc');
                    break;
                case 'newest':
                default:
                    $selectedSort = 'newest';
                    $query->orderBy('courses.created_at', 'desc');
                    break;
            }

            // Paginate (9 courses per page, 3x3 grid)
            $courses = $query->paginate(9)->withQueryString();
        } else {
            if ($selectedType !== 'free' && $selectedType !== 'paid') {
                $selectedType = 'all';
            }
            $selectedSort = 'newest';
        }

        // 5. Efficient enrollment ownership lookup for authenticated student (no N+1)
        $enrolledCourseIds = [];
        $completedCourseIds = [];
        if ($user && $user->isStudent() && Schema::hasTable('enrollments')) {
            $enrolledCourseIds = $user->enrollments()
                ->whereIn('status', ['active', 'completed'])
                ->pluck('course_id')
                ->flip()
                ->toArray();

            $completedCourseIds = $user->enrollments()
                ->where('status', 'completed')
                ->pluck('course_id')
                ->flip()
                ->toArray();
        }

        // Determine if any filters are active
        $hasActiveFilters = ($searchQuery !== '')
            || ($selectedCategory !== null)
            || ($selectedType !== 'all')
            || ($selectedSort !== 'newest');

        return view('public.courses.index', [
            'courses' => $courses,
            'categories' => $categories,
            'searchQuery' => $searchQuery,
            'selectedCategory' => $selectedCategory,
            'selectedCategorySlug' => $selectedCategorySlug,
            'selectedType' => $selectedType,
            'selectedSort' => $selectedSort,
            'hasActiveFilters' => $hasActiveFilters,
            'enrolledCourseIds' => $enrolledCourseIds,
            'completedCourseIds' => $completedCourseIds,
            'user' => $user,
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
                    'instructor',
                    'modules' => function ($query) {
                        $query->orderBy('sort_order')->with(['lessons' => function ($lq) {
                            $lq->where('lessons.status', LessonStatus::PUBLISHED->value)->orderBy('sort_order');
                        }]);
                    },
                ]);
            }
        } else {
            $course = null;
        }

        $user = $request->user();
        $isEnrolled = false;
        $isCompleted = false;
        $progress = ['total' => 0, 'completed' => 0, 'percentage' => 0, 'is_completed' => false];

        if ($user && $user->isStudent() && $course && Schema::hasTable('enrollments')) {
            $enrollment = $user->enrollments()
                ->where('course_id', $course->id)
                ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                ->first();

            if ($enrollment) {
                $isEnrolled = true;
                $isCompleted = $enrollment->isCompleted();
                $progress = $course->progressFor($user);
                if ($progress['is_completed']) {
                    $isCompleted = true;
                }
            }
        }

        // Fetch up to 3 related published courses in same category (or fallback to other published courses)
        $relatedCourses = collect();
        if (Schema::hasTable('courses') && $course) {
            $relatedCourses = Course::query()
                ->published()
                ->where('id', '!=', $course->id)
                ->when($course->course_category_id, function ($q) use ($course) {
                    $q->where('courses.course_category_id', $course->course_category_id);
                })
                ->with(['category'])
                ->withCount(['modules', 'lessons' => function ($lq) {
                    $lq->where('lessons.status', LessonStatus::PUBLISHED->value);
                }])
                ->latest()
                ->take(3)
                ->get();

            if ($relatedCourses->count() < 3) {
                $excludedIds = $relatedCourses->pluck('id')->push($course->id)->all();
                $supplement = Course::query()
                    ->published()
                    ->whereNotIn('id', $excludedIds)
                    ->with(['category'])
                    ->withCount(['modules', 'lessons' => function ($lq) {
                        $lq->where('lessons.status', LessonStatus::PUBLISHED->value);
                    }])
                    ->latest()
                    ->take(3 - $relatedCourses->count())
                    ->get();
                $relatedCourses = $relatedCourses->merge($supplement);
            }
        }

        $certificate = null;
        if ($isCompleted && $user && Schema::hasTable('certificates')) {
            $certificate = Certificate::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
        }

        $reviews = new LengthAwarePaginator([], 0, 5);
        $averageRating = 0.0;
        $reviewsCount = 0;
        $ratingDistribution = [];
        $userReview = null;

        if ($course && Schema::hasTable('course_reviews')) {
            $reviews = $course->approvedReviews()
                ->with('user:id,name,role')
                ->latest()
                ->paginate(5, ['*'], 'reviews_page')
                ->withQueryString();

            $averageRating = $course->averageRating();
            $reviewsCount = $course->reviewsCount();
            $ratingDistribution = $course->ratingDistribution();

            if ($user && $isEnrolled) {
                $userReview = $course->userReview($user);
            }
        }

        return view('public.courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'isCompleted' => $isCompleted,
            'certificate' => $certificate,
            'progress' => $progress,
            'relatedCourses' => $relatedCourses,
            'user' => $user,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'reviewsCount' => $reviewsCount,
            'ratingDistribution' => $ratingDistribution,
            'userReview' => $userReview,
        ]);
    }
}