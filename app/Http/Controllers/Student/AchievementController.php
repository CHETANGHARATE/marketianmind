<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Display the student's gamification hub, streaks, points, and achievement badges.
     */
    public function index(Request $request, GamificationService $gamificationService): View
    {
        $user = $request->user();
        $summary = $gamificationService->getGamificationSummary($user);

        return view('student.achievements.index', [
            'user' => $user,
            'summary' => $summary,
            'streaks' => $summary['streaks'],
            'pointsBalance' => $summary['points_balance'],
            'pointsToday' => $summary['points_today'],
            'achievements' => $summary['achievements'],
            'recentPoints' => $summary['recent_points'],
            'earnedCount' => $summary['earned_count'],
            'totalCount' => $summary['total_count'],
            'headerTitle' => 'Achievements & Learning Streaks',
        ]);
    }
}
