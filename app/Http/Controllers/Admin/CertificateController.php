<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    /**
     * Display a paginated listing of all issued course completion certificates.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $courseFilter = $request->query('course');
        $dateFilter = $request->query('date');
        $sort = $request->query('sort', 'newest');

        $query = Certificate::query()->with([
            'user',
            'course.category',
            'enrollment',
        ]);

        // Search across certificate number, student snapshot name, course snapshot title, user details, and course title
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%")
                    ->orWhere('course_title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function ($cq) use ($search) {
                        $cq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        // Course filter
        if ($courseFilter) {
            $query->whereHas('course', function ($cq) use ($courseFilter) {
                $cq->where('slug', $courseFilter);
            });
        }

        // Date filter on issued_at
        if ($dateFilter === 'today') {
            $query->whereDate('issued_at', today());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('issued_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } else {
            $dateFilter = null;
        }

        // Whitelisted sorting
        match ($sort) {
            'oldest' => $query->orderBy('issued_at', 'asc'),
            'student_asc' => $query->orderBy('student_name', 'asc'),
            'student_desc' => $query->orderBy('student_name', 'desc'),
            'course_asc' => $query->orderBy('course_title', 'asc'),
            'course_desc' => $query->orderBy('course_title', 'desc'),
            default => $query->orderBy('issued_at', 'desc'),
        };

        $certificates = $query->paginate(15)->withQueryString();

        // Platform metrics
        $metrics = [
            'total_certificates' => Certificate::count(),
            'unique_students' => Certificate::distinct('user_id')->count('user_id'),
            'certified_courses' => Certificate::distinct('course_id')->count('course_id'),
        ];

        $courses = Course::orderBy('title')->get(['id', 'title', 'slug']);

        return view('admin.certificates.index', [
            'certificates' => $certificates,
            'metrics' => $metrics,
            'courses' => $courses,
            'currentCourse' => $courseFilter,
            'currentDate' => $dateFilter,
            'currentSort' => $sort,
            'search' => $search,
        ]);
    }

    /**
     * Display a detailed read-only certificate audit inspector.
     */
    public function show(Certificate $certificate): View
    {
        $certificate->loadMissing([
            'user',
            'course.category',
            'enrollment.course',
        ]);

        return view('admin.certificates.show', [
            'certificate' => $certificate,
        ]);
    }
}