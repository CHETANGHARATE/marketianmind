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
    public function index(Request $request): View
    {
        $courses = Schema::hasTable('courses')
            ? Course::published()->orderBy('title')->get(['id', 'title', 'slug'])
            : collect();

        $selectedCourseId = null;
        if ($request->filled('course_id')) {
            $selectedCourseId = (int) $request->input('course_id');
        } elseif ($request->filled('course')) {
            $matched = $courses->firstWhere('slug', $request->input('course'));
            if ($matched) {
                $selectedCourseId = $matched->id;
            }
        }

        $seoService = app(\App\Services\SeoService::class);
        $seo = $seoService->buildMeta([
            'title' => 'Contact Us — Speak with Our Course Advisors',
            'description' => 'Have questions about our marketing courses, 365-day access validity, or corporate training? Get in touch with the Marketian Mind team today.',
            'canonical' => route('contact'),
            'schemas' => [
                $seoService->buildBreadcrumbSchema([
                    'Home' => url('/'),
                    'Contact' => route('contact'),
                ]),
            ],
        ]);

        return view('public.contact', compact('courses', 'selectedCourseId', 'seo'));
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

        // Track Contact Form Lead Conversion Event with Attribution
        $attributionMeta = array_filter([
            'source' => 'contact_page',
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

        return redirect()
            ->route('contact')
            ->with('success', "Thank you, {$request->input('name')}! We have received your message and will get back to you within 1-2 business days.");
    }
}