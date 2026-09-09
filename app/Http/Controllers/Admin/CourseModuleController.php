<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseModuleRequest;
use App\Http\Requests\Admin\UpdateCourseModuleRequest;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseModuleController extends Controller
{
    /**
     * Display a listing of modules for the specified course.
     */
    public function index(Course $course): View
    {
        $modules = $course->modules()->withCount('lessons')->orderBy('sort_order')->get();

        return view('admin.course-modules.index', compact('course', 'modules'));
    }

    /**
     * Show the form for creating a new module in the specified course.
     */
    public function create(Course $course): View
    {
        $nextSortOrder = ($course->modules()->max('sort_order') ?? 0) + 1;

        return view('admin.course-modules.create', compact('course', 'nextSortOrder'));
    }

    /**
     * Store a newly created module in the specified course.
     */
    public function store(StoreCourseModuleRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();

        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $validated['sort_order'] = ($course->modules()->max('sort_order') ?? 0) + 1;
        }

        $validated['course_id'] = $course->id;

        $module = CourseModule::create($validated);

        return redirect()
            ->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$module->title}' created successfully.");
    }

    /**
     * Show the form for editing the specified module.
     */
    public function edit(Course $course, CourseModule $module): View
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        return view('admin.course-modules.edit', compact('course', 'module'));
    }

    /**
     * Update the specified module in storage.
     */
    public function update(UpdateCourseModuleRequest $request, Course $course, CourseModule $module): RedirectResponse
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        $validated = $request->validated();

        if (! isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $validated['sort_order'] = $module->sort_order;
        }

        $module->update($validated);

        return redirect()
            ->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$module->title}' updated successfully.");
    }

    /**
     * Remove the specified module from storage.
     */
    public function destroy(Course $course, CourseModule $module): RedirectResponse
    {
        $this->ensureModuleBelongsToCourse($course, $module);

        $title = $module->title;

        // Clean up any PDF files attached to lessons under this module
        foreach ($module->lessons as $lesson) {
            if ($lesson->pdf_url && Storage::disk('public')->exists($lesson->pdf_url)) {
                Storage::disk('public')->delete($lesson->pdf_url);
            }
        }

        // Delete module (cascades to lessons per database foreign key)
        $module->delete();

        return redirect()
            ->route('admin.courses.modules.index', $course)
            ->with('success', "Module '{$title}' and its associated lessons were deleted successfully.");
    }

    /**
     * Ensure the module belongs to the specified course to prevent cross-course manipulation.
     */
    protected function ensureModuleBelongsToCourse(Course $course, CourseModule $module): void
    {
        if ((int) $module->course_id !== (int) $course->id) {
            abort(404, 'The requested module does not belong to this course.');
        }
    }
}
