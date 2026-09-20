<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\RetentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RetentionController extends Controller
{
    /**
     * Display the Admin Retention & Learning Support Workspace.
     */
    public function index(Request $request, RetentionService $retentionService): View
    {
        $cohort = (string) $request->query('cohort', 'all');
        $validCohorts = ['all', 'not_started', 'inactive', 'approaching_completion', 'expiring_soon', 'expired', 'completed', 'renewed'];
        if (! in_array($cohort, $validCohorts, true)) {
            $cohort = 'all';
        }

        $search = trim((string) $request->query('search', ''));
        $courseId = $request->filled('course_id') && is_numeric($request->query('course_id'))
            ? (int) $request->query('course_id')
            : null;

        $page = max(1, (int) $request->query('page', 1));

        $summary = $retentionService->getRetentionSummary();
        $students = $retentionService->getStudentsNeedingSupport($cohort, $search ?: null, $courseId, 15, $page);
        $courses = Course::published()->orderBy('title')->get(['id', 'title']);

        return view('admin.retention.index', compact(
            'summary',
            'students',
            'courses',
            'cohort',
            'search',
            'courseId'
        ));
    }

    /**
     * Dispatch an ad-hoc learning support communication to an enrolled student.
     */
    public function sendSupport(Request $request, User $student, Course $course, RetentionService $retentionService): RedirectResponse
    {
        $validated = $request->validate([
            'support_type' => 'required|string|in:kickstart,reengagement,completion_push,renewal_reminder',
        ]);

        $result = $retentionService->sendLearningSupport(
            $student,
            $course,
            $validated['support_type']
        );

        if ($result['sent']) {
            return back()->with('status', "Learning support communication successfully dispatched to {$student->name}.");
        }

        return back()->with('error', $result['reason'] ?? 'Could not dispatch learning support.');
    }

    /**
     * Stream CSV export of retention cohorts.
     */
    public function export(Request $request, RetentionService $retentionService): StreamedResponse
    {
        $cohort = (string) $request->query('cohort', 'all');
        $courseId = $request->filled('course_id') && is_numeric($request->query('course_id'))
            ? (int) $request->query('course_id')
            : null;

        return $retentionService->streamRetentionCsv($cohort, $courseId);
    }
}
