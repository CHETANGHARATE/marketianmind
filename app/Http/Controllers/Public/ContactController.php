<?php

namespace App\Http\Controllers\Public;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLeadRequest;
use App\Models\Course;
use App\Models\Lead;
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
    public function submit(StoreLeadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Remove honeypot before persisting
        unset($validated['website']);

        $validated['source'] = $validated['source'] ?? 'contact_page';
        $validated['status'] = LeadStatus::NEW->value;
        $validated['ip_address'] = $request->ip();

        Lead::create($validated);

        return redirect()
            ->route('contact')
            ->with('success', "Thank you, {$request->input('name')}! We have received your message and will get back to you within 1-2 business days.");
    }
}