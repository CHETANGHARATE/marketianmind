<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeadCaptureController extends Controller
{
    /**
     * Store an incoming lead / course inquiry.
     */
    public function store(StoreLeadRequest $request, LeadService $leadService): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        // Remove honeypot
        unset($validated['website']);

        $validated['source'] = $validated['source'] ?? 'course_landing';

        $lead = $leadService->createOrDeduplicateLead($validated, $request->ip());

        // Track Lead Created Conversion Event with Attribution
        $attributionMeta = array_filter([
            'source' => $lead->source,
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
        ]);

        app(\App\Services\ConversionTrackingService::class)->track('lead_created', [
            'lead_id' => $lead->id,
            'course_id' => $lead->course_id,
            'metadata' => $attributionMeta,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Thank you, {$lead->name}! Your inquiry has been submitted. Our admissions team will reach out shortly.",
            ]);
        }

        return redirect()
            ->back()
            ->with('lead_success', "Thank you, {$lead->name}! Your inquiry has been submitted. Our admissions team will reach out shortly.");
    }
}