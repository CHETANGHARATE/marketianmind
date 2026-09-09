<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a paginated listing of all platform orders with status filters.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $search = $request->query('search');

        $query = Order::query()->with(['user', 'course', 'payments'])->latest();

        if ($statusFilter && in_array($statusFilter, OrderStatus::values())) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        // Aggregate counts for filter tabs
        $counts = [
            'all' => Order::count(),
            'paid' => Order::where('status', OrderStatus::PAID->value)->count(),
            'pending' => Order::where('status', OrderStatus::PENDING->value)->count(),
            'failed' => Order::where('status', OrderStatus::FAILED->value)->count(),
            'refunded' => Order::where('status', OrderStatus::REFUNDED->value)->count(),
        ];

        return view('admin.orders.index', compact('orders', 'counts', 'statusFilter', 'search'));
    }

    /**
     * Display detailed read-only order audit inspector.
     */
    public function show(Order $order): View
    {
        $order->load(['user', 'course', 'payments']);

        return view('admin.orders.show', compact('order'));
    }
}