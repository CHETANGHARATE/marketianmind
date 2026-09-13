<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateCategory;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\AuditLogger;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Display WhatsApp Integration Dashboard / Overview.
     */
    public function dashboard(): View
    {
        $stats = [
            'total_messages' => WhatsAppMessage::count(),
            'sent_messages' => WhatsAppMessage::whereIn('status', [
                WhatsAppMessageStatus::SENT,
                WhatsAppMessageStatus::DELIVERED,
                WhatsAppMessageStatus::READ,
            ])->count(),
            'delivered_messages' => WhatsAppMessage::whereIn('status', [
                WhatsAppMessageStatus::DELIVERED,
                WhatsAppMessageStatus::READ,
            ])->count(),
            'read_messages' => WhatsAppMessage::where('status', WhatsAppMessageStatus::READ)->count(),
            'failed_messages' => WhatsAppMessage::where('status', WhatsAppMessageStatus::FAILED)->count(),
            'opted_in_leads' => Lead::where('whatsapp_opt_in', true)->count(),
            'opted_in_users' => User::where('whatsapp_opt_in', true)->count(),
        ];

        $recentMessages = WhatsAppMessage::with(['user', 'lead', 'template'])
            ->latest()
            ->limit(10)
            ->get();

        $isConfigured = config('whatsapp.enabled') &&
            ! empty(config('whatsapp.phone_number_id')) &&
            ! empty(config('whatsapp.access_token'));

        $availableTemplates = WhatsAppTemplate::active()->orderBy('name')->get();

        return view('admin.whatsapp.dashboard', [
            'stats' => $stats,
            'recentMessages' => $recentMessages,
            'isConfigured' => $isConfigured,
            'availableTemplates' => $availableTemplates,
        ]);
    }

    /**
     * List WhatsApp Message Templates.
     */
    public function templates(Request $request): View
    {
        $query = WhatsAppTemplate::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('template_name', 'like', $term)
                    ->orWhere('body', 'like', $term);
            });
        }

        $templates = $query->latest()->paginate(15)->withQueryString();

        return view('admin.whatsapp.templates.index', [
            'templates' => $templates,
            'categories' => WhatsAppTemplateCategory::cases(),
        ]);
    }

    /**
     * Show form to create a WhatsApp template.
     */
    public function createTemplate(): View
    {
        return view('admin.whatsapp.templates.create', [
            'categories' => WhatsAppTemplateCategory::cases(),
        ]);
    }

    /**
     * Store a new WhatsApp template.
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'template_name' => ['required', 'string', 'max:150', 'alpha_dash', 'unique:whatsapp_message_templates,template_name'],
            'category' => ['required', 'string'],
            'language' => ['required', 'string', 'max:10'],
            'header' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1024'],
            'footer' => ['nullable', 'string', 'max:100'],
            'buttons' => ['nullable', 'string'],
            'variables' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Parse variables string (comma-separated or json)
        $variables = [];
        if (! empty($validated['variables'])) {
            $variables = array_values(array_filter(array_map('trim', explode(',', $validated['variables']))));
        }

        // Parse buttons json if valid
        $buttons = null;
        if (! empty($validated['buttons'])) {
            $decoded = json_decode($validated['buttons'], true);
            $buttons = is_array($decoded) ? $decoded : null;
        }

        $template = WhatsAppTemplate::create([
            'name' => $validated['name'],
            'template_name' => strtolower($validated['template_name']),
            'category' => $validated['category'],
            'language' => $validated['language'] ?? 'en',
            'header' => $validated['header'] ?? null,
            'body' => $validated['body'],
            'footer' => $validated['footer'] ?? null,
            'buttons' => $buttons,
            'variables' => $variables,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log(
            'created',
            $template,
            "Created WhatsApp message template '{$template->name}' ({$template->template_name})"
        );

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', "WhatsApp template '{$template->name}' created successfully.");
    }

    /**
     * Show form to edit a WhatsApp template.
     */
    public function editTemplate(WhatsAppTemplate $template): View
    {
        return view('admin.whatsapp.templates.edit', [
            'template' => $template,
            'categories' => WhatsAppTemplateCategory::cases(),
        ]);
    }

    /**
     * Update an existing WhatsApp template.
     */
    public function updateTemplate(Request $request, WhatsAppTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'template_name' => ['required', 'string', 'max:150', 'alpha_dash', 'unique:whatsapp_message_templates,template_name,' . $template->id],
            'category' => ['required', 'string'],
            'language' => ['required', 'string', 'max:10'],
            'header' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1024'],
            'footer' => ['nullable', 'string', 'max:100'],
            'buttons' => ['nullable', 'string'],
            'variables' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $variables = [];
        if (! empty($validated['variables'])) {
            $variables = array_values(array_filter(array_map('trim', explode(',', $validated['variables']))));
        }

        $buttons = null;
        if (! empty($validated['buttons'])) {
            $decoded = json_decode($validated['buttons'], true);
            $buttons = is_array($decoded) ? $decoded : null;
        }

        $oldValues = $template->only(['name', 'category', 'body', 'is_active']);

        $template->update([
            'name' => $validated['name'],
            'template_name' => strtolower($validated['template_name']),
            'category' => $validated['category'],
            'language' => $validated['language'] ?? 'en',
            'header' => $validated['header'] ?? null,
            'body' => $validated['body'],
            'footer' => $validated['footer'] ?? null,
            'buttons' => $buttons,
            'variables' => $variables,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log(
            'updated',
            $template,
            "Updated WhatsApp message template '{$template->name}'",
            $oldValues,
            $template->only(['name', 'category', 'body', 'is_active'])
        );

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', "WhatsApp template '{$template->name}' updated successfully.");
    }

    /**
     * Toggle active state of a template.
     */
    public function toggleTemplate(WhatsAppTemplate $template): RedirectResponse
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        AuditLogger::log(
            'updated',
            $template,
            ($template->is_active ? 'Activated' : 'Deactivated') . " WhatsApp template '{$template->name}'"
        );

        return back()->with('success', "Template '{$template->name}' status updated.");
    }

    /**
     * Delete a WhatsApp template.
     */
    public function destroyTemplate(WhatsAppTemplate $template): RedirectResponse
    {
        $name = $template->name;
        $template->delete();

        AuditLogger::log(
            'deleted',
            $template,
            "Deleted WhatsApp message template '{$name}'"
        );

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', "WhatsApp template '{$name}' deleted successfully.");
    }

    /**
     * Message audit logs.
     */
    public function messages(Request $request): View
    {
        $query = WhatsAppMessage::with(['user', 'lead', 'template']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->query('direction'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('phone_number', 'like', $term)
                    ->orWhere('phone_normalized', 'like', $term)
                    ->orWhere('provider_message_id', 'like', $term);
            });
        }

        $messages = $query->latest()->paginate(20)->withQueryString();

        return view('admin.whatsapp.messages.index', [
            'messages' => $messages,
            'statuses' => WhatsAppMessageStatus::cases(),
        ]);
    }

    /**
     * Read-only masked settings inspection.
     */
    public function settings(): View
    {
        $mask = function (?string $value, int $start = 4, int $end = 4): string {
            if (empty($value)) {
                return '<Not Configured>';
            }
            $len = strlen($value);
            if ($len <= ($start + $end)) {
                return '••••••••';
            }
            return substr($value, 0, $start) . str_repeat('•', max(4, $len - $start - $end)) . substr($value, -$end);
        };

        $settings = [
            'enabled' => config('whatsapp.enabled') ? 'Enabled' : 'Disabled',
            'provider' => config('whatsapp.provider', 'meta'),
            'api_version' => config('whatsapp.api_version', 'v21.0'),
            'default_country' => config('whatsapp.default_country', 'India'),
            'phone_number_id' => $mask(config('whatsapp.phone_number_id'), 4, 3),
            'business_account_id' => $mask(config('whatsapp.business_account_id'), 4, 3),
            'access_token' => $mask(config('whatsapp.access_token'), 6, 4),
            'webhook_verify_token' => $mask(config('whatsapp.webhook_verify_token'), 3, 3),
            'app_secret' => $mask(config('whatsapp.app_secret'), 4, 4),
            'webhook_url' => route('webhooks.whatsapp'),
        ];

        return view('admin.whatsapp.settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Send single manual WhatsApp template message with consent verification.
     */
    public function sendManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_type' => ['required', 'in:lead,user'],
            'recipient_id' => ['required', 'integer'],
            'template_id' => ['required', 'integer', 'exists:whatsapp_message_templates,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'bundle_id' => ['nullable', 'integer', 'exists:bundles,id'],
        ]);

        $recipient = $validated['recipient_type'] === 'lead'
            ? Lead::findOrFail($validated['recipient_id'])
            : User::findOrFail($validated['recipient_id']);

        $template = WhatsAppTemplate::findOrFail($validated['template_id']);

        // Check active
        if (! $template->is_active) {
            return back()->with('error', "The selected template '{$template->name}' is inactive.");
        }

        // Phone check
        $phone = $recipient->phone_normalized ?? $recipient->phone;
        if (empty($phone)) {
            return back()->with('error', 'Recipient does not have a registered phone number.');
        }

        // Opt-in check for marketing templates
        if ($template->category === WhatsAppTemplateCategory::MARKETING && ! $recipient->hasWhatsAppOptIn()) {
            return back()->with('error', 'Cannot send marketing WhatsApp message: recipient has not opted in or has unsubscribed.');
        }

        $context = [];
        if (! empty($validated['course_id'])) {
            $context['course_id'] = $validated['course_id'];
        }
        if (! empty($validated['bundle_id'])) {
            $context['bundle_id'] = $validated['bundle_id'];
        }

        $result = $this->whatsAppService->sendTemplateMessage(
            recipient: $recipient,
            template: $template,
            context: $context,
            referenceType: 'admin_manual_send',
            referenceId: (string) auth()->id()
        );

        if ($result->success) {
            AuditLogger::log(
                'manual_send',
                $result->message ?? 'WhatsAppMessage',
                "Manually dispatched WhatsApp template '{$template->name}' to {$validated['recipient_type']} #{$recipient->id}"
            );

            return back()->with('success', "WhatsApp template message '{$template->name}' dispatched successfully.");
        }

        return back()->with('error', 'Failed to dispatch WhatsApp message: ' . ($result->error ?? 'Provider error.'));
    }
}
