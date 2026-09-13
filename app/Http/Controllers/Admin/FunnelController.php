<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversionEventName;
use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\ConversionEvent;
use App\Models\Course;
use App\Services\ConversionTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FunnelController extends Controller
{
    /**
     * Display the visual Conversion Funnel & Optimization dashboard.
     */
    public function index(Request $request, ConversionTrackingService $trackingService): View
    {
        $startDateStr = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDateStr = $request->input('end_date', now()->toDateString());

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        $courseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;
        $bundleId = $request->filled('bundle_id') ? (int) $request->input('bundle_id') : null;

        $funnelMetrics = $trackingService->getFunnelMetrics($startDate, $endDate, $courseId, $bundleId);

        // Event counts breakdown
        $eventCountsQuery = ConversionEvent::query()
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if ($courseId) {
            $eventCountsQuery->where('course_id', $courseId);
        }
        if ($bundleId) {
            $eventCountsQuery->where('bundle_id', $bundleId);
        }

        $eventCounts = (clone $eventCountsQuery)
            ->selectRaw('event_name, count(*) as count')
            ->groupBy('event_name')
            ->pluck('count', 'event_name')
            ->toArray();

        // Recent conversion events
        $recentEvents = (clone $eventCountsQuery)
            ->with(['user', 'course', 'bundle', 'experiment', 'variant'])
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString();

        $courses = Course::orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);

        return view('admin.funnel.index', [
            'funnelMetrics' => $funnelMetrics,
            'eventCounts' => $eventCounts,
            'recentEvents' => $recentEvents,
            'courses' => $courses,
            'bundles' => $bundles,
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
            'selectedCourseId' => $courseId,
            'selectedBundleId' => $bundleId,
            'eventNames' => ConversionEventName::cases(),
        ]);
    }
}
