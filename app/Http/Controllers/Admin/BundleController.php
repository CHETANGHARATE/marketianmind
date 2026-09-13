<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BundleStatus;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BundleController extends Controller
{
    /**
     * Display a listing of course bundles with search and stats.
     */
    public function index(Request $request): View
    {
        $query = Bundle::withCount(['courses', 'orders']);

        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->search($searchTerm);
        }

        if ($request->filled('status') && in_array($request->input('status'), BundleStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        $bundles = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => Bundle::count(),
            'published' => Bundle::where('status', BundleStatus::PUBLISHED->value)->count(),
            'draft' => Bundle::where('status', BundleStatus::DRAFT->value)->count(),
            'archived' => Bundle::where('status', BundleStatus::ARCHIVED->value)->count(),
        ];

        return view('admin.bundles.index', compact('bundles', 'stats'));
    }

    /**
     * Show form to create a new course bundle.
     */
    public function create(): View
    {
        $courses = Course::orderBy('title')->get();
        return view('admin.bundles.create', compact('courses'));
    }

    /**
     * Store a newly created bundle.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:bundles,slug'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(BundleStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['exists:courses,id'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Bundle::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('bundles', 'public');
        }

        $bundle = Bundle::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'featured' => $request->boolean('featured'),
            'thumbnail' => $thumbnailPath,
        ]);

        // Attach courses with sort_order
        $syncData = [];
        foreach ($validated['courses'] as $index => $courseId) {
            $syncData[$courseId] = ['sort_order' => $index + 1];
        }
        $bundle->courses()->sync($syncData);

        AuditLogger::log(
            'created',
            $bundle,
            "Created course bundle '{$bundle->title}' with " . count($syncData) . ' courses.',
            null,
            $bundle->toArray(),
            auth()->user(),
            $bundle->title
        );

        return redirect()
            ->route('admin.bundles.index')
            ->with('success', "Bundle '{$bundle->title}' created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Bundle $bundle): RedirectResponse
    {
        return redirect()->route('admin.bundles.edit', $bundle);
    }

    /**
     * Show the form for editing an existing course bundle.
     */
    public function edit(Bundle $bundle): View
    {
        $bundle->load('courses');
        $courses = Course::orderBy('title')->get();

        return view('admin.bundles.edit', compact('bundle', 'courses'));
    }

    /**
     * Update an existing course bundle.
     */
    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('bundles', 'slug')->ignore($bundle->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(BundleStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['exists:courses,id'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Bundle::where('slug', $slug)->where('id', '!=', $bundle->id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $oldValues = $bundle->toArray();

        $thumbnailPath = $bundle->thumbnail;
        if ($request->hasFile('thumbnail')) {
            if ($bundle->thumbnail && Storage::disk('public')->exists($bundle->thumbnail)) {
                Storage::disk('public')->delete($bundle->thumbnail);
            }
            $thumbnailPath = $request->file('thumbnail')->store('bundles', 'public');
        }

        $bundle->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'featured' => $request->boolean('featured'),
            'thumbnail' => $thumbnailPath,
        ]);

        // Sync courses with sort_order
        $syncData = [];
        foreach ($validated['courses'] as $index => $courseId) {
            $syncData[$courseId] = ['sort_order' => $index + 1];
        }
        $bundle->courses()->sync($syncData);

        AuditLogger::log(
            'updated',
            $bundle,
            "Updated course bundle '{$bundle->title}'.",
            $oldValues,
            $bundle->fresh()->toArray(),
            auth()->user(),
            $bundle->title
        );

        return redirect()
            ->route('admin.bundles.index')
            ->with('success', "Bundle '{$bundle->title}' updated successfully.");
    }

    /**
     * Remove the specified course bundle from storage.
     */
    public function destroy(Bundle $bundle): RedirectResponse
    {
        $title = $bundle->title;
        $oldValues = $bundle->toArray();

        if ($bundle->thumbnail && Storage::disk('public')->exists($bundle->thumbnail)) {
            Storage::disk('public')->delete($bundle->thumbnail);
        }

        $bundle->delete();

        AuditLogger::log(
            'deleted',
            Bundle::class,
            "Deleted course bundle '{$title}'.",
            $oldValues,
            null,
            auth()->user(),
            $title
        );

        return redirect()
            ->route('admin.bundles.index')
            ->with('success', "Bundle '{$title}' deleted successfully.");
    }
}
