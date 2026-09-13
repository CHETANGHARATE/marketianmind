<?php

namespace App\Http\Controllers\Public;

use App\Enums\ConversionEventName;
use App\Http\Controllers\Controller;
use App\Services\ConversionTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversionEventController extends Controller
{
    public function __construct(
        protected ConversionTrackingService $trackingService
    ) {}

    /**
     * Ingest client-side interaction events with strict allowlisting.
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => 'required|string|max:60',
            'course_id' => 'nullable|exists:courses,id',
            'bundle_id' => 'nullable|exists:bundles,id',
            'metadata' => 'nullable|array',
        ]);

        $eventEnum = ConversionEventName::tryFrom($validated['event_name']);

        if (!$eventEnum || !$eventEnum->isClientReportable()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'event_name' => ['Event is not allowed for client-side reporting.'],
            ]);
        }

        $this->trackingService->track($eventEnum, [
            'course_id' => $validated['course_id'] ?? null,
            'bundle_id' => $validated['bundle_id'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
