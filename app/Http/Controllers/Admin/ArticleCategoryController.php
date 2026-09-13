<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of article categories.
     */
    public function index(Request $request): View
    {
        $query = ArticleCategory::withCount('articles');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.article-categories.index', compact('categories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120|unique:article_categories,slug',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $uniqueSlug = $this->generateUniqueSlug($slug);

        $category = ArticleCategory::create([
            'name' => $validated['name'],
            'slug' => $uniqueSlug,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        AuditLogger::log(
            action: 'created',
            auditable: $category,
            description: "Created blog category '{$category->name}'",
            oldValues: null,
            newValues: $category->only(['name', 'slug', 'description', 'is_active']),
            resourceLabel: $category->name
        );

        return redirect()
            ->route('admin.article-categories.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, ArticleCategory $articleCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => "nullable|string|max:120|unique:article_categories,slug,{$articleCategory->id}",
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $oldValues = $articleCategory->only(['name', 'slug', 'description', 'is_active']);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        if ($slug !== $articleCategory->slug) {
            $slug = $this->generateUniqueSlug($slug, $articleCategory->id);
        }

        $articleCategory->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        AuditLogger::log(
            action: 'updated',
            auditable: $articleCategory,
            description: "Updated blog category '{$articleCategory->name}'",
            oldValues: $oldValues,
            newValues: $articleCategory->only(['name', 'slug', 'description', 'is_active']),
            resourceLabel: $articleCategory->name
        );

        return redirect()
            ->route('admin.article-categories.index')
            ->with('success', "Category '{$articleCategory->name}' updated successfully.");
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(ArticleCategory $articleCategory): RedirectResponse
    {
        $name = $articleCategory->name;
        $oldValues = $articleCategory->only(['name', 'slug']);

        $articleCategory->delete();

        AuditLogger::log(
            action: 'deleted',
            auditable: 'ArticleCategory',
            description: "Deleted blog category '{$name}'",
            oldValues: $oldValues,
            newValues: null,
            resourceLabel: $name
        );

        return redirect()
            ->route('admin.article-categories.index')
            ->with('success', "Category '{$name}' deleted successfully.");
    }

    /**
     * Generate unique slug for category.
     */
    protected function generateUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $original = $slug;
        $count = 1;

        while (ArticleCategory::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
