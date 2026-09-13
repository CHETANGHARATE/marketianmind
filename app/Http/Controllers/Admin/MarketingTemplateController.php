<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarketingTemplateController extends Controller
{
    /**
     * Display a listing of marketing email templates.
     */
    public function index(Request $request)
    {
        $query = MarketingTemplate::withCount('automations')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(15)->withQueryString();

        return view('admin.marketing-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new marketing email template.
     */
    public function create()
    {
        return view('admin.marketing-templates.create');
    }

    /**
     * Store a newly created marketing email template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:marketing_templates,slug'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $count = 1;
            while (MarketingTemplate::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = "{$originalSlug}-{$count}";
                $count++;
            }
        }

        $template = MarketingTemplate::create($validated);

        return redirect()->route('admin.marketing-templates.index')
            ->with('success', "Marketing template '{$template->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified marketing email template.
     */
    public function edit(MarketingTemplate $marketingTemplate)
    {
        return view('admin.marketing-templates.edit', [
            'template' => $marketingTemplate,
        ]);
    }

    /**
     * Update the specified marketing email template.
     */
    public function update(Request $request, MarketingTemplate $marketingTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('marketing_templates', 'slug')->ignore($marketingTemplate->id)],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $marketingTemplate->update($validated);

        return redirect()->route('admin.marketing-templates.index')
            ->with('success', "Marketing template '{$marketingTemplate->name}' updated successfully.");
    }

    /**
     * Remove the specified marketing email template.
     */
    public function destroy(MarketingTemplate $marketingTemplate)
    {
        if ($marketingTemplate->automations()->exists()) {
            return back()->with('error', 'Cannot delete template because it is currently assigned to one or more automations.');
        }

        $name = $marketingTemplate->name;
        $marketingTemplate->delete();

        return redirect()->route('admin.marketing-templates.index')
            ->with('success', "Marketing template '{$name}' deleted successfully.");
    }
}
