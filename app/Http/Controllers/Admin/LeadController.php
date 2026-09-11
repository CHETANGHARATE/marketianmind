<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lead;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * Display a listing of prospective leads and course inquiries.
     */
    public function index(Request $request): View
    {
        $query = Lead::with('course');

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && in_array($request->input('status'), LeadStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        $leads = $query->latest()->paginate(15)->withQueryString();
        $courses = Course::orderBy('title')->get(['id', 'title']);

        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', LeadStatus::NEW->value)->count(),
            'contacted' => Lead::where('status', LeadStatus::CONTACTED->value)->count(),
            'converted' => Lead::where('status', LeadStatus::CONVERTED->value)->count(),
        ];

        return view('admin.leads.index', compact('leads', 'courses', 'stats'));
    }

    /**
     * Display the specified lead details.
     */
    public function show(Lead $lead): View
    {
        $lead->load('course');
        $matchedUser = $lead->matchedUser();

        return view('admin.leads.show', compact('lead', 'matchedUser'));
    }

    /**
     * Update the status and internal notes for the specified lead.
     */
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $oldStatus = $lead->status->value;
        $oldNotes = $lead->notes;

        $lead->update($validated);

        AuditLogger::log(
            action: 'updated',
            auditable: $lead,
            description: "Updated lead inquiry for: {$lead->name} ({$lead->email})",
            oldValues: ['status' => $oldStatus, 'notes' => $oldNotes],
            newValues: ['status' => $lead->status->value, 'notes' => $lead->notes]
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', "Lead inquiry updated successfully.");
    }

    /**
     * Remove the specified lead record.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $name = $lead->name;
        $email = $lead->email;

        AuditLogger::log(
            action: 'deleted',
            auditable: $lead,
            description: "Deleted lead inquiry from: {$name} ({$email})",
            oldValues: $lead->toArray(),
            newValues: null
        );

        $lead->delete();

        return redirect()
            ->route('admin.leads.index')
            ->with('success', "Lead '{$name}' deleted successfully.");
    }
}