<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\AdminCourseAccessService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class EnrollmentController extends Controller
{
    /**
     * Display a paginated, searchable, filterable listing of platform course enrollments.
     */
    public function index(Request $request): View
    {
        $query = Enrollment::query()
            ->with([
                'user:id,name,email',
                'course:id,title,slug,course_category_id,is_free,price',
                'course.category:id,name',
                'certificate:id,enrollment_id,certificate_number',
                'latestAccessPeriod',
            ]);

        // Aggregate counts for lifecycle and access tabs
        $counts = [
            'all' => Enrollment::count(),
            'active' => Enrollment::accessActive()->count(),
            'expiring' => Enrollment::expiringSoon(30)->count(),
            'expired' => Enrollment::accessExpired()->count(),
            'lifetime' => Enrollment::legacyLifetime()->count(),
            'completed' => Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count(),
            'cancelled' => Enrollment::where('status', EnrollmentStatus::CANCELLED->value)->count(),
            'anomalous' => Enrollment::anomalousAccess()->count(),
        ];

        // Search across student name, student email, or course title
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('course', function ($cq) use ($search) {
                    $cq->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Filter by course slug
        $selectedCourseSlug = trim((string) $request->query('course', ''));
        if ($selectedCourseSlug !== '') {
            $query->whereHas('course', function ($cq) use ($selectedCourseSlug) {
                $cq->where('slug', $selectedCourseSlug);
            });
        }

        // Filter by student ID if specified
        $selectedStudentId = $request->query('student');
        if ($selectedStudentId && is_numeric($selectedStudentId)) {
            $query->where('user_id', (int) $selectedStudentId);
        }

        // Filter by status / access lifecycle tab
        $status = (string) $request->query('status', 'all');
        match ($status) {
            'active' => $query->accessActive(),
            'expiring' => $query->expiringSoon(30),
            'expired' => $query->accessExpired(),
            'lifetime' => $query->legacyLifetime(),
            'completed' => $query->where('status', EnrollmentStatus::COMPLETED->value),
            'cancelled' => $query->where('status', EnrollmentStatus::CANCELLED->value),
            'anomalous' => $query->anomalousAccess(),
            default => null,
        };

        // Filter by completion (in_progress vs completed) if requested
        $completion = (string) $request->query('completion', 'all');
        if ($status === 'all') {
            if ($completion === 'in_progress') {
                $query->where('status', EnrollmentStatus::ACTIVE->value);
            } elseif ($completion === 'completed') {
                $query->where('status', EnrollmentStatus::COMPLETED->value);
            }
        }

        // Whitelisted sorting
        $sort = (string) $request->query('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest('enrollments.id'),
            'expiring_soon' => $query->whereNotNull('expires_at')->orderBy('expires_at', 'asc'),
            'student_asc' => $query->join('users', 'enrollments.user_id', '=', 'users.id')
                ->orderBy('users.name', 'asc')
                ->select('enrollments.*'),
            'student_desc' => $query->join('users', 'enrollments.user_id', '=', 'users.id')
                ->orderBy('users.name', 'desc')
                ->select('enrollments.*'),
            'course_asc' => $query->join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->orderBy('courses.title', 'asc')
                ->select('enrollments.*'),
            'course_desc' => $query->join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->orderBy('courses.title', 'desc')
                ->select('enrollments.*'),
            default => $query->latest('enrollments.id'),
        };

        $courses = Course::select(['id', 'title', 'slug'])->orderBy('title')->get();

        $enrollments = $query->paginate(15)->withQueryString();

        // Calculate progress for each enrollment item on the current page
        foreach ($enrollments as $enrollment) {
            $enrollment->course_progress = $enrollment->course
                ? $enrollment->course->progressFor($enrollment->user)
                : ['total' => 0, 'completed' => 0, 'percentage' => 0, 'is_completed' => false];
        }

        return view('admin.enrollments.index', compact(
            'enrollments',
            'counts',
            'courses',
            'search',
            'selectedCourseSlug',
            'status',
            'completion',
            'sort'
        ));
    }

    /**
     * Display comprehensive enrollment inspector including student, course,
     * learning progress, completion state, order/payment relationship, access period history,
     * and audit trail.
     */
    public function show(Enrollment $enrollment): View
    {
        $enrollment->load([
            'user',
            'course.category',
            'certificate',
            'accessPeriods.order.payments',
        ]);

        $progress = $enrollment->course
            ? $enrollment->course->progressFor($enrollment->user)
            : ['total' => 0, 'completed' => 0, 'percentage' => 0, 'is_completed' => false];

        // Retrieve associated primary Order if a paid purchase exists for this user and course
        $order = Order::with(['payments'])
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->latest()
            ->first();

        // Retrieve all orders associated with this user and course (for renewal history context)
        $allOrders = Order::with(['payments'])
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->latest()
            ->get();

        // Retrieve audit logs for this enrollment
        $auditLogs = AuditLog::with('user')
            ->where('auditable_type', 'Enrollment')
            ->where('auditable_id', $enrollment->id)
            ->latest('id')
            ->take(25)
            ->get();

        return view('admin.enrollments.show', compact(
            'enrollment',
            'progress',
            'order',
            'allOrders',
            'auditLogs'
        ));
    }

    /**
     * Administratively grant or extend course access.
     */
    public function extendAccess(
        Request $request,
        Enrollment $enrollment,
        AdminCourseAccessService $accessService
    ): RedirectResponse {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $accessService->grantOrExtendAccess(
                enrollment: $enrollment,
                days: (int) $validated['days'],
                reason: (string) $validated['reason'],
                admin: $request->user(),
                idempotencyKey: $validated['idempotency_key'] ?? null
            );

            return redirect()
                ->route('admin.enrollments.show', $enrollment)
                ->with('success', "Successfully granted/extended {$validated['days']} days of course access.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Failed to update access: ' . $e->getMessage());
        }
    }
}