<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingService
{
    /**
     * Parse date range criteria from request parameters.
     *
     * @return array{start: ?Carbon, end: ?Carbon, range: string, label: string}
     */
    public function parseDateRange(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = now();
        $start = null;
        $end = $now->copy()->endOfDay();
        $label = 'Last 30 Days';

        switch ($range) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $label = 'Today';
                break;
            case '7d':
                $start = $now->copy()->subDays(7)->startOfDay();
                $label = 'Last 7 Days';
                break;
            case '30d':
                $start = $now->copy()->subDays(30)->startOfDay();
                $label = 'Last 30 Days';
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $label = 'This Month (' . $now->format('M Y') . ')';
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                $label = 'Last Month (' . $start->format('M Y') . ')';
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $label = 'This Year (' . $now->format('Y') . ')';
                break;
            case 'custom':
                if ($customStart) {
                    $start = Carbon::parse($customStart)->startOfDay();
                }
                if ($customEnd) {
                    $end = Carbon::parse($customEnd)->endOfDay();
                }
                $label = ($start ? $start->format('M d, Y') : 'Beginning') . ' - ' . ($end ? $end->format('M d, Y') : 'Present');
                break;
            case 'all':
            default:
                $start = null;
                $end = null;
                $range = 'all';
                $label = 'All Time';
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'range' => $range,
            'label' => $label,
        ];
    }

    /**
     * Get Executive Overview Summary metrics across the platform.
     */
    public function getExecutiveSummary(?Carbon $start = null, ?Carbon $end = null): array
    {
        $hasOrders = Schema::hasTable('orders');
        $hasEnrollments = Schema::hasTable('enrollments');
        $hasUsers = Schema::hasTable('users');
        $hasCertificates = Schema::hasTable('certificates');

        // Authoritative Paid Orders: stored in paise, converted to INR
        $orderQuery = $hasOrders ? Order::where('status', OrderStatus::PAID->value) : null;
        if ($orderQuery && $start) {
            $orderQuery->where('paid_at', '>=', $start);
        }
        if ($orderQuery && $end) {
            $orderQuery->where('paid_at', '<=', $end);
        }

        $paidOrdersCount = $orderQuery ? (clone $orderQuery)->count() : 0;
        $totalRevenuePaise = $orderQuery ? (int) (clone $orderQuery)->sum('amount') : 0;
        $totalDiscountPaise = $orderQuery ? (int) (clone $orderQuery)->sum('discount_amount') : 0;
        $totalRevenue = round($totalRevenuePaise / 100, 2);
        $totalDiscount = round($totalDiscountPaise / 100, 2);
        $grossSales = round(($totalRevenuePaise + $totalDiscountPaise) / 100, 2);
        $avgOrderValue = $paidOrdersCount > 0 ? round($totalRevenue / $paidOrdersCount, 2) : 0;

        // Enrollments
        $enrollmentQuery = $hasEnrollments ? Enrollment::query() : null;
        if ($enrollmentQuery && $start) {
            $enrollmentQuery->where('enrolled_at', '>=', $start);
        }
        if ($enrollmentQuery && $end) {
            $enrollmentQuery->where('enrolled_at', '<=', $end);
        }

        $totalEnrollments = $enrollmentQuery ? (clone $enrollmentQuery)->count() : 0;
        $activeEnrollments = $enrollmentQuery ? (clone $enrollmentQuery)->where('status', EnrollmentStatus::ACTIVE->value)->count() : 0;
        $completedEnrollments = $enrollmentQuery ? (clone $enrollmentQuery)->where('status', EnrollmentStatus::COMPLETED->value)->count() : 0;
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0;

        // Student Growth
        $userQuery = $hasUsers ? User::where('role', UserRole::STUDENT->value) : null;
        if ($userQuery && $start) {
            $userQuery->where('created_at', '>=', $start);
        }
        if ($userQuery && $end) {
            $userQuery->where('created_at', '<=', $end);
        }
        $newStudents = $userQuery ? (clone $userQuery)->count() : 0;
        $totalStudents = $hasUsers ? User::where('role', UserRole::STUDENT->value)->count() : 0;

        // Conversion rate from student account to paid purchaser
        $uniquePaidStudents = $hasOrders
            ? Order::where('status', OrderStatus::PAID->value)->distinct('user_id')->count('user_id')
            : 0;
        $studentConversionRate = $totalStudents > 0 ? round(($uniquePaidStudents / $totalStudents) * 100, 1) : 0;

        // Certificates Issued
        $certQuery = $hasCertificates ? Certificate::query() : null;
        if ($certQuery && $start) {
            $certQuery->where('issued_at', '>=', $start);
        }
        if ($certQuery && $end) {
            $certQuery->where('issued_at', '<=', $end);
        }
        $certificatesIssued = $certQuery ? (clone $certQuery)->count() : 0;

        return [
            'total_revenue' => $totalRevenue,
            'formatted_revenue' => '₹' . number_format($totalRevenue, 2),
            'gross_sales' => $grossSales,
            'formatted_gross_sales' => '₹' . number_format($grossSales, 2),
            'total_discount' => $totalDiscount,
            'formatted_discount' => '₹' . number_format($totalDiscount, 2),
            'paid_orders_count' => $paidOrdersCount,
            'avg_order_value' => $avgOrderValue,
            'formatted_aov' => '₹' . number_format($avgOrderValue, 2),
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'completion_rate' => $completionRate,
            'new_students' => $newStudents,
            'total_students' => $totalStudents,
            'unique_paid_students' => $uniquePaidStudents,
            'student_conversion_rate' => $studentConversionRate,
            'certificates_issued' => $certificatesIssued,
        ];
    }

    /**
     * Get Sales & Revenue Detailed Report.
     */
    public function getSalesReport(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (! Schema::hasTable('orders')) {
            return [
                'summary' => $this->getExecutiveSummary($start, $end),
                'status_breakdown' => [],
                'daily_trend' => collect(),
                'recent_orders' => collect(),
            ];
        }

        $allOrdersQuery = Order::query();
        if ($start) {
            $allOrdersQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $allOrdersQuery->where('created_at', '<=', $end);
        }

        // Status counts
        $statusBreakdown = [
            'paid' => (clone $allOrdersQuery)->where('status', OrderStatus::PAID->value)->count(),
            'pending' => (clone $allOrdersQuery)->where('status', OrderStatus::PENDING->value)->count(),
            'failed' => (clone $allOrdersQuery)->where('status', OrderStatus::FAILED->value)->count(),
            'cancelled' => (clone $allOrdersQuery)->where('status', OrderStatus::CANCELLED->value)->count(),
            'refunded' => (clone $allOrdersQuery)->where('status', OrderStatus::REFUNDED->value)->count(),
        ];

        // Daily/Periodic Revenue Aggregate for paid orders
        $paidQuery = Order::where('status', OrderStatus::PAID->value);
        if ($start) {
            $paidQuery->where('paid_at', '>=', $start);
        }
        if ($end) {
            $paidQuery->where('paid_at', '<=', $end);
        }

        $orders = $paidQuery->select(['id', 'amount', 'discount_amount', 'paid_at', 'created_at'])
            ->orderBy('paid_at', 'asc')
            ->get();

        $dailyTrend = $orders->groupBy(function ($order) {
            return $order->paid_at ? $order->paid_at->format('Y-m-d') : $order->created_at->format('Y-m-d');
        })->map(function ($dayOrders, $date) {
            $revPaise = $dayOrders->sum('amount');
            $discPaise = $dayOrders->sum('discount_amount');
            return [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->format('M d, Y'),
                'orders_count' => $dayOrders->count(),
                'revenue' => round($revPaise / 100, 2),
                'discount' => round($discPaise / 100, 2),
            ];
        })->values();

        // Recent paid transactions
        $recentOrders = (clone $paidQuery)
            ->with(['user:id,name,email', 'course:id,title,slug'])
            ->latest('paid_at')
            ->take(15)
            ->get();

        return [
            'summary' => $this->getExecutiveSummary($start, $end),
            'status_breakdown' => $statusBreakdown,
            'daily_trend' => $dailyTrend,
            'recent_orders' => $recentOrders,
        ];
    }

    /**
     * Get Course Performance Report.
     */
    public function getCoursePerformanceReport(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        if (! Schema::hasTable('courses')) {
            return collect();
        }

        $courses = Course::all();

        return $courses->map(function (Course $course) use ($start, $end) {
            // Paid orders for this course
            $orderQuery = Order::where('course_id', $course->id)
                ->where('status', OrderStatus::PAID->value);
            if ($start) {
                $orderQuery->where('paid_at', '>=', $start);
            }
            if ($end) {
                $orderQuery->where('paid_at', '<=', $end);
            }

            $paidOrdersCount = (clone $orderQuery)->count();
            $revenuePaise = (clone $orderQuery)->sum('amount');
            $discountPaise = (clone $orderQuery)->sum('discount_amount');
            $revenue = round($revenuePaise / 100, 2);
            $discount = round($discountPaise / 100, 2);

            // Enrollments for this course
            $enrollmentQuery = Enrollment::where('course_id', $course->id);
            if ($start) {
                $enrollmentQuery->where('enrolled_at', '>=', $start);
            }
            if ($end) {
                $enrollmentQuery->where('enrolled_at', '<=', $end);
            }

            $totalEnrollments = (clone $enrollmentQuery)->count();
            $activeEnrollments = (clone $enrollmentQuery)->where('status', EnrollmentStatus::ACTIVE->value)->count();
            $completedEnrollments = (clone $enrollmentQuery)->where('status', EnrollmentStatus::COMPLETED->value)->count();
            $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0;

            // Certificates issued
            $certQuery = Certificate::where('course_id', $course->id);
            if ($start) {
                $certQuery->where('issued_at', '>=', $start);
            }
            if ($end) {
                $certQuery->where('issued_at', '<=', $end);
            }
            $certificatesIssued = (clone $certQuery)->count();

            return [
                'course' => $course,
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'status' => $course->status,
                'price' => $course->price,
                'is_free' => (bool) $course->is_free,
                'paid_orders_count' => $paidOrdersCount,
                'revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
                'discount' => $discount,
                'total_enrollments' => $totalEnrollments,
                'active_enrollments' => $activeEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'completion_rate' => $completionRate,
                'certificates_issued' => $certificatesIssued,
            ];
        })->sortByDesc('revenue')->values();
    }

    /**
     * Get Enrollment & Completion Report.
     */
    public function getEnrollmentReport(?Carbon $start = null, ?Carbon $end = null): array
    {
        if (! Schema::hasTable('enrollments')) {
            return [
                'summary' => $this->getExecutiveSummary($start, $end),
                'daily_trend' => collect(),
                'recent_completions' => collect(),
            ];
        }

        $query = Enrollment::query();
        if ($start) {
            $query->where('enrolled_at', '>=', $start);
        }
        if ($end) {
            $query->where('enrolled_at', '<=', $end);
        }

        $enrollments = (clone $query)->select(['id', 'status', 'enrolled_at', 'created_at'])->get();

        $dailyTrend = $enrollments->groupBy(function ($enr) {
            return $enr->enrolled_at ? $enr->enrolled_at->format('Y-m-d') : $enr->created_at->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->format('M d, Y'),
                'total' => $group->count(),
                'active' => $group->where('status', EnrollmentStatus::ACTIVE->value)->count(),
                'completed' => $group->where('status', EnrollmentStatus::COMPLETED->value)->count(),
            ];
        })->values();

        // Recent completions
        $recentCompletions = Enrollment::where('status', EnrollmentStatus::COMPLETED->value)
            ->with(['user:id,name,email', 'course:id,title,slug'])
            ->latest('completed_at')
            ->take(15)
            ->get();

        return [
            'summary' => $this->getExecutiveSummary($start, $end),
            'daily_trend' => $dailyTrend,
            'recent_completions' => $recentCompletions,
        ];
    }

    /**
     * Get Coupon Performance Report.
     */
    public function getCouponPerformanceReport(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        if (! Schema::hasTable('coupons')) {
            return collect();
        }

        $coupons = Coupon::all();

        return $coupons->map(function (Coupon $coupon) use ($start, $end) {
            $ordersQuery = Order::where('coupon_id', $coupon->id)
                ->where('status', OrderStatus::PAID->value);

            if ($start) {
                $ordersQuery->where('paid_at', '>=', $start);
            }
            if ($end) {
                $ordersQuery->where('paid_at', '<=', $end);
            }

            $ordersCount = (clone $ordersQuery)->count();
            $discountPaise = (clone $ordersQuery)->sum('discount_amount');
            $revenuePaise = (clone $ordersQuery)->sum('amount');
            $discount = round($discountPaise / 100, 2);
            $revenue = round($revenuePaise / 100, 2);

            return [
                'coupon' => $coupon,
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->discount_type,
                'value' => $coupon->discount_value,
                'is_active' => $coupon->is_active,
                'orders_count' => $ordersCount,
                'discount' => $discount,
                'formatted_discount' => '₹' . number_format($discount, 2),
                'revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
            ];
        })->sortByDesc('orders_count')->values();
    }

    /**
     * Stream Sales CSV export.
     */
    public function streamSalesCsv(?Carbon $start = null, ?Carbon $end = null): StreamedResponse
    {
        $filename = 'sales-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Order ID',
                'Order Number',
                'Student Name',
                'Student Email',
                'Course Title',
                'Original Amount (INR)',
                'Discount (INR)',
                'Net Amount Paid (INR)',
                'Status',
                'Coupon Code',
                'Razorpay Order ID',
                'Paid At',
                'Created At',
            ]);

            if (Schema::hasTable('orders')) {
                $query = Order::with(['user:id,name,email', 'course:id,title']);
                if ($start) {
                    $query->where('created_at', '>=', $start);
                }
                if ($end) {
                    $query->where('created_at', '<=', $end);
                }

                $query->orderBy('id', 'desc')->chunk(100, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->id,
                            $order->order_number,
                            $order->user?->name ?? 'N/A',
                            $order->user?->email ?? 'N/A',
                            $order->course?->title ?? 'N/A',
                            round(($order->original_amount ?? $order->amount) / 100, 2),
                            round(($order->discount_amount ?? 0) / 100, 2),
                            round($order->amount / 100, 2),
                            $order->status instanceof OrderStatus ? $order->status->value : $order->status,
                            $order->coupon_code ?? 'None',
                            $order->razorpay_order_id ?? 'N/A',
                            $order->paid_at ? $order->paid_at->toDateTimeString() : 'N/A',
                            $order->created_at->toDateTimeString(),
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Stream Course Performance CSV export.
     */
    public function streamCoursesCsv(?Carbon $start = null, ?Carbon $end = null): StreamedResponse
    {
        $filename = 'course-performance-' . now()->format('Y-m-d-His') . '.csv';
        $reportData = $this->getCoursePerformanceReport($start, $end);

        return response()->streamDownload(function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Course ID',
                'Course Title',
                'Catalog Status',
                'Price (INR)',
                'Is Free',
                'Paid Orders',
                'Net Revenue (INR)',
                'Total Discounts Given (INR)',
                'Total Enrollments',
                'Active Students',
                'Completed Students',
                'Completion Rate (%)',
                'Certificates Issued',
            ]);

            foreach ($reportData as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['title'],
                    $row['status'] instanceof CourseStatus ? $row['status']->value : $row['status'],
                    $row['price'],
                    $row['is_free'] ? 'Yes' : 'No',
                    $row['paid_orders_count'],
                    $row['revenue'],
                    $row['discount'],
                    $row['total_enrollments'],
                    $row['active_enrollments'],
                    $row['completed_enrollments'],
                    $row['completion_rate'] . '%',
                    $row['certificates_issued'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Stream Enrollments CSV export.
     */
    public function streamEnrollmentsCsv(?Carbon $start = null, ?Carbon $end = null): StreamedResponse
    {
        $filename = 'enrollments-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Enrollment ID',
                'Student Name',
                'Student Email',
                'Course Title',
                'Status',
                'Enrolled At',
                'Completed At',
            ]);

            if (Schema::hasTable('enrollments')) {
                $query = Enrollment::with(['user:id,name,email', 'course:id,title']);
                if ($start) {
                    $query->where('enrolled_at', '>=', $start);
                }
                if ($end) {
                    $query->where('enrolled_at', '<=', $end);
                }

                $query->orderBy('id', 'desc')->chunk(100, function ($enrollments) use ($handle) {
                    foreach ($enrollments as $enr) {
                        fputcsv($handle, [
                            $enr->id,
                            $enr->user?->name ?? 'N/A',
                            $enr->user?->email ?? 'N/A',
                            $enr->course?->title ?? 'N/A',
                            $enr->status instanceof EnrollmentStatus ? $enr->status->value : $enr->status,
                            $enr->enrolled_at ? $enr->enrolled_at->toDateTimeString() : 'N/A',
                            $enr->completed_at ? $enr->completed_at->toDateTimeString() : 'N/A',
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}