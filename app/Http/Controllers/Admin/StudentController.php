<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a paginated, searchable, filterable listing of students.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', UserRole::STUDENT->value)
            ->withCount([
                'enrollments',
                'orders as paid_orders_count' => function ($q) {
                    $q->where('status', OrderStatus::PAID->value);
                },
            ]);

        // Aggregate counts for filter tabs
        $counts = [
            'all' => User::where('role', UserRole::STUDENT->value)->count(),
            'with_enrollments' => User::where('role', UserRole::STUDENT->value)->has('enrollments')->count(),
            'without_enrollments' => User::where('role', UserRole::STUDENT->value)->doesntHave('enrollments')->count(),
            'recent' => User::where('role', UserRole::STUDENT->value)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Search across name or email
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Whitelisted filters
        $filter = (string) $request->query('filter', 'all');
        if ($filter === 'with_enrollments') {
            $query->has('enrollments');
        } elseif ($filter === 'without_enrollments') {
            $query->doesntHave('enrollments');
        } elseif ($filter === 'recent') {
            $query->where('created_at', '>=', now()->subDays(30));
        } else {
            $filter = 'all';
        }

        // Whitelisted sorting
        $sort = (string) $request->query('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'enrollments_desc' => $query->orderByDesc('enrollments_count'),
            default => $query->latest('id'),
        };

        $students = $query->paginate(15)->withQueryString();

        return view('admin.students.index', compact(
            'students',
            'counts',
            'search',
            'filter',
            'sort'
        ));
    }

    /**
     * Display comprehensive student audit inspector including enrollments, progress,
     * purchase history, and certificates.
     */
    public function show(User $student): View
    {
        // Enforce strict student role authorization to prevent role pollution and IDOR
        abort_unless($student->isStudent(), 404);

        $totalEnrollments = $student->enrollments()->count();
        $completedCourses = $student->enrollments()->where('status', EnrollmentStatus::COMPLETED->value)->count();
        $inProgressCourses = $student->enrollments()->where('status', EnrollmentStatus::ACTIVE->value)->count();

        $paidOrders = $student->orders()->where('status', OrderStatus::PAID->value)->count();
        $totalPaidPaise = (int) $student->orders()->where('status', OrderStatus::PAID->value)->sum('amount');
        $totalPaidRevenue = round($totalPaidPaise / 100, 2);

        $certificatesCount = $student->certificates()->count();

        $stats = [
            'total_enrollments' => $totalEnrollments,
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $inProgressCourses,
            'paid_orders' => $paidOrders,
            'total_paid_revenue' => $totalPaidRevenue,
            'formatted_paid_revenue' => '₹' . number_format($totalPaidRevenue, 2),
            'certificates_count' => $certificatesCount,
        ];

        // Paginated enrollments with calculated progress per course
        $enrollments = $student->enrollments()
            ->with(['course.category'])
            ->latest()
            ->paginate(10, ['*'], 'enrollments_page')
            ->withQueryString();

        // Calculate progress for each enrollment safely using existing model logic
        foreach ($enrollments as $enrollment) {
            $enrollment->course_progress = $enrollment->course
                ? $enrollment->course->progressFor($student)
                : ['total' => 0, 'completed' => 0, 'percentage' => 0, 'is_completed' => false];
        }

        // Paginated orders
        $orders = $student->orders()
            ->with(['course:id,title,slug'])
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        // Paginated certificates
        $certificates = $student->certificates()
            ->with(['course:id,title,slug'])
            ->latest()
            ->paginate(10, ['*'], 'certificates_page')
            ->withQueryString();

        return view('admin.students.show', compact(
            'student',
            'stats',
            'enrollments',
            'orders',
            'certificates'
        ));
    }
}