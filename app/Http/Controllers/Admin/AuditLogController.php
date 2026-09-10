<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Whitelisted actions for filtering.
     *
     * @var array<int, string>
     */
    protected array $allowedActions = [
        'created',
        'updated',
        'published',
        'unpublished',
        'deleted',
    ];

    /**
     * Whitelisted resource types for filtering.
     *
     * @var array<string, string>
     */
    protected array $allowedResources = [
        'Course' => 'Course',
        'CourseCategory' => 'Course Category',
        'CourseModule' => 'Curriculum Module',
        'Lesson' => 'Lesson',
    ];

    /**
     * Display a paginated, searchable, filterable listing of administrative audit logs.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $actionFilter = $request->query('action');
        $resourceFilter = $request->query('resource');
        $dateRangeFilter = $request->query('date_range');
        $adminIdFilter = $request->query('admin_id');
        $sort = $request->query('sort', 'newest');

        $query = AuditLog::query()->with(['user:id,name,email']);

        // Search across description, resource_label, or admin_name
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('resource_label', 'like', "%{$search}%")
                    ->orWhere('admin_name', 'like', "%{$search}%");
            });
        }

        // Filter by Action
        if ($actionFilter && in_array(strtolower($actionFilter), $this->allowedActions, true)) {
            $query->where('action', strtolower($actionFilter));
        }

        // Filter by Resource Type
        if ($resourceFilter && array_key_exists($resourceFilter, $this->allowedResources)) {
            $query->where('auditable_type', $resourceFilter);
        }

        // Filter by Date Range
        if ($dateRangeFilter) {
            match ($dateRangeFilter) {
                'today' => $query->where('created_at', '>=', Carbon::today()),
                '7d' => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
                '30d' => $query->where('created_at', '>=', Carbon::now()->subDays(30)),
                default => null,
            };
        }

        // Filter by Admin Actor
        if ($adminIdFilter && is_numeric($adminIdFilter)) {
            $query->where('user_id', (int) $adminIdFilter);
        }

        // Sorting (Whitelisted)
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        } else {
            $sort = 'newest';
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $auditLogs = $query->paginate(20)->withQueryString();

        // Metrics for summary cards
        $metrics = [
            'total_logs' => AuditLog::count(),
            'today_logs' => AuditLog::where('created_at', '>=', Carbon::today())->count(),
            'past_7_days_logs' => AuditLog::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'total_admins' => User::where('role', UserRole::ADMIN->value)->count(),
        ];

        // Admins for dropdown
        $admins = User::where('role', UserRole::ADMIN->value)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', [
            'auditLogs' => $auditLogs,
            'metrics' => $metrics,
            'admins' => $admins,
            'allowedActions' => $this->allowedActions,
            'allowedResources' => $this->allowedResources,
            'currentSearch' => $search,
            'currentAction' => $actionFilter,
            'currentResource' => $resourceFilter,
            'currentDateRange' => $dateRangeFilter,
            'currentAdminId' => $adminIdFilter,
            'currentSort' => $sort,
        ]);
    }

    /**
     * Display the read-only details of a specific audit log record.
     */
    public function show(AuditLog $auditLog): View
    {
        $auditLog->loadMissing(['user:id,name,email']);

        return view('admin.audit-logs.show', [
            'auditLog' => $auditLog,
        ]);
    }
}