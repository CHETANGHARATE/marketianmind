<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Services\BusinessDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the Admin Portal dashboard with comprehensive platform metrics,
     * recent activity feeds, financial reporting, and performance indicators.
     */
    public function index(Request $request, BusinessDashboardService $dashboardService): View
    {
        $data = $dashboardService->getDashboardData($request);

        return view('admin.dashboard', $data);
    }
}

