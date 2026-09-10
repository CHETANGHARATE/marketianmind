<?php

namespace App\Http\Controllers\Student;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of the authenticated student's purchase history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $validStatuses = OrderStatus::values();
        $statusFilter = $request->query('status');

        $query = $user->orders()
            ->with(['course.category', 'payments'])
            ->latest();

        if ($statusFilter && in_array($statusFilter, $validStatuses, true)) {
            $query->where('status', $statusFilter);
        } else {
            $statusFilter = null;
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('student.orders.index', [
            'orders' => $orders,
            'currentStatus' => $statusFilter,
            'statuses' => OrderStatus::cases(),
            'headerTitle' => 'Purchase History',
        ]);
    }

    /**
     * Display details and receipt for a specific order.
     */
    public function show(Request $request, Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['course.category', 'payments']);

        $isEnrolled = $request->user()->isEnrolledIn($order->course);

        return view('student.orders.show', [
            'order' => $order,
            'isEnrolled' => $isEnrolled,
            'headerTitle' => 'Order Details',
        ]);
    }
}