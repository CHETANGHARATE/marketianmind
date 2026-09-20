<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * Display a paginated, searchable, multi-filtered listing of leads with pipeline metrics.
     */
    public function index(Request $request): View
    {
        $query = Lead::with(['course', 'bundle', 'assignedUser', 'convertedUser']);

        // 1. Search across name, email, phone, company, source
        if ($search = trim($request->input('search', ''))) {
            $query->search($search);
        }

        // 2. Status Filter
        if ($status = $request->input('status')) {
            if (in_array($status, LeadStatus::values(), true)) {
                $query->where('status', $status);
            }
        }

        // 3. Priority Filter
        if ($priority = $request->input('priority')) {
            if (in_array($priority, LeadPriority::values(), true)) {
                $query->where('priority', $priority);
            }
        }

        // 4. Source Filter
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        // 5. Assignment Filter
        if ($assignedTo = $request->input('assigned_to')) {
            if ($assignedTo === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($assignedTo)) {
                $query->where('assigned_to', (int) $assignedTo);
            }
        }

        // 6. Course & Bundle Filters
        if ($courseId = $request->input('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($bundleId = $request->input('bundle_id')) {
            $query->where('bundle_id', $bundleId);
        }

        // 7. Follow-up Filter
        if ($followUp = $request->input('follow_up')) {
            if ($followUp === 'today') {
                $query->dueTodayFollowUps();
            } elseif ($followUp === 'overdue') {
                $query->overdueFollowUps();
            } elseif ($followUp === 'upcoming') {
                $query->upcomingFollowUps();
            }
        }

        // 8. Sorting (Whitelisted)
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'recently_updated' => $query->latest('updated_at'),
            'follow_up_asc' => $query->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END, next_follow_up_at ASC'),
            'follow_up_desc' => $query->orderByDesc('next_follow_up_at'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END"),
            'name' => $query->orderBy('name'),
            default => $query->latest('created_at'),
        };

        $leads = $query->paginate(15)->withQueryString();

        // Pipeline Counts
        $pipelineCounts = [
            'new' => Lead::where('status', LeadStatus::NEW->value)->count(),
            'contacted' => Lead::where('status', LeadStatus::CONTACTED->value)->count(),
            'qualified' => Lead::where('status', LeadStatus::QUALIFIED->value)->count(),
            'interested' => Lead::where('status', LeadStatus::INTERESTED->value)->count(),
            'follow_up' => Lead::where('status', LeadStatus::FOLLOW_UP->value)->count(),
            'converted' => Lead::where('status', LeadStatus::CONVERTED->value)->count(),
            'lost' => Lead::whereIn('status', [LeadStatus::LOST->value, LeadStatus::NOT_INTERESTED->value, LeadStatus::CLOSED->value])->count(),
        ];

        // Core Metrics
        $totalLeads = Lead::count();
        $convertedLeads = Lead::where('status', LeadStatus::CONVERTED->value)->count();
        $stats = [
            'total' => $totalLeads,
            'new' => $pipelineCounts['new'],
            'qualified' => $pipelineCounts['qualified'] + $pipelineCounts['interested'],
            'follow_up_today' => Lead::dueTodayFollowUps()->count(),
            'overdue' => Lead::overdueFollowUps()->count(),
            'converted' => $convertedLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0,
        ];

        $courses = Course::orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);
        $admins = User::where('role', UserRole::ADMIN->value)->orderBy('name')->get(['id', 'name', 'email']);
        $assignees = $admins;

        $followUpCounts = [
            'today' => Lead::dueTodayFollowUps()->count(),
            'overdue' => Lead::overdueFollowUps()->count(),
            'upcoming' => Lead::upcomingFollowUps()->count(),
        ];

        $sources = Lead::distinct()->whereNotNull('source')->pluck('source')->toArray();

        return view('admin.leads.index', compact(
            'leads',
            'courses',
            'bundles',
            'admins',
            'assignees',
            'stats',
            'pipelineCounts',
            'followUpCounts',
            'sources'
        ));
    }

    /**
     * Show the form for creating a new lead manually.
     */
    public function create(): View
    {
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);
        $admins = User::where('role', UserRole::ADMIN->value)->orderBy('name')->get(['id', 'name', 'email']);
        $assignees = $admins;

        return view('admin.leads.create', compact('courses', 'bundles', 'admins', 'assignees'));
    }

    /**
     * Store a newly created lead in the CRM.
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $initialNote = $validated['initial_note'] ?? null;
        unset($validated['initial_note']);

        $validated['email'] = strtolower(trim($validated['email']));
        $validated['source'] = $validated['source'] ?? 'manual';
        $validated['last_contacted_at'] = now();

        $lead = Lead::create($validated);

        $actor = $request->user();

        // Record initial activity
        $lead->recordActivity(
            'created',
            "Lead created manually by {$actor->name}",
            ['source' => $lead->source, 'status' => $lead->status->value],
            $actor
        );

        if (! empty($initialNote)) {
            $lead->addNote($initialNote, $actor);
        }

        AuditLogger::log(
            action: 'created',
            auditable: $lead,
            description: "Created CRM lead: {$lead->name} ({$lead->email})",
            oldValues: null,
            newValues: ['name' => $lead->name, 'email' => $lead->email, 'status' => $lead->status->value]
        );

        app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
            \App\Enums\AutomationTrigger::LEAD_CREATED,
            $lead,
            ['source' => $lead->source, 'course_id' => $lead->course_id, 'bundle_id' => $lead->bundle_id],
            'lead_created_' . $lead->id
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', "Lead '{$lead->name}' created successfully.")
            ->with('status', "Lead '{$lead->name}' created successfully.");
    }

    /**
     * Display the specified lead detail, notes, activity history, and conversion status.
     */
    public function show(Lead $lead): View
    {
        $lead->load([
            'course',
            'bundle',
            'assignedUser',
            'convertedUser',
            'leadNotes.author',
            'activities.user',
        ]);

        $admins = User::where('role', UserRole::ADMIN->value)->orderBy('name')->get(['id', 'name', 'email']);
        $assignees = $admins;
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);
        $matchedUser = $lead->matchedUser();

        $targetUser = $lead->convertedUser ?? $matchedUser;
        $associatedOrders = $targetUser
            ? $targetUser->orders()->with(['course', 'bundle'])->latest()->take(5)->get()
            : collect();

        return view('admin.leads.show', compact(
            'lead',
            'admins',
            'assignees',
            'courses',
            'bundles',
            'matchedUser',
            'associatedOrders'
        ));
    }

    /**
     * Show the edit form for modifying a lead's profile and CRM settings.
     */
    public function edit(Lead $lead): View
    {
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);
        $admins = User::where('role', UserRole::ADMIN->value)->orderBy('name')->get(['id', 'name', 'email']);
        $assignees = $admins;

        return view('admin.leads.edit', compact('lead', 'courses', 'bundles', 'admins', 'assignees'));
    }

    /**
     * Update the specified lead in the CRM and record activity changes.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();
        $actor = $request->user();

        $oldValues = [
            'status' => $lead->status->value,
            'priority' => $lead->priority->value,
            'assigned_to' => $lead->assigned_to,
            'next_follow_up_at' => $lead->next_follow_up_at?->toDateTimeString(),
            'notes' => $lead->notes,
        ];

        $statusChanged = false;
        $isQualified = false;

        // Track and record meaningful activities
        if (isset($validated['status']) && $validated['status'] !== $lead->status->value) {
            $newStatus = LeadStatus::from($validated['status']);
            $statusChanged = true;
            if ($newStatus === LeadStatus::QUALIFIED) {
                $isQualified = true;
            }
            $lead->recordActivity(
                'status_changed',
                "Status changed from '{$lead->status->label()}' to '{$newStatus->label()}'",
                ['old' => $lead->status->value, 'new' => $newStatus->value],
                $actor
            );
        }

        if (isset($validated['priority']) && $validated['priority'] !== $lead->priority->value) {
            $newPriority = LeadPriority::from($validated['priority']);
            $lead->recordActivity(
                'priority_changed',
                "Priority changed from '{$lead->priority->label()}' to '{$newPriority->label()}'",
                ['old' => $lead->priority->value, 'new' => $newPriority->value],
                $actor
            );
        }

        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to'] != $lead->assigned_to) {
            $newAssignee = $validated['assigned_to'] ? User::find($validated['assigned_to']) : null;
            $assigneeName = $newAssignee ? $newAssignee->name : 'Unassigned';
            $lead->recordActivity(
                'assigned',
                "Lead assigned to {$assigneeName}",
                ['assigned_to' => $validated['assigned_to']],
                $actor
            );
        }

        if (array_key_exists('next_follow_up_at', $validated) && $validated['next_follow_up_at'] != $lead->next_follow_up_at) {
            $followUpDate = $validated['next_follow_up_at'] ? date('M d, Y h:i A', strtotime($validated['next_follow_up_at'])) : 'cleared';
            $lead->recordActivity(
                'follow_up_scheduled',
                "Follow-up scheduled for {$followUpDate}",
                ['date' => $validated['next_follow_up_at']],
                $actor
            );
            $validated['follow_up_status'] = 'scheduled';
        }

        $lead->update($validated);

        if ($statusChanged) {
            app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
                \App\Enums\AutomationTrigger::LEAD_STATUS_CHANGED,
                $lead,
                ['status' => $lead->status->value],
                'lead_status_' . $lead->id . '_' . $lead->status->value
            );

            if ($isQualified) {
                app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
                    \App\Enums\AutomationTrigger::LEAD_QUALIFIED,
                    $lead,
                    ['status' => 'qualified'],
                    'lead_qualified_' . $lead->id
                );
            }
        }

        AuditLogger::log(
            action: 'updated',
            auditable: $lead,
            description: "Updated CRM lead details for: {$lead->name} ({$lead->email})",
            oldValues: $oldValues,
            newValues: $lead->only(['status', 'priority', 'assigned_to', 'next_follow_up_at', 'notes'])
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Lead details updated successfully.')
            ->with('status', 'Lead details updated successfully.');
    }

    /**
     * Add an internal CRM note to the lead.
     */
    public function addNote(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $lead->addNote($validated['content'], $request->user());

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Internal note added successfully.')
            ->with('status', 'Internal note added successfully.');
    }

    /**
     * Explicitly convert a lead to a registered student.
     */
    public function convert(Request $request, Lead $lead, LeadService $leadService): RedirectResponse
    {
        if ($lead->isConverted()) {
            return redirect()
                ->route('admin.leads.show', $lead)
                ->with('warning', 'This lead has already been converted to a student.');
        }

        $password = $request->input('password');
        $studentUser = $leadService->convertLeadToStudent($lead, $request->user(), $password);

        AuditLogger::log(
            action: 'updated',
            auditable: $lead,
            description: "Converted lead '{$lead->name}' to student account (User ID: {$studentUser->id})",
            oldValues: ['status' => $lead->status->value],
            newValues: ['status' => LeadStatus::CONVERTED->value, 'converted_user_id' => $studentUser->id]
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', "Lead '{$lead->name}' successfully converted into student user '{$studentUser->name}' ({$studentUser->email}).")
            ->with('status', "Lead '{$lead->name}' successfully converted into student user '{$studentUser->name}' ({$studentUser->email}).");
    }

    /**
     * Mark the current scheduled follow-up as completed.
     */
    public function completeFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update([
            'follow_up_status' => 'completed',
        ]);

        $lead->recordActivity(
            'follow_up_completed',
            "Follow-up marked completed by {$request->user()->name}",
            ['completed_at' => now()->toDateTimeString()],
            $request->user()
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Follow-up marked as completed.')
            ->with('status', 'Follow-up marked as completed.');
    }

    /**
     * Delete / remove the lead record.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $name = $lead->name;
        $email = $lead->email;

        AuditLogger::log(
            action: 'deleted',
            auditable: $lead,
            description: "Deleted CRM lead from: {$name} ({$email})",
            oldValues: $lead->toArray(),
            newValues: null
        );

        $lead->delete();

        return redirect()
            ->route('admin.leads.index')
            ->with('success', "Lead '{$name}' was deleted successfully.")
            ->with('status', "Lead '{$name}' was deleted successfully.");
    }
}
