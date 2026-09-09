@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.index', $course) }}" class="hover:text-white transition truncate max-w-xs">{{ $course->title }}</a>
        <span>/</span>
        <span class="text-slate-300 truncate max-w-xs">{{ $module->title }}</span>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Lessons</span>
    </div>

    <!-- Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                    Module {{ $module->sort_order }}
                </span>
                <span class="text-xs text-slate-400">in {{ $course->title }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
                {{ $module->title }}
            </h1>
            @if($module->description)
                <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                    {{ $module->description }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses.modules.index', $course) }}"
               class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                &larr; Back to Modules
            </a>
            <a href="{{ route('admin.courses.modules.lessons.create', [$course, $module]) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Add Lesson
            </a>
        </div>
    </div>

    <!-- Lessons Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
        @if($lessons->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">#</th>
                            <th scope="col" class="px-3 py-3.5">Lesson Title</th>
                            <th scope="col" class="px-3 py-3.5">Type</th>
                            <th scope="col" class="px-3 py-3.5">Duration</th>
                            <th scope="col" class="px-3 py-3.5">Preview</th>
                            <th scope="col" class="px-3 py-3.5">Status</th>
                            <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($lessons as $lesson)
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Sort Order -->
                                <td class="py-4 pl-4 pr-3 sm:pl-6 whitespace-nowrap text-xs font-mono font-bold text-amber-400">
                                    {{ $lesson->sort_order }}
                                </td>

                                <!-- Title & Slug -->
                                <td class="px-3 py-4">
                                    <div class="font-bold text-white max-w-sm truncate">
                                        {{ $lesson->title }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono truncate">
                                        /{{ $lesson->slug }}
                                    </div>
                                </td>

                                <!-- Lesson Type -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($lesson->lesson_type->value === 'video')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-sky-400 border border-sky-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Video
                                        </span>
                                    @elseif($lesson->lesson_type->value === 'text')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Text
                                        </span>
                                    @elseif($lesson->lesson_type->value === 'pdf')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            PDF Resource
                                        </span>
                                    @endif
                                </td>

                                <!-- Duration -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $lesson->duration ?? '—' }}
                                </td>

                                <!-- Preview -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($lesson->is_preview)
                                        <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-[11px] font-bold text-amber-400 border border-amber-500/20">
                                            Free Preview
                                        </span>
                                    @else
                                        <span class="text-slate-500">Locked</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($lesson->status->value === 'published')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-400 border border-amber-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Draft
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.courses.modules.lessons.edit', [$course, $module, $lesson]) }}"
                                           class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.courses.modules.lessons.destroy', [$course, $module, $lesson]) }}" onsubmit="return confirm('Are you sure you want to delete lesson &quot;{{ $lesson->title }}&quot;?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg bg-rose-500/10 px-2.5 py-1.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16 px-4">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">No lessons in this module yet</h3>
                <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                    Add video lectures, reading notes, or PDF resources to this module.
                </p>
                <div class="mt-6">
                    <a href="{{ route('admin.courses.modules.lessons.create', [$course, $module]) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                        + Add First Lesson
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
