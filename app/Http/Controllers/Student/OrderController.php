<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of the student's purchase history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with(['course', 'payments'])
            ->latest()
            ->paginate(10);

        return view('student.orders.index', compact('orders'));
    }

    /**
     * Display details and receipt for a specific order.
     */
    public function show(Request $request, Order $order): View
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this order.');
        }

        $order->load(['course', 'payments']);

        return view('student.orders.show', compact('order'));
    }
}