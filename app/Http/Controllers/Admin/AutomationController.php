<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationStatus;
use App\Enums\AutomationTrigger;
use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\MarketingTemplate;
use App\Services\MarketingAutomationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    /**
     * Display a listing of automations.
     */
    public function index(Request $request)
    {
        $query = Automation::with('template')
            ->withCount([
                'executions as total_executions_count',
                'executions as sent_executions_count' => fn($q) => $q->where('status', AutomationExecutionStatus::SENT->value),
                'executions as pending_executions_count' => fn($q) => $q->where('status', AutomationExecutionStatus::PENDING->value),
                'executions as failed_executions_count' => fn($q) => $q->where('status', AutomationExecutionStatus::FAILED->value),
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger')) {
            $query->where('trigger_type', $request->trigger);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        $automations = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Automation::count(),
            'active' => Automation::where('status', AutomationStatus::ACTIVE->value)->count(),
            'paused' => Automation::where('status', AutomationStatus::PAUSED->value)->count(),
            'total_sent' => AutomationExecution::where('status', AutomationExecutionStatus::SENT->value)->count(),
            'total_pending' => AutomationExecution::where('status', AutomationExecutionStatus::PENDING->value)->count(),
        ];

        return view('admin.automations.index', [
            'automations' => $automations,
            'stats' => $stats,
            'triggers' => AutomationTrigger::cases(),
            'statuses' => AutomationStatus::cases(),
        ]);
    }

    /**
     * Show the form for creating a new automation.
     */
    public function create()
    {
        $templates = MarketingTemplate::orderBy('name')->get();
        $whatsappTemplates = \App\Models\WhatsAppTemplate::active()->orderBy('name')->get();
        $triggers = AutomationTrigger::cases();
        $statuses = AutomationStatus::cases();

        return view('admin.automations.create', compact('templates', 'whatsappTemplates', 'triggers', 'statuses'));
    }

    /**
     * Store a newly created automation in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'channel' => ['nullable', 'string', 'in:email,whatsapp'],
            'trigger_type' => ['required', Rule::enum(AutomationTrigger::class)],
            'status' => ['required', Rule::enum(AutomationStatus::class)],
            'template_id' => ['nullable', 'required_if:channel,email', 'exists:marketing_templates,id'],
            'whatsapp_template_id' => ['nullable', 'required_if:channel,whatsapp', 'exists:whatsapp_message_templates,id'],
            'delay_minutes' => ['required', 'integer', 'min:0', 'max:10080'], // max 7 days
            'conditions' => ['nullable', 'string'],
        ]);

        $validated['channel'] = $validated['channel'] ?? 'email';

        if (!empty($validated['conditions'])) {
            $decoded = json_decode($validated['conditions'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $validated['conditions'] = $decoded;
            } else {
                $validated['conditions'] = null;
            }
        } else {
            $validated['conditions'] = null;
        }

        $automation = Automation::create($validated);

        return redirect()->route('admin.automations.show', $automation)
            ->with('success', "Automation '{$automation->name}' created successfully.");
    }

    /**
     * Display the specified automation and its execution history.
     */
    public function show(Automation $automation, Request $request)
    {
        $automation->load('template');

        $query = $automation->executions()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $executions = $query->paginate(20)->withQueryString();

        $executionStats = [
            'total' => $automation->executions()->count(),
            'sent' => $automation->executions()->where('status', AutomationExecutionStatus::SENT->value)->count(),
            'pending' => $automation->executions()->where('status', AutomationExecutionStatus::PENDING->value)->count(),
            'skipped' => $automation->executions()->where('status', AutomationExecutionStatus::SKIPPED->value)->count(),
            'failed' => $automation->executions()->where('status', AutomationExecutionStatus::FAILED->value)->count(),
        ];

        return view('admin.automations.show', compact('automation', 'executions', 'executionStats'));
    }

    /**
     * Show the form for editing the specified automation.
     */
    public function edit(Automation $automation)
    {
        $templates = MarketingTemplate::orderBy('name')->get();
        $whatsappTemplates = \App\Models\WhatsAppTemplate::active()->orderBy('name')->get();
        $triggers = AutomationTrigger::cases();
        $statuses = AutomationStatus::cases();

        return view('admin.automations.edit', compact('automation', 'templates', 'whatsappTemplates', 'triggers', 'statuses'));
    }

    /**
     * Update the specified automation in storage.
     */
    public function update(Request $request, Automation $automation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'channel' => ['nullable', 'string', 'in:email,whatsapp'],
            'trigger_type' => ['required', Rule::enum(AutomationTrigger::class)],
            'status' => ['required', Rule::enum(AutomationStatus::class)],
            'template_id' => ['nullable', 'required_if:channel,email', 'exists:marketing_templates,id'],
            'whatsapp_template_id' => ['nullable', 'required_if:channel,whatsapp', 'exists:whatsapp_message_templates,id'],
            'delay_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'conditions' => ['nullable', 'string'],
        ]);

        $validated['channel'] = $validated['channel'] ?? 'email';

        if (!empty($validated['conditions'])) {
            $decoded = json_decode($validated['conditions'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $validated['conditions'] = $decoded;
            } else {
                $validated['conditions'] = null;
            }
        } else {
            $validated['conditions'] = null;
        }

        $automation->update($validated);

        return redirect()->route('admin.automations.show', $automation)
            ->with('success', "Automation '{$automation->name}' updated successfully.");
    }

    /**
     * Toggle the status of the specified automation.
     */
    public function toggleStatus(Request $request, Automation $automation)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(AutomationStatus::class)],
        ]);

        $automation->update(['status' => $validated['status']]);

        return back()->with('success', "Automation status updated to {$automation->status->label()}.");
    }

    /**
     * Remove the specified automation from storage.
     */
    public function destroy(Automation $automation)
    {
        $name = $automation->name;
        $automation->delete();

        return redirect()->route('admin.automations.index')
            ->with('success', "Automation '{$name}' deleted successfully.");
    }

    /**
     * Trigger immediate execution processing of due entries.
     */
    public function processNow(MarketingAutomationService $service)
    {
        $stats = $service->processDueExecutions(100);

        return back()->with('success', "Execution processing completed: {$stats['sent']} sent, {$stats['skipped']} skipped, {$stats['failed']} failed.");
    }
}
