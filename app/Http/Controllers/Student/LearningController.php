<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    /**
     * Display enrolled courses for the student.
     */
    public function myLearning(Request $request): View
    {
        $user = $request->user();

        // Enrolled courses foundation (empty for now until enrollment system is built)
        $enrolledCourses = [];

        return view('student.my-learning', [
            'user' => $user,
            'enrolledCourses' => $enrolledCourses,
            'headerTitle' => 'My Learning',
        ]);
    }

    /**
     * Browse available marketing courses for the student.
     */
    public function courses(Request $request): View
    {
        $user = $request->user();

        $courses = [
            [
                'title' => 'Digital Marketing for Business Owners',
                'description' => 'A practical, non-agency framework to understand digital channels, customer acquisition, and marketing funnels without agency fees.',
                'modules' => 6,
                'duration' => '4 Weeks',
                'level' => 'Beginner to Intermediate',
                'badge' => 'Featured Course',
                'actionUrl' => route('course.details'),
                'actionLabel' => 'Course Curriculum & Preview',
            ],
        ];

        return view('student.courses', [
            'user' => $user,
            'courses' => $courses,
            'headerTitle' => 'Browse Courses',
        ]);
    }

    /**
     * Display learning progress metrics and completed modules.
     */
    public function progress(Request $request): View
    {
        $user = $request->user();

        $progressMetrics = [
            'total_learning_hours' => 0,
            'completed_lessons' => 0,
            'courses_in_progress' => 0,
            'certificates_earned' => 0,
        ];

        return view('student.progress', [
            'user' => $user,
            'metrics' => $progressMetrics,
            'headerTitle' => 'Learning Progress',
        ]);
    }

}