<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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

        // Learning statistics foundation (ready for future database connection)
        $stats = [
            'enrolled_courses' => 0,
            'lessons_completed' => 0,
            'progress_percentage' => 0,
        ];

        return view('student.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'headerTitle' => 'Student Dashboard',
        ]);
    }
}