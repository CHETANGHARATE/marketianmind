<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LessonController extends Controller
{
    /**
     * Display a listing of lessons for the specified course and module.
     */
    public function index(Course $course, CourseModule $module): View
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        $lessons = $module->lessons()->orderBy('sort_order')->get();

        return view('admin.lessons.index', compact('course', 'module', 'lessons'));
    }

    /**
     * Show the form for creating a new lesson in the specified module.
     */
    public function create(Course $course, CourseModule $module): View
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        $nextSortOrder = ($module->lessons()->max('sort_order') ?? 0) + 1;

        return view('admin.lessons.create', compact('course', 'module', 'nextSortOrder'));
    }

    /**
     * Store a newly created lesson in the specified module.
     */
    public function store(StoreLessonRequest $request, Course $course, CourseModule $module): RedirectResponse
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        $validated = $request->validated();

        // Resolve slug uniqueness
        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->input('slug') : $request->input('title')
        );

        // Auto-assign sort order if left blank
        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $validated['sort_order'] = ($module->lessons()->max('sort_order') ?? 0) + 1;
        }

        // Handle PDF upload if lesson type is PDF
        if ($validated['lesson_type'] === LessonType::PDF->value && $request->hasFile('pdf_file')) {
            $validated['pdf_url'] = $request->file('pdf_file')->store('courses/lesson-pdfs', 'public');
        }

        $validated['course_module_id'] = $module->id;

        $lesson = Lesson::create($validated);

        return redirect()
            ->route('admin.courses.modules.lessons.index', [$course, $module])
            ->with('success', "Lesson '{$lesson->title}' created successfully.");
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit(Course $course, CourseModule $module, Lesson $lesson): View
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        return view('admin.lessons.edit', compact('course', 'module', 'lesson'));
    }

    /**
     * Update the specified lesson in storage.
     */
    public function update(UpdateLessonRequest $request, Course $course, CourseModule $module, Lesson $lesson): RedirectResponse
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        $validated = $request->validated();

        // Resolve slug uniqueness if changed
        if ($request->filled('slug') && $request->input('slug') !== $lesson->slug) {
            $validated['slug'] = $this->generateUniqueSlug($request->input('slug'), $lesson->id);
        }

        // Auto-assign sort order if left blank
        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $validated['sort_order'] = $lesson->sort_order;
        }

        // Handle PDF upload and cleanup
        if ($validated['lesson_type'] === LessonType::PDF->value) {
            if ($request->hasFile('pdf_file')) {
                if ($lesson->pdf_url && Storage::disk('public')->exists($lesson->pdf_url)) {
                    Storage::disk('public')->delete($lesson->pdf_url);
                }
                $validated['pdf_url'] = $request->file('pdf_file')->store('courses/lesson-pdfs', 'public');
            }
        } else {
            // If switched away from PDF, clean up old PDF file
            if ($lesson->pdf_url && Storage::disk('public')->exists($lesson->pdf_url)) {
                Storage::disk('public')->delete($lesson->pdf_url);
                $validated['pdf_url'] = null;
            }
        }

        $lesson->update($validated);

        return redirect()
            ->route('admin.courses.modules.lessons.index', [$course, $module])
            ->with('success', "Lesson '{$lesson->title}' updated successfully.");
    }

    /**
     * Remove the specified lesson from storage.
     */
    public function destroy(Course $course, CourseModule $module, Lesson $lesson): RedirectResponse
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        $title = $lesson->title;

        // Clean up PDF file if exists
        if ($lesson->pdf_url && Storage::disk('public')->exists($lesson->pdf_url)) {
            Storage::disk('public')->delete($lesson->pdf_url);
        }

        $lesson->delete();

        return redirect()
            ->route('admin.courses.modules.lessons.index', [$course, $module])
            ->with('success', "Lesson '{$title}' deleted successfully.");
    }

    /**
     * Ensure the module belongs to the specified course.
     */
    protected function ensureModuleBelongsToCourse(Course $course, CourseModule $module): void
    {
        if ((int) $module->course_id !== (int) $course->id) {
            abort(404, 'The requested module does not belong to this course.');
        }
    }

    /**
     * Ensure the lesson belongs to the module and the module belongs to the course.
     */
    protected function ensureHierarchyValid(Course $course, CourseModule $module, Lesson $lesson): void
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        if ((int) $lesson->course_module_id !== (int) $module->id) {
            abort(404, 'The requested lesson does not belong to this module.');
        }
    }

    /**
     * Generate a unique slug for a lesson.
     */
    protected function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Lesson::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
