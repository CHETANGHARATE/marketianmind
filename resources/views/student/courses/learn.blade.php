@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Top Learning Header & Progress Bar -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('student.courses') }}" class="hover:text-indigo-600 transition">&larr; My Courses</a>
                    <span>/</span>
                    <span class="text-slate-800">{{ $course->title }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $currentLesson ? $currentLesson->title : $course->title }}
                </h1>
            </div>

            <!-- Course Progress Summary -->
            <div class="w-full md:w-64 shrink-0">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="font-bold text-indigo-600">{{ $progress['percentage'] }}% Complete</span>
                    <span class="text-slate-500">{{ $progress['completed'] }} of {{ $progress['total'] }} lessons</span>
                </div>
                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-300" style="width: {{ $progress['percentage'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Learning Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Main Player & Content (8 cols on desktop) -->
        <div class="lg:col-span-8 space-y-6">
            @if($currentLesson)
                <!-- Lesson Stage Player -->
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    @if($currentLesson->isVideo())
                        <!-- Video Player Area -->
                        <div class="bg-black aspect-video relative flex items-center justify-center">
                            @if($currentLesson->isIframeVideo())
                                <iframe
                                    src="{{ $currentLesson->embedUrl() }}"
                                    class="w-full h-full"
                                    title="{{ $currentLesson->title }}"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                            @elseif($currentLesson->video_url)
                                <video controls class="w-full h-full bg-black">
                                    <source src="{{ $currentLesson->video_url }}">
                                    Your browser does not support the video tag.
                                </video>
                            @else
                                <div class="text-center p-8 text-slate-400 text-xs">
                                    <svg class="h-10 w-10 mx-auto mb-2 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Video stream is currently being prepared for this lesson.
                                </div>
                            @endif
                        </div>
                    @elseif($currentLesson->isPdf())
                        <!-- PDF Lesson Header & Action Area -->
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">PDF Guide & Worksheet</h3>
                                        <p class="text-xs text-slate-500">Read and follow this lesson's execution framework</p>
                                    </div>
                                </div>

                                <a href="{{ route('student.courses.lessons.pdf', [$course, $currentLesson]) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download / View PDF
                                </a>
                            </div>
                        </div>

                        <!-- Embedded PDF View Frame -->
                        <div class="h-[600px] bg-slate-100">
                            <iframe src="{{ route('student.courses.lessons.pdf', [$course, $currentLesson]) }}" class="w-full h-full" frameborder="0"></iframe>
                        </div>
                    @else
                        <!-- Text Lesson Article Stage -->
                        <div class="p-6 sm:p-10">
                            <div class="prose max-w-none text-slate-800 leading-relaxed text-sm sm:text-base whitespace-pre-line">
                                {{ $currentLesson->content ?? $currentLesson->description }}
                            </div>
                        </div>
                    @endif

                    <!-- Lesson Information & Notes -->
                    <div class="p-6 sm:p-8 border-t border-slate-100">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div>
                                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">
                                    {{ $currentLesson->module?->title ?? 'Module' }}
                                </span>
                                <h2 class="text-xl font-bold text-slate-900 mt-1">
                                    {{ $currentLesson->title }}
                                </h2>
                            </div>

                            <div class="flex items-center gap-2 text-xs">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 capitalize">
                                    {{ $currentLesson->lesson_type->value }} Lesson
                                </span>
                                @if($currentLesson->duration)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                        {{ $currentLesson->duration }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($currentLesson->description && $currentLesson->isVideo())
                            <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                                <h4 class="text-xs font-bold uppercase text-slate-400 mb-2">Lesson Notes & Action Steps</h4>
                                {{ $currentLesson->description }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Navigation & Completion Controls Bar -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        @if($previousLesson)
                            <a href="{{ route('student.courses.lessons.show', [$course, $previousLesson]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                                &larr; Previous Lesson
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-300 cursor-not-allowed">
                                &larr; Previous Lesson
                            </span>
                        @endif
                    </div>

                    <!-- Mark as Complete Form -->
                    <div>
                        <form action="{{ route('student.courses.lessons.complete', [$course, $currentLesson]) }}" method="POST">
                            @csrf
                            @if($isCompleted)
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 ring-1 ring-inset ring-emerald-600/30 px-5 py-2.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Completed ✓
                                </button>
                            @else
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Mark as Complete
                                </button>
                            @endif
                        </form>
                    </div>

                    <div>
                        @if($nextLesson)
                            <a href="{{ route('student.courses.lessons.show', [$course, $nextLesson]) }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition">
                                Next Lesson &rarr;
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700">
                                Course Finished 🎉
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <!-- Empty Lesson Fallback -->
                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                    <h3 class="text-base font-bold text-slate-900">Lessons coming soon</h3>
                    <p class="mt-1 text-xs text-slate-500">The curriculum for this course is being prepared.</p>
                </div>
            @endif
        </div>

        <!-- Course Curriculum Sidebar (4 cols on desktop) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden sticky top-6">
                <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Course Curriculum</h3>
                    <span class="text-xs font-bold text-indigo-600">{{ $progress['completed'] }}/{{ $progress['total'] }}</span>
                </div>

                <div class="max-h-[calc(100vh-14rem)] overflow-y-auto divide-y divide-slate-100">
                    @forelse($modules as $mIndex => $module)
                        <div class="p-3">
                            <div class="text-xs font-bold text-slate-700 mb-2 px-2 flex items-center justify-between">
                                <span class="truncate">Module {{ $mIndex + 1 }}: {{ $module->title }}</span>
                                <span class="text-slate-400 font-normal text-[11px] shrink-0">{{ $module->lessons->count() }}</span>
                            </div>

                            <div class="space-y-1">
                                @forelse($module->lessons as $lesson)
                                    @php
                                        $lessonCompleted = in_array($lesson->id, array_keys($completedLessonIds));
                                        $isCurrent = $currentLesson && $currentLesson->id === $lesson->id;
                                    @endphp

                                    <a
                                        href="{{ route('student.courses.lessons.show', [$course, $lesson]) }}"
                                        class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-xs transition {{ $isCurrent ? 'bg-indigo-50 font-bold text-indigo-700' : 'text-slate-700 hover:bg-slate-50' }}"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            @if($lessonCompleted)
                                                <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-[10px]">
                                                    ✓
                                                </div>
                                            @else
                                                <div class="h-4 w-4 shrink-0 rounded-full border-2 {{ $isCurrent ? 'border-indigo-600 bg-white' : 'border-slate-300' }}"></div>
                                            @endif

                                            <span class="truncate {{ $lessonCompleted && !$isCurrent ? 'text-slate-500 line-through' : '' }}">
                                                {{ $lesson->title }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0 text-slate-400 text-[11px]">
                                            @if($lesson->duration)
                                                <span>{{ $lesson->duration }}</span>
                                            @endif
                                            @if($lesson->isVideo())
                                                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                </svg>
                                            @elseif($lesson->isPdf())
                                                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-3 py-1 text-[11px] text-slate-400 italic">
                                        No published lessons in this module.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">
                            Curriculum modules coming soon.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection