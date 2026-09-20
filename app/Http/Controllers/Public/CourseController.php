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
     * Display the Courses discovery and catalog page with advanced search, filters, and sorting.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Capture & sanitize input parameters
        $searchQuery = trim($request->query('q', ''));
        $selectedCategorySlug = $request->query('category');
        $selectedType = $request->query('type'); // 'all', 'free', 'paid'
        
        $minPrice = $request->filled('min_price') && is_numeric($request->query('min_price'))
            ? max(0.0, (float) $request->query('min_price'))
            : null;
        $maxPrice = $request->filled('max_price') && is_numeric($request->query('max_price'))
            ? max(0.0, (float) $request->query('max_price'))
            : null;

        if ($minPrice !== null && $maxPrice !== null && $maxPrice < $minPrice) {
            $temp = $minPrice;
            $minPrice = $maxPrice;
            $maxPrice = $temp;
        }

        $selectedRating = $request->filled('rating') && is_numeric($request->query('rating'))
            ? (float) $request->query('rating')
            : null;

        $selectedDuration = $request->query('duration'); // 'short', 'medium', 'long'
        if (! in_array($selectedDuration, ['short', 'medium', 'long'], true)) {
            $selectedDuration = null;
        }

        $selectedFeatured = $request->boolean('featured');

        // Whitelist allowed sorts and set intelligent default
        $allowedSorts = ['relevance', 'newest', 'oldest', 'price_low', 'price_high', 'rating', 'popular'];
        $selectedSort = $request->query('sort');
        if (! in_array($selectedSort, $allowedSorts, true)) {
            $selectedSort = $searchQuery !== '' ? 'relevance' : 'newest';
        }

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
            // Start base query strictly scoped to published courses with eager loading
            $query = Course::query()
                ->published()
                ->with([
                    'category',
                    'offers' => function ($q) {
                        $q->currentlyValid()->orderByDesc('priority')->orderByDesc('discount_value')->orderByDesc('id');
                    },
                ])
                ->withAvg('approvedReviews as average_rating', 'rating')
                ->withCount([
                    'approvedReviews as reviews_count',
                    'enrollments as active_enrollments_count' => function ($q) {
                        $q->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value]);
                    },
                    'modules',
                    'lessons' => function ($q) {
                        $q->where('lessons.status', LessonStatus::PUBLISHED->value);
                    }
                ]);

            // 1. Search filter across title, descriptions, instructor, and category
            if ($searchQuery !== '') {
                $query->advancedSearch($searchQuery);
            }

            // 2. Category filter
            if ($selectedCategorySlug && Schema::hasTable('course_categories')) {
                $selectedCategory = CourseCategory::query()
                    ->active()
                    ->where('slug', $selectedCategorySlug)
                    ->orWhere('id', is_numeric($selectedCategorySlug) ? (int) $selectedCategorySlug : 0)
                    ->first();

                if ($selectedCategory) {
                    $query->where('courses.course_category_id', $selectedCategory->id);
                    $selectedCategorySlug = $selectedCategory->slug;
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

            // 4. Effective Price Range filter
            if ($minPrice !== null || $maxPrice !== null) {
                $query->priceRange($minPrice, $maxPrice);
            }

            // 5. Minimum Rating filter
            if ($selectedRating !== null && $selectedRating > 0) {
                $query->minRating($selectedRating);
            }

            // 6. Estimated Duration filter
            if ($selectedDuration !== null) {
                $query->durationRange($selectedDuration);
            }

            // 7. Featured only filter
            if ($selectedFeatured) {
                $query->featured();
            }

            // 8. Sorting with strict whitelist
            switch ($selectedSort) {
                case 'relevance':
                    if ($searchQuery !== '') {
                        $query->orderByRaw("
                            CASE 
                                WHEN LOWER(courses.title) = LOWER(?) THEN 1
                                WHEN LOWER(courses.title) LIKE LOWER(?) THEN 2
                                WHEN LOWER(courses.short_description) LIKE LOWER(?) THEN 3
                                WHEN LOWER(courses.description) LIKE LOWER(?) THEN 4
                                ELSE 5
                            END ASC
                        ", [
                            $searchQuery,
                            "{$searchQuery}%",
                            "%{$searchQuery}%",
                            "%{$searchQuery}%",
                        ])->orderBy('courses.created_at', 'desc');
                    } else {
                        $query->orderBy('courses.created_at', 'desc');
                    }
                    break;

                case 'oldest':
                    $query->orderBy('courses.created_at', 'asc');
                    break;

                case 'price_low':
                    $query->orderByRaw(Course::effectivePriceSql() . ' ASC')->orderBy('courses.created_at', 'desc');
                    break;

                case 'price_high':
                    $query->orderByRaw(Course::effectivePriceSql() . ' DESC')->orderBy('courses.created_at', 'desc');
                    break;

                case 'rating':
                    $query->orderByRaw(
                        '(SELECT COALESCE(AVG(rating), 0) FROM course_reviews WHERE course_reviews.course_id = courses.id AND course_reviews.status = ?) DESC',
                        [\App\Enums\CourseReviewStatus::APPROVED->value]
                    )->orderBy('courses.created_at', 'desc');
                    break;

                case 'popular':
                    $query->orderBy('active_enrollments_count', 'desc')->orderBy('courses.created_at', 'desc');
                    break;

                case 'newest':
                default:
                    $selectedSort = 'newest';
                    $query->orderBy('courses.created_at', 'desc');
                    break;
            }

            // Paginate (9 courses per page, 3x3 grid) with query string persistence
            $courses = $query->paginate(9)->withQueryString();
        } else {
            if ($selectedType !== 'free' && $selectedType !== 'paid') {
                $selectedType = 'all';
            }
            $selectedSort = $searchQuery !== '' ? 'relevance' : 'newest';
        }

        // 9. Efficient enrollment ownership lookup for authenticated student (no N+1)
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
        $defaultSort = $searchQuery !== '' ? 'relevance' : 'newest';
        $hasActiveFilters = ($searchQuery !== '')
            || ($selectedCategory !== null)
            || ($selectedType !== 'all')
            || ($minPrice !== null)
            || ($maxPrice !== null)
            || ($selectedRating !== null)
            || ($selectedDuration !== null)
            || ($selectedFeatured)
            || ($selectedSort !== $defaultSort);

        $seoService = app(\App\Services\SeoService::class);
        $catalogTitle = 'Online Marketing Courses for Business Owners';
        if ($selectedCategory) {
            $catalogTitle = "{$selectedCategory->name} Courses for Business Owners";
        } elseif ($searchQuery !== '') {
            $catalogTitle = "Search results for \"{$searchQuery}\" — Courses";
        }

        $catalogDescription = 'Explore practical, ROI-driven digital marketing courses for small business owners and startup founders. Master SEO, ads, analytics, and social media with 365-day access.';
        if ($selectedCategory && !empty($selectedCategory->description)) {
            $catalogDescription = $selectedCategory->description;
        }

        $seo = $seoService->buildMeta([
            'title' => $catalogTitle,
            'description' => $catalogDescription,
            'canonical' => route('courses'),
            'schemas' => [
                $seoService->buildBreadcrumbSchema([
                    'Home' => url('/'),
                    'Courses' => route('courses'),
                ]),
            ],
        ]);

        return view('public.courses.index', [
            'courses' => $courses,
            'categories' => $categories,
            'searchQuery' => $searchQuery,
            'selectedCategory' => $selectedCategory,
            'selectedCategorySlug' => $selectedCategorySlug,
            'selectedType' => $selectedType,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'selectedRating' => $selectedRating,
            'selectedDuration' => $selectedDuration,
            'selectedFeatured' => $selectedFeatured,
            'selectedSort' => $selectedSort,
            'hasActiveFilters' => $hasActiveFilters,
            'enrolledCourseIds' => $enrolledCourseIds,
            'completedCourseIds' => $completedCourseIds,
            'user' => $user,
            'seo' => $seo,
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

        $enrollment = null;
        if ($user && $user->isStudent() && $course && Schema::hasTable('enrollments')) {
            $enrollment = $user->enrollments()
                ->where('course_id', $course->id)
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

        $seo = null;
        if ($course) {
            $seoService = app(\App\Services\SeoService::class);
            $seoMeta = $course->seo;
            $seo = $seoService->buildMeta([
                'title' => $seoMeta?->meta_title ?: $course->title,
                'description' => $seoMeta?->meta_description ?: ($course->short_description ?: strip_tags($course->description ?? '')),
                'canonical' => $seoMeta?->canonical_url ?: route('courses.show', $course->slug),
                'robots' => $seoMeta?->robots ?: 'index, follow',
                'og_title' => $seoMeta?->og_title ?: $course->title,
                'og_description' => $seoMeta?->og_description ?: ($course->short_description ?: strip_tags($course->description ?? '')),
                'og_image' => $seoMeta?->og_image ?: ($course->thumbnail ? url($course->thumbnail) : null),
                'schemas' => [
                    $seoService->buildCourseSchema($course),
                    $seoService->buildBreadcrumbSchema([
                        'Home' => url('/'),
                        'Courses' => route('courses'),
                        $course->title => route('courses.show', $course->slug),
                    ]),
                ],
            ]);

            // Track Course View Conversion Event
            app(\App\Services\ConversionTrackingService::class)->track('course_view', [
                'course_id' => $course->id,
            ]);
        }

        // Resolve CTA Experiment Assignment (Target: course_cta or course_cta_text)
        $ctaVariant = null;
        if ($course) {
            $experimentService = app(\App\Services\ExperimentService::class);
            $ctaVariant = $experimentService->getAssignment('course_cta_text', $user, $request, ['target_id' => $course->id])
                ?: $experimentService->getAssignment('course_cta', $user, $request, ['target_id' => $course->id]);
        }

        return view('public.courses.show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'enrollment' => $enrollment,
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
            'seo' => $seo,
            'ctaVariant' => $ctaVariant,
        ]);
    }
}