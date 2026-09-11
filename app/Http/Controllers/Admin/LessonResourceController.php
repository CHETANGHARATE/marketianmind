<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonResourceRequest;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LessonResourceController extends Controller
{
    /**
     * Display a listing of resources for a lesson.
     */
    public function index(Course $course, CourseModule $module, Lesson $lesson): View
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        $resources = $lesson->resources()->orderBy('sort_order')->get();
        $nextSortOrder = ($resources->max('sort_order') ?? 0) + 1;

        return view('admin.lessons.resources.index', compact('course', 'module', 'lesson', 'resources', 'nextSortOrder'));
    }

    /**
     * Store a newly created lesson resource.
     */
    public function store(StoreLessonResourceRequest $request, Course $course, CourseModule $module, Lesson $lesson): RedirectResponse
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        $validated = $request->validated();
        $sortOrder = $validated['sort_order'] ?? (($lesson->resources()->max('sort_order') ?? 0) + 1);

        $filePath = null;
        $fileName = null;
        $fileSize = null;
        $fileType = null;
        $externalUrl = null;

        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileType = strtolower($file->getClientOriginalExtension());
            $filePath = $file->store("courses/{$course->id}/lessons/{$lesson->id}/resources", 'public');
        } elseif ($validated['type'] === 'link') {
            $externalUrl = $validated['external_url'];
            $fileType = 'link';
        }

        $resource = $lesson->resources()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'external_url' => $externalUrl,
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        AuditLogger::log(
            'created',
            $resource,
            "Added resource '{$resource->title}' ({$resource->type}) to lesson '{$lesson->title}' in course '{$course->title}'"
        );

        return redirect()
            ->route('admin.courses.modules.lessons.resources.index', [$course, $module, $lesson])
            ->with('success', "Resource '{$resource->title}' added successfully.");
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Course $course, CourseModule $module, Lesson $lesson, LessonResource $resource): RedirectResponse
    {
        $this->ensureHierarchyValid($course, $module, $lesson);

        if ((int) $resource->lesson_id !== (int) $lesson->id) {
            abort(404, 'The specified resource does not belong to this lesson.');
        }

        $title = $resource->title;
        $resource->deleteStoredFile();
        $resource->delete();

        AuditLogger::log(
            'deleted',
            'LessonResource',
            "Deleted resource '{$title}' from lesson '{$lesson->title}' in course '{$course->title}'"
        );

        return redirect()
            ->route('admin.courses.modules.lessons.resources.index', [$course, $module, $lesson])
            ->with('success', "Resource '{$title}' deleted successfully.");
    }

    /**
     * Ensure the lesson belongs to module and module belongs to course.
     */
    protected function ensureHierarchyValid(Course $course, CourseModule $module, Lesson $lesson): void
    {
        if ((int) $module->course_id !== (int) $course->id) {
            abort(404, 'The requested module does not belong to this course.');
        }

        if ((int) $lesson->course_module_id !== (int) $module->id) {
            abort(404, 'The requested lesson does not belong to this module.');
        }
    }
}