@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <span class="text-slate-300 truncate max-w-xs">{{ $course->title }}</span>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Curriculum Modules</span>
    </div>

    <!-- Course Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    {{ $course->title }}
                </h1>
                @if($course->is_free)
                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-400 border border-emerald-500/20">
                        Free
                    </span>
                @else
                    <span class="rounded-full bg-indigo-500/10 px-2.5 py-0.5 text-xs font-semibold text-indigo-400 border border-indigo-500/20">
                        ₹{{ number_format($course->effectivePrice(), 2) }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-400">
                Organize learning chapters and sequential modules for this course.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses.edit', $course) }}"
               class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Course Settings
            </a>
            <a href="{{ route('admin.courses.modules.create', $course) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Add Module
            </a>
        </div>
    </div>

    <!-- Modules List -->
    <div class="space-y-4">
        @if($modules->count() > 0)
            @foreach($modules as $index => $module)
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5 hover:border-slate-700 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <!-- Module Info -->
                        <div class="flex items-start gap-4">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 font-bold text-sm border border-amber-500/20 shrink-0 mt-0.5">
                                {{ $module->sort_order }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-base font-bold text-white">
                                        {{ $module->title }}
                                    </h3>
                                    <span class="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-300 border border-slate-700">
                                        {{ $module->lessons_count }} {{ Str::plural('Lesson', $module->lessons_count) }}
                                    </span>
                                </div>
                                @if($module->description)
                                    <p class="mt-1 text-xs text-slate-400 max-w-2xl">
                                        {{ $module->description }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Module Actions -->
                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                            <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 px-3 py-1.5 text-xs font-bold text-amber-400 hover:bg-amber-500/20 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Manage Lessons ({{ $module->lessons_count }})
                            </a>

                            <a href="{{ route('admin.courses.modules.edit', [$course, $module]) }}"
                               class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-700 border border-slate-700 transition">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.courses.modules.destroy', [$course, $module]) }}" onsubmit="return confirm('Are you sure you want to delete module &quot;{{ $module->title }}&quot;? All associated lessons will be permanently deleted.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg bg-rose-500/10 px-2.5 py-1.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Empty State -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 text-center py-16 px-4">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">No modules added yet</h3>
                <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                    Courses are divided into structured modules. Start creating modules to assemble your learning path.
                </p>
                <div class="mt-6">
                    <a href="{{ route('admin.courses.modules.create', $course) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                        + Add First Module
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
