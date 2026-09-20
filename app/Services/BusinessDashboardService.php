<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BusinessDashboardService
{
    public function __construct(
        protected ReportingService $reportingService
    ) {}

    /**
     * Compile complete business overview and revenue dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(Request $request): array
    {
        $hasUsers = Schema::hasTable('users');
        $hasCourses = Schema::hasTable('courses');
        $hasEnrollments = Schema::hasTable('enrollments');
        $hasOrders = Schema::hasTable('orders');
        $hasLeads = Schema::hasTable('leads');
        $hasPayments = Schema::hasTable('payments');
        $hasAccessPeriods = Schema::hasTable('course_access_periods');

        // 1. Authoritative Date Filtering
        $requestedRange = $request->query('date_range') ?: $request->query('range', '30d');
        $dateFilter = $this->reportingService->parseDateRange(
            $requestedRange,
            $request->query('start_date'),
            $request->query('end_date')
        );

        $start = $dateFilter['start'];
        $end = $dateFilter['end'];
        $range = $dateFilter['range'];

        // 2. Period Revenue & Sales Performance
        $revenueMetrics = $this->calculateRevenueMetrics($start, $end);

        // 3. Daily / Interval Revenue Trends
        $revenueTrend = $this->calculateRevenueTrend($start, $end);

        // 4. Course Performance Breakdown
        $coursePerformance = $this->calculateCoursePerformance($start, $end);

        // 5. Customer Acquisition & Repeat Purchase Metrics
        $customerMetrics = $this->calculateCustomerMetrics($start, $end);

        // 6. Lead-to-Purchase Funnel & Attribution
        $leadFunnel = $this->calculateLeadFunnel($start, $end);

        // 7. Operational Safety & Payment Health
        $operationalHealth = $this->calculateOperationalHealth();

        // 8. Backward-Compatible Lifetime Platform Metrics
        $lifetimeMetrics = $this->calculateLifetimeMetrics();

        // 9. Recent Feeds (eager-loaded)
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

        $recentLeads = $hasLeads
            ? Lead::with(['course:id,title', 'bundle:id,title'])
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

        // Merge lifetime metrics with period values for views that reference $metrics directly
        $mergedMetrics = array_merge($lifetimeMetrics, [
            'period_revenue' => $revenueMetrics['verified_net_revenue'],
            'formatted_period_revenue' => $revenueMetrics['formatted_net_revenue'],
            'period_paid_orders' => $revenueMetrics['paid_orders_count'],
            'period_aov' => $revenueMetrics['avg_order_value'],
            'formatted_period_aov' => $revenueMetrics['formatted_aov'],
            'period_unique_customers' => $revenueMetrics['unique_purchasing_customers'],
            'period_first_time_customers' => $revenueMetrics['first_time_customers'],
            'period_returning_customers' => $revenueMetrics['returning_customers'],
            'repeat_purchase_rate' => $customerMetrics['repeat_purchase_rate'],
            'unfulfilled_paid_orders' => $operationalHealth['unfulfilled_paid_orders'],
        ]);

        return [
            'dateFilter' => $dateFilter,
            'metrics' => $mergedMetrics,
            'revenueMetrics' => $revenueMetrics,
            'revenueTrend' => $revenueTrend,
            'coursePerformance' => $coursePerformance,
            'customerMetrics' => $customerMetrics,
            'leadFunnel' => $leadFunnel,
            'operationalHealth' => $operationalHealth,
            'recentStudents' => $recentStudents,
            'recentOrders' => $recentOrders,
            'recentEnrollments' => $recentEnrollments,
            'recentLeads' => $recentLeads,
            'coursesOverview' => $coursesOverview,
        ];
    }

    /**
     * Compute verified revenue, order volumes, and average order value.
     *
     * @return array<string, mixed>
     */
    protected function calculateRevenueMetrics(?Carbon $start, ?Carbon $end): array
    {
        if (! Schema::hasTable('orders')) {
            return $this->emptyRevenueMetrics();
        }

        // Paid Orders in the requested period
        $paidQuery = Order::where('status', OrderStatus::PAID->value);
        if ($start) {
            $paidQuery->where('paid_at', '>=', $start);
        }
        if ($end) {
            $paidQuery->where('paid_at', '<=', $end);
        }

        $paidOrdersCount = (clone $paidQuery)->count();
        $netRevenuePaise = (int) (clone $paidQuery)->sum('amount');
        $discountPaise = (int) (clone $paidQuery)->sum('discount_amount');

        $netRevenue = round($netRevenuePaise / 100, 2);
        $discountAmount = round($discountPaise / 100, 2);
        $grossSales = round(($netRevenuePaise + $discountPaise) / 100, 2);

        $avgOrderValue = $paidOrdersCount > 0 ? round($netRevenue / $paidOrdersCount, 2) : 0.0;

        // Order status breakdown for period
        $periodOrdersQuery = Order::query();
        if ($start) {
            $periodOrdersQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $periodOrdersQuery->where('created_at', '<=', $end);
        }

        $pendingCount = (clone $periodOrdersQuery)->where('status', OrderStatus::PENDING->value)->count();
        $failedCount = (clone $periodOrdersQuery)->where('status', OrderStatus::FAILED->value)->count();
        $cancelledCount = (clone $periodOrdersQuery)->where('status', OrderStatus::CANCELLED->value)->count();
        $refundedCount = (clone $periodOrdersQuery)->where('status', OrderStatus::REFUNDED->value)->count();
        $refundedPaise = (int) (clone $periodOrdersQuery)->where('status', OrderStatus::REFUNDED->value)->sum('amount');

        // Unique Customers in Period
        $periodPaidOrders = (clone $paidQuery)->select(['id', 'user_id', 'amount', 'paid_at', 'metadata', 'order_type'])->get();
        $purchaserIds = $periodPaidOrders->pluck('user_id')->unique()->filter()->values();
        $uniquePurchasingCustomers = $purchaserIds->count();

        // Distinguish First-time Buyers vs Returning Buyers
        $firstTimeCount = 0;
        $returningCount = 0;
        $firstTimeRevenuePaise = 0;
        $returningRevenuePaise = 0;
        $renewalOrdersCount = 0;
        $renewalRevenuePaise = 0;

        if ($uniquePurchasingCustomers > 0) {
            $earliestPaidDates = Order::where('status', OrderStatus::PAID->value)
                ->whereIn('user_id', $purchaserIds)
                ->groupBy('user_id')
                ->selectRaw('user_id, MIN(paid_at) as earliest_paid_at')
                ->pluck('earliest_paid_at', 'user_id');

            foreach ($purchaserIds as $uid) {
                $earliest = $earliestPaidDates->get($uid);
                $isFirstTime = $start ? ($earliest && Carbon::parse($earliest)->gte($start)) : true;
                if ($isFirstTime) {
                    $firstTimeCount++;
                } else {
                    $returningCount++;
                }
            }

            foreach ($periodPaidOrders as $pOrder) {
                $earliest = $earliestPaidDates->get($pOrder->user_id);
                $isFirstTime = $start ? ($earliest && Carbon::parse($earliest)->gte($start)) : true;
                if ($isFirstTime) {
                    $firstTimeRevenuePaise += $pOrder->amount;
                } else {
                    $returningRevenuePaise += $pOrder->amount;
                }

                $isRenewal = ($pOrder->order_type === 'renewal')
                    || (($pOrder->metadata['purchase_type'] ?? null) === 'renewal');
                if ($isRenewal) {
                    $renewalOrdersCount++;
                    $renewalRevenuePaise += $pOrder->amount;
                }
            }
        }

        $firstTimeRevenue = round($firstTimeRevenuePaise / 100, 2);
        $returningRevenue = round($returningRevenuePaise / 100, 2);
        $renewalRevenue = round($renewalRevenuePaise / 100, 2);

        return [
            'verified_gross_sales' => $grossSales,
            'formatted_gross_sales' => '₹' . number_format($grossSales, 2),
            'verified_net_revenue' => $netRevenue,
            'formatted_net_revenue' => '₹' . number_format($netRevenue, 2),
            'total_discount' => $discountAmount,
            'total_discounts' => $discountAmount,
            'formatted_discount' => '₹' . number_format($discountAmount, 2),
            'formatted_total_discounts' => '₹' . number_format($discountAmount, 2),
            'paid_orders_count' => $paidOrdersCount,
            'avg_order_value' => $avgOrderValue,
            'average_order_value' => $avgOrderValue,
            'formatted_aov' => '₹' . number_format($avgOrderValue, 2),
            'unique_purchasing_customers' => $uniquePurchasingCustomers,
            'unique_paying_customers' => $uniquePurchasingCustomers,
            'first_time_customers' => $firstTimeCount,
            'first_time_buyers_count' => $firstTimeCount,
            'returning_customers' => $returningCount,
            'returning_buyers_count' => $returningCount,
            'first_time_revenue' => $firstTimeRevenue,
            'first_time_buyers_revenue' => $firstTimeRevenue,
            'formatted_first_time_revenue' => '₹' . number_format($firstTimeRevenue, 2),
            'returning_revenue' => $returningRevenue,
            'returning_buyers_revenue' => $returningRevenue,
            'formatted_returning_revenue' => '₹' . number_format($returningRevenue, 2),
            'renewal_orders_count' => $renewalOrdersCount,
            'renewals_count' => $renewalOrdersCount,
            'renewal_revenue' => $renewalRevenue,
            'formatted_renewal_revenue' => '₹' . number_format($renewalRevenue, 2),
            'pending_orders_count' => $pendingCount,
            'failed_orders_count' => $failedCount,
            'cancelled_orders_count' => $cancelledCount,
            'refunded_orders_count' => $refundedCount,
            'refunded_amount' => round($refundedPaise / 100, 2),
            'formatted_refunded_amount' => '₹' . number_format(round($refundedPaise / 100, 2), 2),
            'currency' => 'INR',
        ];
    }

    /**
     * Compute daily revenue trends over the selected period.
     */
    protected function calculateRevenueTrend(?Carbon $start, ?Carbon $end): array
    {
        $emptyTrend = [
            'days' => [],
            'max_daily_revenue' => 0.0,
            'total_formatted_revenue' => '₹0.00',
        ];

        if (! Schema::hasTable('orders')) {
            return $emptyTrend;
        }

        $paidQuery = Order::where('status', OrderStatus::PAID->value);
        if ($start) {
            $paidQuery->where('paid_at', '>=', $start);
        }
        if ($end) {
            $paidQuery->where('paid_at', '<=', $end);
        }

        $orders = $paidQuery->select(['id', 'user_id', 'amount', 'discount_amount', 'paid_at', 'created_at'])
            ->orderBy('paid_at', 'asc')
            ->get();

        if ($orders->isEmpty()) {
            return $emptyTrend;
        }

        // Get earliest paid dates for user segmentation
        $userIds = $orders->pluck('user_id')->unique()->filter()->values();
        $earliestPaidDates = Order::where('status', OrderStatus::PAID->value)
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, MIN(paid_at) as earliest_paid_at')
            ->pluck('earliest_paid_at', 'user_id');

        $dailyGroups = $orders->groupBy(function ($order) {
            return $order->paid_at ? $order->paid_at->format('Y-m-d') : $order->created_at->format('Y-m-d');
        });

        $maxDailyRevenue = 0;

        $trend = $dailyGroups->map(function ($dayOrders, $date) use ($earliestPaidDates, $start, &$maxDailyRevenue) {
            $revPaise = $dayOrders->sum('amount');
            $revenue = round($revPaise / 100, 2);
            if ($revenue > $maxDailyRevenue) {
                $maxDailyRevenue = $revenue;
            }

            $firstTimePaise = 0;
            $returningPaise = 0;

            foreach ($dayOrders as $ord) {
                $earliest = $earliestPaidDates->get($ord->user_id);
                $isFirstTime = $start ? ($earliest && Carbon::parse($earliest)->gte($start)) : true;
                if ($isFirstTime) {
                    $firstTimePaise += $ord->amount;
                } else {
                    $returningPaise += $ord->amount;
                }
            }

            return [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->format('M d'),
                'orders_count' => $dayOrders->count(),
                'revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
                'first_time_revenue' => round($firstTimePaise / 100, 2),
                'returning_revenue' => round($returningPaise / 100, 2),
            ];
        })->values();

        $days = $trend->map(function ($item) use ($maxDailyRevenue) {
            $item['height_percentage'] = $maxDailyRevenue > 0
                ? max(6, min(100, round(($item['revenue'] / $maxDailyRevenue) * 100)))
                : 0;
            $item['bar_height_percentage'] = $item['height_percentage'];
            $item['label'] = $item['formatted_date'];
            return $item;
        })->values()->toArray();

        return [
            'days' => $days,
            'max_daily_revenue' => $maxDailyRevenue,
            'total_formatted_revenue' => '₹' . number_format($trend->sum('revenue'), 2),
        ];
    }

    /**
     * Compute course performance metrics across sales, access, and academic completion.
     */
    protected function calculateCoursePerformance(?Carbon $start, ?Carbon $end): Collection
    {
        if (! Schema::hasTable('courses')) {
            return collect();
        }

        $courses = Course::with('category:id,name')->get();
        if ($courses->isEmpty()) {
            return collect();
        }

        // Aggregate orders per course in period
        $orderAggregates = Order::where('status', OrderStatus::PAID->value)
            ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('paid_at', '<=', $end))
            ->selectRaw('course_id, count(id) as paid_orders_count, sum(amount) as revenue_paise, count(distinct user_id) as unique_buyers_count')
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        // Aggregate enrollments per course
        $now = now();
        $enrollmentAggregates = Enrollment::selectRaw('
            course_id,
            count(id) as total_enrollments,
            sum(case when status in (?, ?) and (starts_at is null or starts_at <= ?) and (expires_at is null or expires_at > ?) then 1 else 0 end) as active_count,
            sum(case when status = ? or (expires_at is not null and expires_at <= ?) then 1 else 0 end) as expired_count,
            sum(case when status = ? then 1 else 0 end) as completed_count
        ', [
            EnrollmentStatus::ACTIVE->value,
            EnrollmentStatus::COMPLETED->value,
            $now,
            $now,
            EnrollmentStatus::EXPIRED->value,
            $now,
            EnrollmentStatus::COMPLETED->value,
        ])
        ->groupBy('course_id')
        ->get()
        ->keyBy('course_id');

        return $courses->map(function (Course $course) use ($orderAggregates, $enrollmentAggregates) {
            $ord = $orderAggregates->get($course->id);
            $enr = $enrollmentAggregates->get($course->id);

            $paidOrdersCount = $ord ? (int) $ord->paid_orders_count : 0;
            $revenuePaise = $ord ? (int) $ord->revenue_paise : 0;
            $revenue = round($revenuePaise / 100, 2);
            $uniqueBuyers = $ord ? (int) $ord->unique_buyers_count : 0;

            $totalEnrollments = $enr ? (int) $enr->total_enrollments : 0;
            $activeCount = $enr ? (int) $enr->active_count : 0;
            $expiredCount = $enr ? (int) $enr->expired_count : 0;
            $completedCount = $enr ? (int) $enr->completed_count : 0;
            $completionRate = $totalEnrollments > 0 ? round(($completedCount / $totalEnrollments) * 100, 1) : 0.0;

            $statusStr = $course->status instanceof \BackedEnum ? $course->status->value : (string) $course->status;

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'category' => $course->category?->name ?? 'General',
                'status' => $statusStr,
                'price' => $course->price,
                'is_free' => (bool) $course->is_free,
                'formatted_price' => $course->is_free ? 'Free' : '₹' . number_format($course->effectivePrice(), 2),
                'paid_orders_count' => $paidOrdersCount,
                'period_paid_orders_count' => $paidOrdersCount,
                'revenue' => $revenue,
                'formatted_revenue' => '₹' . number_format($revenue, 2),
                'formatted_period_revenue' => '₹' . number_format($revenue, 2),
                'unique_buyers' => $uniqueBuyers,
                'unique_buyers_count' => $uniqueBuyers,
                'active_students' => $activeCount,
                'active_access_count' => $activeCount,
                'expired_students' => $expiredCount,
                'expired_access_count' => $expiredCount,
                'total_enrollments_count' => $totalEnrollments,
                'completed_students' => $completedCount,
                'completed_enrollments_count' => $completedCount,
                'completion_rate' => $completionRate,
            ];
        })->sortByDesc('revenue')->values();
    }

    /**
     * Compute customer acquisition, repeat purchase, and active access metrics.
     *
     * @return array<string, mixed>
     */
    protected function calculateCustomerMetrics(?Carbon $start, ?Carbon $end): array
    {
        $hasUsers = Schema::hasTable('users');
        $hasOrders = Schema::hasTable('orders');
        $hasEnrollments = Schema::hasTable('enrollments');

        $totalStudents = $hasUsers ? User::where('role', UserRole::STUDENT->value)->count() : 0;
        $newStudentsInPeriod = $hasUsers
            ? User::where('role', UserRole::STUDENT->value)
                ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
                ->count()
            : 0;

        $allUniquePurchasingCustomers = $hasOrders
            ? Order::where('status', OrderStatus::PAID->value)->distinct('user_id')->count('user_id')
            : 0;

        $repeatPurchasingCustomers = $hasOrders
            ? Order::where('status', OrderStatus::PAID->value)
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(id) >= 2')
                ->get()
                ->count()
            : 0;

        $repeatPurchaseRate = $allUniquePurchasingCustomers > 0
            ? round(($repeatPurchasingCustomers / $allUniquePurchasingCustomers) * 100, 1)
            : 0.0;

        // Platform-wide Active vs Expired Students
        $now = now();
        $totalActiveStudents = $hasEnrollments
            ? Enrollment::whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->distinct('user_id')
                ->count('user_id')
            : 0;

        $totalExpiredEnrollments = $hasEnrollments
            ? Enrollment::where('status', EnrollmentStatus::EXPIRED->value)
                ->orWhere(function ($q) use ($now) {
                    $q->whereNotNull('expires_at')->where('expires_at', '<=', $now);
                })
                ->count()
            : 0;

        $accessExpiringSoon = $hasEnrollments
            ? Enrollment::whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', $now)
                ->where('expires_at', '<=', $now->copy()->addDays(30))
                ->count()
            : 0;

        return [
            'new_registered_students' => $newStudentsInPeriod,
            'period_registered_students' => $newStudentsInPeriod,
            'all_unique_purchasing_customers' => $allUniquePurchasingCustomers,
            'repeat_purchasing_customers' => $repeatPurchasingCustomers,
            'repeat_purchase_rate' => $repeatPurchaseRate,
            'total_registered_students' => $totalStudents,
            'total_active_students' => $totalActiveStudents,
            'active_access_count' => $totalActiveStudents,
            'total_expired_enrollments' => $totalExpiredEnrollments,
            'expired_access_count' => $totalExpiredEnrollments,
            'access_expiring_soon' => $accessExpiringSoon,
            'expiring_soon_count' => $accessExpiringSoon,
        ];
    }

    /**
     * Compute lead-to-purchase conversion and attribution channel performance.
     *
     * @return array<string, mixed>
     */
    protected function calculateLeadFunnel(?Carbon $start, ?Carbon $end): array
    {
        if (! Schema::hasTable('leads')) {
            return [
                'total_leads' => 0,
                'period_leads_count' => 0,
                'leads_with_course' => 0,
                'course_leads_count' => 0,
                'converted_leads' => 0,
                'converted_leads_count' => 0,
                'conversion_rate' => 0.0,
                'attribution_sources' => collect(),
                'top_sources' => collect(),
            ];
        }

        $leadQuery = Lead::query();
        if ($start) {
            $leadQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $leadQuery->where('created_at', '<=', $end);
        }

        $totalLeads = (clone $leadQuery)->count();
        $leadsWithCourse = (clone $leadQuery)->whereNotNull('course_id')->count();
        $convertedLeads = (clone $leadQuery)->where('status', LeadStatus::CONVERTED->value)->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0.0;

        $attributionSources = (clone $leadQuery)
            ->whereNotNull('source')
            ->selectRaw('source, count(id) as total_leads, sum(case when status = ? then 1 else 0 end) as converted_count', [LeadStatus::CONVERTED->value])
            ->groupBy('source')
            ->orderByDesc('total_leads')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_leads;
                $converted = (int) $row->converted_count;
                $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0.0;
                return [
                    'source' => $row->source ?: 'Direct / Organic',
                    'count' => $total,
                    'total_leads' => $total,
                    'converted_count' => $converted,
                    'conversion_rate' => $rate,
                ];
            });

        return [
            'total_leads' => $totalLeads,
            'period_leads_count' => $totalLeads,
            'leads_with_course' => $leadsWithCourse,
            'course_leads_count' => $leadsWithCourse,
            'converted_leads' => $convertedLeads,
            'converted_leads_count' => $convertedLeads,
            'conversion_rate' => $conversionRate,
            'attribution_sources' => $attributionSources,
            'top_sources' => $attributionSources,
        ];
    }

    /**
     * Compute real-time payment health, fulfillment integrity, and alerts.
     *
     * @return array<string, mixed>
     */
    protected function calculateOperationalHealth(): array
    {
        $hasOrders = Schema::hasTable('orders');
        $hasPayments = Schema::hasTable('payments');

        if (! $hasOrders) {
            return [
                'unfulfilled_paid_orders' => 0,
                'unfulfilled_orders_count' => 0,
                'unfulfilled_orders_list' => collect(),
                'recent_failed_payments' => [],
                'failed_payments_count' => 0,
                'pending_orders_count' => 0,
                'status' => 'healthy',
            ];
        }

        // Unfulfilled Paid Orders: order is paid, but no access periods were created
        $unfulfilledQuery = Order::where('status', OrderStatus::PAID->value)
            ->whereDoesntHave('accessPeriods');

        $unfulfilledCount = (clone $unfulfilledQuery)->count();
        $unfulfilledList = (clone $unfulfilledQuery)
            ->with(['user:id,name,email', 'course:id,title'])
            ->latest('paid_at')
            ->take(5)
            ->get();

        // Recent failed payment attempts
        $recentFailedPayments = $hasPayments
            ? Payment::where('status', PaymentStatus::FAILED->value)
                ->with(['user:id,name,email', 'order:id,order_number,course_id', 'order.course:id,title'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function (Payment $payment) {
                    return [
                        'id' => $payment->id,
                        'payment_id' => $payment->razorpay_payment_id,
                        'order_id' => $payment->order_id,
                        'order_number' => $payment->order?->order_number ?? 'N/A',
                        'user_name' => $payment->user?->name ?? 'Student',
                        'user_email' => $payment->user?->email ?? 'N/A',
                        'course_title' => $payment->order?->course?->title ?? 'Course',
                        'amount' => $payment->amount,
                        'formatted_amount' => '₹' . number_format($payment->amount / 100, 2),
                        'error_description' => $payment->failure_description ?: ($payment->failure_code ?: 'Gateway transaction failed'),
                        'failure_description' => $payment->failure_description ?: ($payment->failure_code ?: 'Gateway transaction failed'),
                        'failure_code' => $payment->failure_code,
                        'date' => $payment->created_at?->diffForHumans() ?? 'Recently',
                    ];
                })->toArray()
            : [];

        $pendingCount = Order::where('status', OrderStatus::PENDING->value)->count();

        $status = ($unfulfilledCount > 0) ? 'attention_required' : 'healthy';

        return [
            'unfulfilled_paid_orders' => $unfulfilledCount,
            'unfulfilled_orders_count' => $unfulfilledCount,
            'unfulfilled_orders_list' => $unfulfilledList,
            'recent_failed_payments' => $recentFailedPayments,
            'failed_payments_count' => count($recentFailedPayments),
            'pending_orders_count' => $pendingCount,
            'status' => $status,
        ];
    }

    /**
     * Compute all-time lifetime metrics for full backward compatibility with AdminDashboardTest.
     *
     * @return array<string, mixed>
     */
    protected function calculateLifetimeMetrics(): array
    {
        $hasUsers = Schema::hasTable('users');
        $hasCourses = Schema::hasTable('courses');
        $hasEnrollments = Schema::hasTable('enrollments');
        $hasOrders = Schema::hasTable('orders');
        $hasLeads = Schema::hasTable('leads');
        $hasAccessPeriods = Schema::hasTable('course_access_periods');

        $totalStudents = $hasUsers ? User::where('role', UserRole::STUDENT->value)->count() : 0;
        $newStudents30d = $hasUsers
            ? User::where('role', UserRole::STUDENT->value)->where('created_at', '>=', now()->subDays(30))->count()
            : 0;

        $totalCourses = $hasCourses ? Course::count() : 0;
        $publishedCourses = $hasCourses ? Course::where('status', CourseStatus::PUBLISHED->value)->count() : 0;
        $draftCourses = $hasCourses ? Course::where('status', CourseStatus::DRAFT->value)->count() : 0;
        $featuredCourses = $hasCourses ? Course::where('featured', true)->count() : 0;

        $totalEnrollments = $hasEnrollments ? Enrollment::count() : 0;
        $activeEnrollments = $hasEnrollments ? Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count() : 0;
        $completedEnrollments = $hasEnrollments ? Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count() : 0;
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0;

        $totalOrders = $hasOrders ? Order::count() : 0;
        $paidOrders = $hasOrders ? Order::where('status', OrderStatus::PAID->value)->count() : 0;
        $pendingOrders = $hasOrders ? Order::where('status', OrderStatus::PENDING->value)->count() : 0;
        $failedOrders = $hasOrders ? Order::where('status', OrderStatus::FAILED->value)->count() : 0;
        $cancelledOrders = $hasOrders ? Order::where('status', OrderStatus::CANCELLED->value)->count() : 0;
        $totalRevenuePaise = $hasOrders ? (int) Order::where('status', OrderStatus::PAID->value)->sum('amount') : 0;
        $totalRevenue = round($totalRevenuePaise / 100, 2);

        $totalLeads = $hasLeads ? Lead::count() : 0;
        $newLeads = $hasLeads ? Lead::where('status', LeadStatus::NEW->value)->count() : 0;
        $convertedLeads = $hasLeads ? Lead::where('status', LeadStatus::CONVERTED->value)->count() : 0;
        $dueFollowUps = $hasLeads ? Lead::dueTodayFollowUps()->count() : 0;
        $overdueFollowUps = $hasLeads ? Lead::overdueFollowUps()->count() : 0;
        $leadConversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        $renewalsThisMonth = $hasAccessPeriods
            ? CourseAccessPeriod::where('period_type', 'renewal')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count()
            : 0;

        $renewalRevenueThisMonthPaise = $hasOrders
            ? (int) Order::where('status', OrderStatus::PAID->value)
                ->where(function ($q) {
                    $q->where('metadata->purchase_type', 'renewal')
                      ->orWhereHas('accessPeriods', fn ($p) => $p->where('period_type', 'renewal'));
                })
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount')
            : 0;
        $renewalRevenueThisMonth = round($renewalRevenueThisMonthPaise / 100, 2);

        $expiringSoon = $hasEnrollments
            ? Enrollment::whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(30))
                ->count()
            : 0;

        return [
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
            'total_leads' => $totalLeads,
            'new_leads' => $newLeads,
            'converted_leads' => $convertedLeads,
            'due_follow_ups' => $dueFollowUps,
            'overdue_follow_ups' => $overdueFollowUps,
            'lead_conversion_rate' => $leadConversionRate,
            'renewals_this_month' => $renewalsThisMonth,
            'renewal_revenue_this_month' => $renewalRevenueThisMonth,
            'formatted_renewal_revenue' => '₹' . number_format($renewalRevenueThisMonth, 2),
            'expiring_soon' => $expiringSoon,
        ];
    }

    /**
     * Empty revenue metrics fallback.
     */
    protected function emptyRevenueMetrics(): array
    {
        return [
            'verified_gross_sales' => 0.0,
            'formatted_gross_sales' => '₹0.00',
            'verified_net_revenue' => 0.0,
            'formatted_net_revenue' => '₹0.00',
            'total_discount' => 0.0,
            'formatted_discount' => '₹0.00',
            'paid_orders_count' => 0,
            'avg_order_value' => 0.0,
            'formatted_aov' => '₹0.00',
            'unique_purchasing_customers' => 0,
            'first_time_customers' => 0,
            'returning_customers' => 0,
            'first_time_revenue' => 0.0,
            'formatted_first_time_revenue' => '₹0.00',
            'returning_revenue' => 0.0,
            'formatted_returning_revenue' => '₹0.00',
            'renewal_orders_count' => 0,
            'renewal_revenue' => 0.0,
            'formatted_renewal_revenue' => '₹0.00',
            'pending_orders_count' => 0,
            'failed_orders_count' => 0,
            'cancelled_orders_count' => 0,
            'refunded_orders_count' => 0,
            'refunded_amount' => 0.0,
            'formatted_refunded_amount' => '₹0.00',
            'currency' => 'INR',
        ];
    }
}
