<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Student Portal Dashboard overview.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrolledCount = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->count();

        $lessonsCompletedCount = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->count();

        $enrolledCourses = $user->enrolledCourses()->published()->get();
        $totalPublishedLessons = 0;
        $totalCompletedLessons = 0;

        foreach ($enrolledCourses as $course) {
            $p = $course->progressFor($user);
            $totalPublishedLessons += $p['total'];
            $totalCompletedLessons += $p['completed'];
        }

        $overallProgress = $totalPublishedLessons > 0
            ? (int) round(($totalCompletedLessons / $totalPublishedLessons) * 100)
            : 0;

        $stats = [
            'enrolled_courses' => $enrolledCount,
            'lessons_completed' => $lessonsCompletedCount,
            'progress_percentage' => min(100, $overallProgress),
        ];

        return view('student.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'headerTitle' => 'Student Dashboard',
        ]);
    }
}