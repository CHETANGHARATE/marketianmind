<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Models\Instructor;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstructorController extends Controller
{
    /**
     * Display a listing of instructors.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $instructors = Instructor::query()
            ->withCount('courses')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active' || $status === '1');
            })
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Instructor::count(),
            'active' => Instructor::where('is_active', true)->count(),
            'inactive' => Instructor::where('is_active', false)->count(),
        ];

        return view('admin.instructors.index', compact('instructors', 'search', 'status', 'stats'));
    }

    /**
     * Show the form for creating a new instructor.
     */
    public function create(): View
    {
        return view('admin.instructors.create');
    }

    /**
     * Store a newly created instructor.
     */
    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->input('slug') : $request->input('name')
        );

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('instructors/avatars', 'public');
        }

        $instructor = Instructor::create($validated);

        AuditLogger::log(
            'created',
            $instructor,
            "Created instructor '{$instructor->name}' with title '{$instructor->title}'"
        );

        return redirect()
            ->route('admin.instructors.index')
            ->with('success', "Instructor '{$instructor->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified instructor.
     */
    public function edit(Instructor $instructor): View
    {
        return view('admin.instructors.edit', compact('instructor'));
    }

    /**
     * Update the specified instructor.
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->filled('slug') && $request->input('slug') !== $instructor->slug) {
            $validated['slug'] = $this->generateUniqueSlug($request->input('slug'), $instructor->id);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('avatar')) {
            $instructor->deleteStoredAvatar();
            $validated['avatar'] = $request->file('avatar')->store('instructors/avatars', 'public');
        }

        $instructor->update($validated);

        AuditLogger::log(
            'updated',
            $instructor,
            "Updated instructor '{$instructor->name}' details"
        );

        return redirect()
            ->route('admin.instructors.index')
            ->with('success', "Instructor '{$instructor->name}' updated successfully.");
    }

    /**
     * Remove the specified instructor from storage.
     */
    public function destroy(Instructor $instructor): RedirectResponse
    {
        $name = $instructor->name;
        $instructor->deleteStoredAvatar();
        $instructor->delete();

        AuditLogger::log(
            'deleted',
            'Instructor',
            "Deleted instructor '{$name}'"
        );

        return redirect()
            ->route('admin.instructors.index')
            ->with('success', "Instructor '{$name}' deleted successfully.");
    }

    /**
     * Toggle the active status of an instructor.
     */
    public function toggleStatus(Instructor $instructor): RedirectResponse
    {
        $instructor->update([
            'is_active' => ! $instructor->is_active,
        ]);

        $statusStr = $instructor->is_active ? 'activated' : 'deactivated';

        AuditLogger::log(
            'updated',
            $instructor,
            "Instructor '{$instructor->name}' {$statusStr}"
        );

        return redirect()
            ->back()
            ->with('success', "Instructor '{$instructor->name}' has been {$statusStr}.");
    }

    /**
     * Generate a unique slug for an instructor.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'instructor';
        $counter = 1;

        while (
            Instructor::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}