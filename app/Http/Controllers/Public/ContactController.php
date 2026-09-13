<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLeadRequest;
use App\Models\Course;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ContactController extends Controller
{
    /**
     * Display the Contact page.
     */
    public function index(): View
    {
        $courses = Schema::hasTable('courses')
            ? Course::published()->orderBy('title')->get(['id', 'title', 'slug'])
            : collect();

        return view('public.contact', compact('courses'));
    }

    /**
     * Handle incoming contact form submission.
     */
    public function submit(StoreLeadRequest $request, LeadService $leadService): RedirectResponse
    {
        $validated = $request->validated();

        // Remove honeypot before persisting
        unset($validated['website']);

        $validated['source'] = $validated['source'] ?? 'contact_page';

        $lead = $leadService->createOrDeduplicateLead($validated, $request->ip());

        // Track Contact Form Lead Conversion Event
        app(\App\Services\ConversionTrackingService::class)->track('lead_created', [
            'lead_id' => $lead->id,
            'course_id' => $lead->course_id,
            'metadata' => ['source' => 'contact_page'],
        ]);

        return redirect()
            ->route('contact')
            ->with('success', "Thank you, {$request->input('name')}! We have received your message and will get back to you within 1-2 business days.");
    }
}