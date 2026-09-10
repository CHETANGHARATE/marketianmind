<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseCategoryRequest;
use App\Http\Requests\Admin\UpdateCourseCategoryRequest;
use App\Models\CourseCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseCategoryController extends Controller
{
    /**
     * Display a listing of course categories.
     */
    public function index(Request $request): View
    {
        $query = CourseCategory::withCount('courses');

        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%");
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCourseCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->input('slug') : $request->input('name')
        );

        $category = CourseCategory::create($validated);

        AuditLogger::log(
            action: 'created',
            auditable: $category,
            description: "Created course category: {$category->name}",
            oldValues: null,
            newValues: $category->only(['name', 'slug', 'description', 'status', 'order'])
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCourseCategoryRequest $request, CourseCategory $category): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->filled('slug') && $request->input('slug') !== $category->slug) {
            $validated['slug'] = $this->generateUniqueSlug($request->input('slug'), $category->id);
        }

        $oldValues = $category->only(['name', 'slug', 'description', 'status', 'order']);
        $category->update($validated);

        AuditLogger::log(
            action: 'updated',
            auditable: $category,
            description: "Updated course category: {$category->name}",
            oldValues: $oldValues,
            newValues: $category->only(['name', 'slug', 'description', 'status', 'order'])
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Remove the specified category from storage safely.
     */
    public function destroy(CourseCategory $category): RedirectResponse
    {
        $name = $category->name;
        $categorySnapshot = $category->only(['name', 'slug', 'description', 'status']);

        // Foreign key nullOnDelete in Phase 4.1 ensures courses are not deleted
        $category->delete();

        AuditLogger::log(
            action: 'deleted',
            auditable: 'CourseCategory',
            description: "Deleted course category: {$name}",
            oldValues: $categorySnapshot,
            newValues: null,
            resourceLabel: $name
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$name}' was deleted. Associated courses are now uncategorized.");
    }

    /**
     * Generate a unique slug for a category.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            CourseCategory::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
