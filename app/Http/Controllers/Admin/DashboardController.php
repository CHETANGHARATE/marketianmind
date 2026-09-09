<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the Admin Portal dashboard with platform metrics.
     */
    public function index(): View
    {
        $hasOrdersTable = Schema::hasTable('orders');

        $metrics = [
            'total_courses' => Schema::hasTable('courses') ? Course::count() : 0,
            'total_students' => Schema::hasTable('users') ? User::where('role', \App\Enums\UserRole::STUDENT->value)->count() : 0,
            'total_orders' => $hasOrdersTable ? Order::count() : 0,
            'paid_orders' => $hasOrdersTable ? Order::where('status', OrderStatus::PAID->value)->count() : 0,
            'pending_orders' => $hasOrdersTable ? Order::where('status', OrderStatus::PENDING->value)->count() : 0,
            'total_revenue' => $hasOrdersTable ? round(Order::where('status', OrderStatus::PAID->value)->sum('amount') / 100, 2) : 0.00,
        ];

        return view('admin.dashboard', compact('metrics'));
    }
}
