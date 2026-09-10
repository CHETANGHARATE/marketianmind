<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            ]);

        // Aggregate counts for filter tabs
        $counts = [
            'all' => Enrollment::count(),
            'active' => Enrollment::where('status', EnrollmentStatus::ACTIVE->value)->count(),
            'completed' => Enrollment::where('status', EnrollmentStatus::COMPLETED->value)->count(),
            'cancelled' => Enrollment::where('status', EnrollmentStatus::CANCELLED->value)->count(),
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

        // Filter by status (active, completed, cancelled)
        $status = (string) $request->query('status', 'all');
        if (in_array($status, [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value, EnrollmentStatus::CANCELLED->value], true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        // Filter by completion (in_progress vs completed) if status is all
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
     * learning progress, completion state, order/payment relationship, and certificate.
     */
    public function show(Enrollment $enrollment): View
    {
        $enrollment->load([
            'user',
            'course.category',
            'certificate',
        ]);

        $progress = $enrollment->course
            ? $enrollment->course->progressFor($enrollment->user)
            : ['total' => 0, 'completed' => 0, 'percentage' => 0, 'is_completed' => false];

        // Retrieve associated Order if a paid purchase exists for this user and course
        $order = Order::with(['payments'])
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->latest()
            ->first();

        return view('admin.enrollments.show', compact(
            'enrollment',
            'progress',
            'order'
        ));
    }
}