<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a paginated listing of all platform orders with advanced search, filters, and KPI metrics.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $paymentStatusFilter = $request->query('payment_status');
        $courseFilter = $request->query('course');
        $dateFilter = $request->query('date');
        $sort = $request->query('sort', 'newest');
        $search = trim((string) $request->query('search', ''));

        $query = Order::query()->with([
            'user',
            'course.category',
            'payments' => fn ($q) => $q->latest(),
        ]);

        // Search Filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function ($cq) use ($search) {
                        $cq->where('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payments', function ($pq) use ($search) {
                        $pq->where('razorpay_payment_id', 'like', "%{$search}%");
                    });
            });
        }

        // Order Status Filter
        $validOrderStatuses = OrderStatus::values();
        if ($statusFilter && in_array($statusFilter, $validOrderStatuses, true)) {
            $query->where('status', $statusFilter);
        } else {
            $statusFilter = null;
        }

        // Payment Status Filter
        $validPaymentStatuses = PaymentStatus::values();
        if ($paymentStatusFilter && in_array($paymentStatusFilter, $validPaymentStatuses, true)) {
            $query->whereHas('payments', function ($pq) use ($paymentStatusFilter) {
                $pq->where('status', $paymentStatusFilter);
            });
        } else {
            $paymentStatusFilter = null;
        }

        // Course Filter
        if ($courseFilter) {
            $query->whereHas('course', function ($cq) use ($courseFilter) {
                $cq->where('slug', $courseFilter);
            });
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } else {
            $dateFilter = null;
        }

        // Whitelisted Sorting
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'amount_high' => $query->orderBy('amount', 'desc'),
            'amount_low' => $query->orderBy('amount', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $orders = $query->paginate(15)->withQueryString();

        // Revenue calculation: authoritative from paid orders, in paise converted to INR
        // Multiple payments per order do not double count!
        $totalRevenuePaise = (int) Order::where('status', OrderStatus::PAID->value)->sum('amount');
        $totalRevenue = round($totalRevenuePaise / 100, 2);

        $metrics = [
            'total_orders' => Order::count(),
            'paid_orders' => Order::where('status', OrderStatus::PAID->value)->count(),
            'pending_orders' => Order::where('status', OrderStatus::PENDING->value)->count(),
            'failed_orders' => Order::where('status', OrderStatus::FAILED->value)->count(),
            'cancelled_orders' => Order::where('status', OrderStatus::CANCELLED->value)->count(),
            'refunded_orders' => Order::where('status', OrderStatus::REFUNDED->value)->count(),
            'total_revenue' => $totalRevenue,
            'formatted_revenue' => '₹' . number_format($totalRevenue, 2),
        ];

        $courses = Course::orderBy('title')->get(['id', 'title', 'slug']);

        return view('admin.orders.index', [
            'orders' => $orders,
            'metrics' => $metrics,
            'courses' => $courses,
            'orderStatuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'currentStatus' => $statusFilter,
            'currentPaymentStatus' => $paymentStatusFilter,
            'currentCourse' => $courseFilter,
            'currentDate' => $dateFilter,
            'currentSort' => $sort,
            'search' => $search,
        ]);
    }

    /**
     * Display detailed read-only order audit inspector.
     */
    public function show(Order $order): View
    {
        $order->load([
            'user',
            'course.category',
            'payments' => fn ($q) => $q->latest(),
        ]);

        $enrollment = Enrollment::query()
            ->where('user_id', $order->user_id)
            ->where('course_id', $order->course_id)
            ->first();

        return view('admin.orders.show', [
            'order' => $order,
            'enrollment' => $enrollment,
        ]);
    }
}