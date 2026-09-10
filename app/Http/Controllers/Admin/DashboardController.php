<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the Admin Portal dashboard with comprehensive platform metrics,
     * recent activity feeds, and performance indicators.
     */
    public function index(): View
    {
        $hasUsers = Schema::hasTable('users');
        $hasCourses = Schema::hasTable('courses');
        $hasEnrollments = Schema::hasTable('enrollments');
        $hasOrders = Schema::hasTable('orders');

        // Student Metrics
        $totalStudents = $hasUsers ? User::where('role', UserRole::STUDENT->value)->count() : 0;
        $newStudents30d = $hasUsers
            ? User::where('role', UserRole::STUDENT->value)->where('created_at', '>=', now()->subDays(30))->count()
            : 0;

        // Course Metrics
        $totalCourses = $hasCourses ? Course::count() : 0;
        $publishedCourses = $hasCourses ? Course::where('status', CourseStatus::PUBLISHED->value)->count() : 0;
        $draftCourses = $hasCourses ? Course::where('status', CourseStatus::DRAFT->value)->count() : 0;
        $featuredCourses = $hasCourses ? Course::where('featured', true)->count() : 0;

        // Enrollment Metrics
        $totalEnrollments = $hasEnrollments ? Enrollment::count() : 0;
        $activeEnrollments = $hasEnrollments ? Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count() : 0;
        $completedEnrollments = $hasEnrollments ? Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count() : 0;
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0;

        // Order & Revenue Metrics
        // Revenue is calculated strictly from verified, paid orders (OrderStatus::PAID).
        // Stored in paise as integer, converted to INR float.
        $totalOrders = $hasOrders ? Order::count() : 0;
        $paidOrders = $hasOrders ? Order::where('status', OrderStatus::PAID->value)->count() : 0;
        $pendingOrders = $hasOrders ? Order::where('status', OrderStatus::PENDING->value)->count() : 0;
        $failedOrders = $hasOrders ? Order::where('status', OrderStatus::FAILED->value)->count() : 0;
        $cancelledOrders = $hasOrders ? Order::where('status', OrderStatus::CANCELLED->value)->count() : 0;
        $totalRevenuePaise = $hasOrders ? (int) Order::where('status', OrderStatus::PAID->value)->sum('amount') : 0;
        $totalRevenue = round($totalRevenuePaise / 100, 2);

        $metrics = [
            'total_students' => $totalStudents,
            'new_students_30d' => $newStudents30d,
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'draft_courses' => $draftCourses,
            'featured_courses' => $featuredCourses,
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'completion_rate' => $completionRate,
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'pending_orders' => $pendingOrders,
            'failed_orders' => $failedOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_revenue' => $totalRevenue,
            'formatted_revenue' => '₹' . number_format($totalRevenue, 2),
        ];

        // Recent Activity Feeds (limited to latest 5, with eager loading)
        $recentStudents = $hasUsers
            ? User::where('role', UserRole::STUDENT->value)
                ->select(['id', 'name', 'email', 'created_at'])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $recentOrders = $hasOrders
            ? Order::with(['user:id,name,email', 'course:id,title,slug,price,is_free'])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $recentEnrollments = $hasEnrollments
            ? Enrollment::with(['user:id,name,email', 'course:id,title,slug'])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $coursesOverview = $hasCourses
            ? Course::with('category:id,name')
                ->withCount([
                    'enrollments',
                    'enrollments as completed_enrollments_count' => function ($q) {
                        $q->where('status', EnrollmentStatus::COMPLETED->value);
                    },
                ])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        return view('admin.dashboard', compact(
            'metrics',
            'recentStudents',
            'recentOrders',
            'recentEnrollments',
            'coursesOverview'
        ));
    }
}
