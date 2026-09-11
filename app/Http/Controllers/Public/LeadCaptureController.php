<?php

namespace App\Http\Controllers\Public;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeadCaptureController extends Controller
{
    /**
     * Store an incoming lead / course inquiry.
     */
    public function store(StoreLeadRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        // Remove honeypot
        unset($validated['website']);

        $validated['source'] = $validated['source'] ?? 'course_landing';
        $validated['status'] = LeadStatus::NEW->value;
        $validated['ip_address'] = $request->ip();

        $lead = Lead::create($validated);

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