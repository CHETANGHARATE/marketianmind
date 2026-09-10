<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Display a listing of courses with filtering and search.
     */
    public function index(Request $request): View
    {
        $query = Course::with(['category'])->withCount(['modules', 'lessons']);

        // Search by course title
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where('title', 'like', "%{$searchTerm}%");
        }

        // Filter by status
        if ($request->filled('status') && in_array($request->input('status'), CourseStatus::values(), true)) {
            $query->where('status', $request->input('status'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('course_category_id', $request->input('category_id'));
        }

        // Filter by free or paid
        if ($request->filled('type')) {
            if ($request->input('type') === 'free') {
                $query->where('is_free', true);
            } elseif ($request->input('type') === 'paid') {
                $query->where('is_free', false);
            }
        }

        $courses = $query->latest()->paginate(12)->withQueryString();
        $categories = CourseCategory::active()->orderBy('name')->get();

        $stats = [
            'total' => Course::count(),
            'published' => Course::where('status', CourseStatus::PUBLISHED->value)->count(),
            'draft' => Course::where('status', CourseStatus::DRAFT->value)->count(),
            'featured' => Course::where('featured', true)->count(),
        ];

        return view('admin.courses.index', compact('courses', 'categories', 'stats'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): View
    {
        $categories = CourseCategory::active()->orderBy('name')->get();

        return view('admin.courses.create', compact('categories'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Resolve slug uniqueness
        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->input('slug') : $request->input('title')
        );

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $course = Course::create($validated);

        AuditLogger::log(
            action: 'created',
            auditable: $course,
            description: "Created course: {$course->title}",
            oldValues: null,
            newValues: $course->only(['title', 'slug', 'course_category_id', 'price', 'is_free', 'status', 'featured'])
        );

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$course->title}' created successfully.");
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course): View
    {
        $categories = CourseCategory::orderBy('name')->get();

        return view('admin.courses.edit', compact('course', 'categories'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();

        // Resolve slug uniqueness if modified
        if ($request->filled('slug') && $request->input('slug') !== $course->slug) {
            $validated['slug'] = $this->generateUniqueSlug($request->input('slug'), $course->id);
        }

        // Handle thumbnail upload and replace old file
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $trackFields = ['title', 'slug', 'course_category_id', 'price', 'is_free', 'status', 'featured'];
        $oldValues = $course->only($trackFields);
        $oldStatus = $course->status;

        $course->update($validated);

        $newValues = $course->only($trackFields);
        $newStatus = $course->status;

        $action = 'updated';
        $desc = "Updated course: {$course->title}";
        if ($oldStatus !== CourseStatus::PUBLISHED && $newStatus === CourseStatus::PUBLISHED) {
            $action = 'published';
            $desc = "Published course: {$course->title}";
        } elseif ($oldStatus === CourseStatus::PUBLISHED && $newStatus !== CourseStatus::PUBLISHED) {
            $action = 'unpublished';
            $desc = "Unpublished course: {$course->title}";
        }

        AuditLogger::log(
            action: $action,
            auditable: $course,
            description: $desc,
            oldValues: $oldValues,
            newValues: $newValues
        );

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$course->title}' updated successfully.");
    }

    /**
     * Remove the specified course from storage safely.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $title = $course->title;
        $courseSnapshot = $course->only(['title', 'slug', 'course_category_id', 'price', 'is_free', 'status']);

        // Cleanup thumbnail file if exists
        if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        // Deleting course cascades to modules and lessons per Phase 4.1 migrations
        $course->delete();

        AuditLogger::log(
            action: 'deleted',
            auditable: 'Course',
            description: "Deleted course: {$title}",
            oldValues: $courseSnapshot,
            newValues: null,
            resourceLabel: $title
        );

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$title}' and its associated modules were deleted successfully.");
    }

    /**
     * Generate a unique slug for a course.
     */
    protected function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Course::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
