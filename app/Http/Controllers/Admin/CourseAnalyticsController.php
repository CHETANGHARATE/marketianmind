<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    /**
     * Display catalog-wide course analytics, metrics comparison, and rankings.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = $request->query('status');
        $categoryFilter = $request->query('category');
        $sort = $request->query('sort', 'enrollments_desc');

        // Catalog-wide aggregates
        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', CourseStatus::PUBLISHED->value)->count();
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count();
        $activeLearners = Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count();
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0.0;

        // Authoritative revenue from verified paid orders, in paise converted to INR
        $totalRevenuePaise = (int) Order::where('status', OrderStatus::PAID->value)->sum('amount');
        $totalRevenue = round($totalRevenuePaise / 100, 2);

        $overviewMetrics = [
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'total_enrollments' => $totalEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'active_learners' => $activeLearners,
            'completion_rate' => $completionRate,
            'total_revenue' => $totalRevenue,
            'formatted_revenue' => '₹' . number_format($totalRevenue, 2),
        ];

        // Course query with subquery aggregates to prevent N+1 queries
        $query = Course::query()
            ->with(['category:id,name,slug'])
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => function ($q) {
                    $q->where('status', EnrollmentStatus::ACTIVE->value);
                },
                'enrollments as completed_enrollments_count' => function ($q) {
                    $q->where('status', EnrollmentStatus::COMPLETED->value);
                },
            ])
            ->select('courses.*')
            ->selectSub(
                Order::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('orders.course_id', 'courses.id')
                    ->where('orders.status', OrderStatus::PAID->value),
                'paid_revenue_paise'
            );

        // Search Filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('courses.title', 'like', "%{$search}%")
                    ->orWhere('courses.slug', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($statusFilter && in_array($statusFilter, array_column(CourseStatus::cases(), 'value'), true)) {
            $query->where('courses.status', $statusFilter);
        }

        // Category Filter
        if (! empty($categoryFilter)) {
            $query->where('courses.course_category_id', $categoryFilter);
        }

        // Sorting
        switch ($sort) {
            case 'revenue_desc':
                $query->orderByDesc('paid_revenue_paise')->orderByDesc('courses.id');
                break;
            case 'completion_desc':
                $query->orderByRaw('CASE WHEN enrollments_count > 0 THEN (completed_enrollments_count * 1.0 / enrollments_count) ELSE 0 END DESC')
                    ->orderByDesc('enrollments_count');
                break;
            case 'title_asc':
                $query->orderBy('courses.title', 'asc');
                break;
            case 'created_desc':
                $query->orderByDesc('courses.created_at');
                break;
            case 'enrollments_desc':
            default:
                $query->orderByDesc('enrollments_count')->orderByDesc('courses.id');
                break;
        }

        $courses = $query->paginate(15)->withQueryString();

        // Calculate Average Progress per course efficiently
        $averageProgressMap = [];
        $courseIds = $courses->pluck('id')->all();

        if (! empty($courseIds)) {
            // Count total published lessons per course
            $lessonsPerCourse = DB::table('lessons')
                ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
                ->select('course_modules.course_id', DB::raw('COUNT(lessons.id) as total_lessons'))
                ->whereIn('course_modules.course_id', $courseIds)
                ->where('lessons.status', LessonStatus::PUBLISHED->value)
                ->groupBy('course_modules.course_id')
                ->pluck('total_lessons', 'course_id')
                ->all();

            // Count total completed lessons across enrolled users per course
            $completionsPerCourse = DB::table('lesson_progress')
                ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
                ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
                ->join('enrollments', function ($join) {
                    $join->on('enrollments.user_id', '=', 'lesson_progress.user_id')
                        ->on('enrollments.course_id', '=', 'course_modules.course_id');
                })
                ->whereIn('course_modules.course_id', $courseIds)
                ->where('lessons.status', LessonStatus::PUBLISHED->value)
                ->where('lesson_progress.completed', true)
                ->groupBy('course_modules.course_id')
                ->select('course_modules.course_id', DB::raw('COUNT(lesson_progress.id) as total_completed'))
                ->pluck('total_completed', 'course_id')
                ->all();

            foreach ($courses as $courseItem) {
                $enrolledCount = $courseItem->enrollments_count;
                $lessonCount = $lessonsPerCourse[$courseItem->id] ?? 0;
                $completedCount = $completionsPerCourse[$courseItem->id] ?? 0;

                if ($enrolledCount > 0 && $lessonCount > 0) {
                    $avg = round(($completedCount / ($enrolledCount * $lessonCount)) * 100, 1);
                    $averageProgressMap[$courseItem->id] = min(100.0, $avg);
                } else {
                    $averageProgressMap[$courseItem->id] = 0.0;
                }

                // Attach dynamic stats to each course model for view readability
                $courseItem->total_enrollments = (int) $courseItem->enrollments_count;
                $courseItem->active_enrollments = (int) $courseItem->active_enrollments_count;
                $courseItem->completed_enrollments = (int) $courseItem->completed_enrollments_count;
                $courseItem->completion_rate = $courseItem->total_enrollments > 0
                    ? round(($courseItem->completed_enrollments / $courseItem->total_enrollments) * 100, 1)
                    : 0.0;
                $courseItem->total_revenue = round(((int) ($courseItem->paid_revenue_paise ?? 0)) / 100, 2);
                $courseItem->avg_progress = $averageProgressMap[$courseItem->id];
            }
        }

        // Top 3 Courses Rankings (Using Eloquent has/whereHas for cross-database compatibility)
        $topByEnrollment = Course::has('enrollments')
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(3)
            ->get(['id', 'title', 'slug', 'course_category_id']);
        $topByEnrollment->load('category:id,name');

        foreach ($topByEnrollment as $tbe) {
            $tbe->total_enrollments = $tbe->enrollments_count;
            $tbeCompleted = Enrollment::where('course_id', $tbe->id)->where('status', EnrollmentStatus::COMPLETED->value)->count();
            $tbe->completed_enrollments = $tbeCompleted;
            $tbe->completion_rate = $tbe->total_enrollments > 0
                ? round(($tbeCompleted / $tbe->total_enrollments) * 100, 1)
                : 0.0;
        }

        $topByRevenue = Course::whereHas('orders', function ($q) {
                $q->where('status', OrderStatus::PAID->value);
            })
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.price', 'courses.is_free', 'courses.course_category_id')
            ->selectSub(
                Order::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('orders.course_id', 'courses.id')
                    ->where('orders.status', OrderStatus::PAID->value),
                'paid_revenue'
            )
            ->orderByDesc('paid_revenue')
            ->take(3)
            ->get();
        $topByRevenue->load('category:id,name');

        foreach ($topByRevenue as $tbr) {
            $tbr->total_revenue = round(((int) ($tbr->paid_revenue ?? 0)) / 100, 2);
        }

        $categories = CourseCategory::orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.analytics.courses.index', [
            'courses' => $courses,
            'overviewMetrics' => $overviewMetrics,
            'totalCatalogRevenue' => $totalRevenue,
            'totalEnrollments' => $totalEnrollments,
            'activeLearnersCount' => $activeLearners,
            'overallCompletionRate' => $completionRate,
            'topEnrollmentCourse' => $topByEnrollment->first(),
            'topRevenueCourse' => $topByRevenue->first(),
            'topCompletionCourse' => $topByEnrollment->sortByDesc(fn ($c) => $c->completion_rate)->first(),
            'topByEnrollment' => $topByEnrollment,
            'topByRevenue' => $topByRevenue,
            'averageProgressMap' => $averageProgressMap,
            'categories' => $categories,
            'courseStatuses' => CourseStatus::cases(),
            'currentSearch' => $search,
            'currentStatus' => $statusFilter,
            'currentCategory' => $categoryFilter,
            'currentSort' => $sort,
        ]);
    }

    /**
     * Display comprehensive course-specific analytics, lesson performance, and enrollment trends.
     */
    public function show(Course $course, Request $request): View
    {
        $period = $request->query('period', '30d');
        $range = match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        $course->loadMissing(['category', 'modules.lessons']);

        // 1. Enrollment Metrics
        $totalEnrollments = $course->enrollments()->count();
        $activeLearners = $course->enrollments()->where('status', EnrollmentStatus::ACTIVE->value)->count();
        $completedLearners = $course->enrollments()->where('status', EnrollmentStatus::COMPLETED->value)->count();
        $completionRate = $totalEnrollments > 0 ? round(($completedLearners / $totalEnrollments) * 100, 1) : 0.0;

        // 2. Published Lessons & Modules
        $publishedModulesCount = $course->modules()->count();
        $publishedLessonsCount = Lesson::query()
            ->where('status', LessonStatus::PUBLISHED->value)
            ->whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->count();

        // 3. Average Progress Calculation (Exact, zero N+1)
        $totalCompletedLessons = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->join('enrollments', function ($join) use ($course) {
                $join->on('enrollments.user_id', '=', 'lesson_progress.user_id')
                    ->where('enrollments.course_id', '=', $course->id);
            })
            ->where('course_modules.course_id', $course->id)
            ->where('lessons.status', LessonStatus::PUBLISHED->value)
            ->where('lesson_progress.completed', true)
            ->count();

        $averageProgress = ($totalEnrollments > 0 && $publishedLessonsCount > 0)
            ? min(100.0, round(($totalCompletedLessons / ($totalEnrollments * $publishedLessonsCount)) * 100, 1))
            : 0.0;

        // 4. Authoritative Revenue Metrics
        $paidRevenuePaise = $course->is_free
            ? 0
            : (int) Order::where('course_id', $course->id)->where('status', OrderStatus::PAID->value)->sum('amount');
        $paidRevenue = round($paidRevenuePaise / 100, 2);
        $paidOrdersCount = Order::where('course_id', $course->id)->where('status', OrderStatus::PAID->value)->count();
        $pendingOrdersCount = Order::where('course_id', $course->id)->where('status', OrderStatus::PENDING->value)->count();
        $failedOrdersCount = Order::where('course_id', $course->id)->where('status', OrderStatus::FAILED->value)->count();

        $enrollmentsLast7Days = $course->enrollments()->where('enrolled_at', '>=', now()->subDays(7))->count();
        $enrollmentsLast30Days = $course->enrollments()->where('enrolled_at', '>=', now()->subDays(30))->count();
        $certificatesIssued = Certificate::where('course_id', $course->id)->count();

        // 5. Progress Distribution Breakdown
        $enrolledUserIds = $course->enrollments()->pluck('user_id')->all();
        $distributionCounts = [
            'not_started' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        if (! empty($enrolledUserIds) && $publishedLessonsCount > 0) {
            $userCompletions = LessonProgress::query()
                ->select('user_id', DB::raw('COUNT(id) as completed_count'))
                ->whereIn('user_id', $enrolledUserIds)
                ->where('completed', true)
                ->whereHas('lesson', function ($lq) use ($course) {
                    $lq->where('status', LessonStatus::PUBLISHED->value)
                        ->whereHas('module', fn ($mq) => $mq->where('course_id', $course->id));
                })
                ->groupBy('user_id')
                ->pluck('completed_count', 'user_id')
                ->all();

            foreach ($enrolledUserIds as $userId) {
                $completed = $userCompletions[$userId] ?? 0;
                $pct = ($completed / $publishedLessonsCount) * 100;

                if ($completed === 0) {
                    $distributionCounts['not_started']++;
                } elseif ($pct >= 100) {
                    $distributionCounts['completed']++;
                } elseif ($pct <= 49) {
                    $distributionCounts['started']++;
                } else {
                    $distributionCounts['in_progress']++;
                }
            }
        } elseif (! empty($enrolledUserIds)) {
            $distributionCounts['not_started'] = count($enrolledUserIds);
        }

        $progressDistribution = [];
        foreach ($distributionCounts as $key => $cnt) {
            $progressDistribution[$key] = [
                'count' => $cnt,
                'percentage' => $totalEnrollments > 0 ? round(($cnt / $totalEnrollments) * 100, 1) : 0.0,
            ];
        }

        // 6. Lesson-Level Analytics & Module Breakdown
        $lessonStats = DB::table('lessons')
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->leftJoin('lesson_progress', function ($join) use ($course) {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->whereExists(function ($query) use ($course) {
                        $query->select(DB::raw(1))
                            ->from('enrollments')
                            ->whereColumn('enrollments.user_id', 'lesson_progress.user_id')
                            ->where('enrollments.course_id', $course->id);
                    });
            })
            ->where('course_modules.course_id', $course->id)
            ->where('lessons.status', LessonStatus::PUBLISHED->value)
            ->groupBy('lessons.id')
            ->select(
                'lessons.id as lesson_id',
                DB::raw('COUNT(lesson_progress.id) as started_count'),
                DB::raw('SUM(CASE WHEN lesson_progress.completed = 1 THEN 1 ELSE 0 END) as completed_count')
            )
            ->get()
            ->keyBy('lesson_id');

        $moduleAnalytics = [];
        $modules = $course->modules()->orderBy('sort_order')->get();
        $previousLessonCompletionRate = null;

        foreach ($modules as $module) {
            $lessonsData = [];
            $publishedLessons = $module->lessons()
                ->where('status', LessonStatus::PUBLISHED->value)
                ->orderBy('sort_order')
                ->get();

            foreach ($publishedLessons as $lesson) {
                $stat = $lessonStats->get($lesson->id);
                $completionsCount = (int) ($stat->completed_count ?? 0);
                $lessonCompletionRate = $totalEnrollments > 0
                    ? round(($completionsCount / $totalEnrollments) * 100, 1)
                    : 0.0;

                $dropoff = $previousLessonCompletionRate !== null
                    ? round($previousLessonCompletionRate - $lessonCompletionRate, 1)
                    : null;

                $previousLessonCompletionRate = $lessonCompletionRate;

                $lessonsData[] = [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'order' => $lesson->sort_order,
                    'content_type' => $lesson->lesson_type ?? 'video',
                    'completions_count' => $completionsCount,
                    'completion_rate' => $lessonCompletionRate,
                    'dropoff_from_previous' => $dropoff,
                ];
            }

            if (! empty($lessonsData) || $publishedLessonsCount === 0) {
                $moduleAnalytics[] = [
                    'id' => $module->id,
                    'title' => $module->title,
                    'order' => $module->sort_order,
                    'lessons' => $lessonsData,
                ];
            }
        }

        // 7. Enrollment and Completion Trends (Time-series)
        $startDate = Carbon::now()->subDays($range)->startOfDay();
        $datePeriod = CarbonPeriod::create($startDate, Carbon::now()->endOfDay());

        $enrollmentsByDate = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('enrolled_at', '>=', $startDate)
            ->select(DB::raw('DATE(enrolled_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(enrolled_at)'))
            ->pluck('count', 'date')
            ->all();

        $completionsByDate = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::COMPLETED->value)
            ->where('completed_at', '>=', $startDate)
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->pluck('count', 'date')
            ->all();

        $enrollmentHistory = [];
        $completionHistory = [];
        $trendsList = [];

        foreach ($datePeriod as $dateObj) {
            $formattedDate = $dateObj->format('Y-m-d');
            $eCount = (int) ($enrollmentsByDate[$formattedDate] ?? 0);
            $cCount = (int) ($completionsByDate[$formattedDate] ?? 0);

            $enrollmentHistory[$formattedDate] = $eCount;
            $completionHistory[$formattedDate] = $cCount;

            $trendsList[] = [
                'date' => $formattedDate,
                'label' => $dateObj->format('M d'),
                'enrollments' => $eCount,
                'completions' => $cCount,
            ];
        }

        $stats = [
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeLearners,
            'completed_enrollments' => $completedLearners,
            'completion_rate' => $completionRate,
            'avg_progress' => $averageProgress,
            'total_revenue' => $paidRevenue,
            'formatted_revenue' => '₹' . number_format($paidRevenue, 2),
            'published_lessons_count' => $publishedLessonsCount,
            'published_modules_count' => $publishedModulesCount,
            'enrollments_last_7_days' => $enrollmentsLast7Days,
            'enrollments_last_30_days' => $enrollmentsLast30Days,
            'certificates_issued' => $certificatesIssued,
            'paid_orders_count' => $paidOrdersCount,
            'pending_orders_count' => $pendingOrdersCount,
            'failed_orders_count' => $failedOrdersCount,
        ];

        return view('admin.analytics.courses.show', [
            'course' => $course,
            'period' => $period,
            'range' => $range,
            'stats' => $stats,
            'metrics' => $stats,
            'progressDistribution' => $progressDistribution,
            'distribution' => $distributionCounts,
            'moduleAnalytics' => $moduleAnalytics,
            'lessonStats' => $lessonStats,
            'trends' => [
                'days_count' => $range,
                'enrollment_history' => $enrollmentHistory,
                'completion_history' => $completionHistory,
                'list' => $trendsList,
            ],
        ]);
    }
}