@extends('layouts.base')

@section('title', ($currentLesson ? $currentLesson->title . ' — ' : '') . $course->title)

@section('content')
<div class="min-h-screen flex flex-col bg-slate-50 text-slate-900">
    <!-- Top Learning Navigation Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Left: Back to Courses & Course Title -->
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to My Courses</span>
                </a>

                <div class="h-4 w-px bg-slate-200 hidden sm:block shrink-0"></div>

                <a href="{{ route('home') }}" class="hidden md:flex items-center gap-2 font-bold text-sm text-slate-900 tracking-tight shrink-0">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white font-black text-xs shadow-2xs">
                        M
                    </span>
                    <span>Marketian<span class="text-indigo-600">Mind</span></span>
                </a>

                <div class="h-4 w-px bg-slate-200 hidden md:block shrink-0"></div>

                <div class="min-w-0">
                    <h1 class="text-xs sm:text-sm font-bold text-slate-900 truncate" title="{{ $course->title }}">
                        {{ $course->title }}
                    </h1>
                </div>
            </div>

            <!-- Right: Course Progress & Mobile Curriculum Toggle -->
            <div class="flex items-center gap-3 sm:gap-5 shrink-0">
                <!-- Course Progress Stats -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-indigo-600">{{ $progress['percentage'] }}% Complete</div>
                        <div class="text-[11px] text-slate-500 font-medium">{{ $progress['completed'] }} of {{ $progress['total'] }} lessons</div>
                    </div>

                    <div class="w-20 sm:w-28 md:w-36 h-2 rounded-full bg-slate-100 overflow-hidden shrink-0 border border-slate-200/50">
                        <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" style="width: {{ $progress['percentage'] }}%"></div>
                    </div>

                    <span class="text-xs font-bold text-indigo-600 sm:hidden">{{ $progress['percentage'] }}%</span>
                </div>

                <!-- Mobile Curriculum Drawer Button -->
                <button
                    id="mobile-curriculum-toggle"
                    type="button"
                    class="lg:hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                    aria-expanded="false"
                    aria-controls="mobile-curriculum-drawer"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span>Curriculum</span>
                    <span class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.2 text-[10px] font-bold text-white">
                        {{ $progress['completed'] }}/{{ $progress['total'] }}
                    </span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <!-- Status Alert Notification -->
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <!-- Course Completion State Banner -->
        @if($progress['is_completed'])
            <div class="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 p-5 sm:p-6 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-sm">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base sm:text-lg font-black text-emerald-950 tracking-tight">Course Completed! 🎉</h2>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-200">100% Finished</span>
                            </div>
                            <p class="text-xs text-emerald-800/90 mt-0.5">
                                Congratulations! You have completed all {{ $progress['total'] }} lessons in {{ $course->title }}.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                        @if(isset($certificate) && $certificate)
                            <a href="{{ route('student.certificates.show', $certificate) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                                View Certificate &rarr;
                            </a>
                        @endif
                        @if($firstLesson)
                            <a href="{{ route('student.courses.lessons.show', [$course, $firstLesson]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-50 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Review Course
                            </a>
                        @endif
                        <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-2xs hover:bg-emerald-500 transition">
                            Back to My Courses &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main 2-Column Learning Layout Grid (~70% Stage, ~30% Sidebar) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            <!-- Main Stage Player & Content (8 cols on desktop) -->
            <div class="lg:col-span-8 space-y-6">
                @if($currentLesson)
                    <!-- Breadcrumbs Hierarchy -->
                    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 flex-wrap" aria-label="Breadcrumb">
                        <a href="{{ route('student.courses') }}" class="hover:text-indigo-600 transition">My Courses</a>
                        <span class="text-slate-300">&rsaquo;</span>
                        <span class="text-slate-700 truncate max-w-[150px] sm:max-w-xs">{{ $course->title }}</span>
                        <span class="text-slate-300">&rsaquo;</span>
                        <span class="text-slate-700 truncate max-w-[150px] sm:max-w-xs">{{ $currentLesson->module?->title ?? 'Module' }}</span>
                        <span class="text-slate-300">&rsaquo;</span>
                        <span class="text-indigo-600 font-bold truncate max-w-[150px] sm:max-w-xs">{{ $currentLesson->title }}</span>
                    </nav>

                    <!-- Lesson Stage Media Player Container -->
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
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
                                    <video controls controlsList="nodownload" class="w-full h-full bg-black">
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
                                            <h3 class="text-sm font-bold text-slate-900">PDF Guide &amp; Worksheet</h3>
                                            <p class="text-xs text-slate-500">Read and follow this lesson's execution framework</p>
                                        </div>
                                    </div>

                                    <a href="{{ route('student.courses.lessons.pdf', [$course, $currentLesson]) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
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
                            <div class="p-6 sm:p-10 border-b border-slate-100">
                                <div class="prose max-w-none text-slate-800 leading-relaxed text-sm sm:text-base whitespace-pre-line">
                                    {{ $currentLesson->content ?? $currentLesson->description }}
                                </div>
                            </div>
                        @endif

                        <!-- Lesson Information & Notes -->
                        <div class="p-6 sm:p-8">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">
                                        {{ $currentLesson->module?->title ?? 'Module' }}
                                    </span>
                                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                                        {{ $currentLesson->title }}
                                    </h2>
                                </div>

                                <div class="flex items-center gap-2 text-xs flex-wrap">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 capitalize">
                                        {{ $currentLesson->lesson_type->value }} Lesson
                                    </span>
                                    @if($currentLesson->duration)
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                            {{ $currentLesson->duration }}
                                        </span>
                                    @endif
                                    @if($isCompleted)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 font-bold text-emerald-700">
                                            ✓ Completed
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($currentLesson->description && $currentLesson->isVideo())
                                <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-600 leading-relaxed whitespace-pre-line">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Lesson Notes &amp; Action Steps</h4>
                                    {{ $currentLesson->description }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Lesson Resources & Downloads Section -->
                    @if($currentLesson->resources && $currentLesson->resources->count() > 0)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7 shadow-xs space-y-4">
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Lesson Resources &amp; Downloads</h3>
                                        <p class="text-xs text-slate-500">Download worksheets, templates, and access reference materials.</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-100">
                                    {{ $currentLesson->resources->count() }} {{ \Illuminate\Support\Str::plural('item', $currentLesson->resources->count()) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                                @foreach($currentLesson->resources as $resource)
                                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-50 p-4 transition flex flex-col justify-between gap-3">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $resource->isFile() ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    @if($resource->isFile())
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <h4 class="text-xs font-bold text-slate-900 truncate flex-1" title="{{ $resource->title }}">
                                                    {{ $resource->title }}
                                                </h4>
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $resource->isFile() ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                                    {{ $resource->type }}
                                                </span>
                                            </div>

                                            @if($resource->description)
                                                <p class="text-xs text-slate-600 line-clamp-2">
                                                    {{ $resource->description }}
                                                </p>
                                            @endif

                                            @if($resource->isFile())
                                                <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                                                    <span class="truncate max-w-[180px] font-mono">{{ $resource->file_name ?? basename($resource->file_path) }}</span>
                                                    @if($resource->formattedSize())
                                                        <span>&bull;</span>
                                                        <span class="font-bold text-slate-700">{{ $resource->formattedSize() }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between gap-2">
                                            @if($resource->isFile())
                                                <a href="{{ route('student.courses.lessons.resources.download', [$course, $currentLesson, $resource]) }}"
                                                   class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                    <span>Download File</span>
                                                </a>
                                            @else
                                                <a href="{{ route('student.courses.lessons.resources.download', [$course, $currentLesson, $resource]) }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition">
                                                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                    <span>Open Link &rarr;</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Navigation & Completion Controls Bar -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Previous Lesson Button -->
                        <div>
                            @if($previousLesson)
                                <a href="{{ route('student.courses.lessons.show', [$course, $previousLesson]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs">
                                    &larr; Previous Lesson
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-4 py-2.5 text-xs font-medium text-slate-400 cursor-not-allowed select-none opacity-60">
                                    &larr; Previous Lesson
                                </span>
                            @endif
                        </div>

                        <!-- Mark as Complete Button -->
                        <div>
                            <form action="{{ route('student.courses.lessons.complete', [$course, $currentLesson]) }}" method="POST">
                                @csrf
                                @if($isCompleted)
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-300 px-5 py-2.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition shadow-2xs">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Completed ✓
                                    </button>
                                @else
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Mark as Complete
                                    </button>
                                @endif
                            </form>
                        </div>

                        <!-- Next Lesson Button -->
                        <div>
                            @if($nextLesson)
                                <a href="{{ route('student.courses.lessons.show', [$course, $nextLesson]) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition">
                                    Next Lesson &rarr;
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700">
                                    Course Finished 🎉
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Empty Lesson Fallback -->
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-2xs">
                        <svg class="h-12 w-12 mx-auto mb-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="text-base font-bold text-slate-900">Lessons coming soon</h3>
                        <p class="mt-1 text-xs text-slate-500">The curriculum for this course is currently being prepared.</p>
                        <div class="mt-4">
                            <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-500">
                                &larr; Back to My Courses
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Desktop Curriculum Sidebar (4 cols on desktop ~30%) -->
            <div class="hidden lg:block lg:col-span-4 space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-2xs overflow-hidden sticky top-24">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Course Curriculum</h3>
                        </div>
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                            {{ $progress['completed'] }}/{{ $progress['total'] }}
                        </span>
                    </div>

                    <div class="max-h-[calc(100vh-10rem)] overflow-y-auto divide-y divide-slate-100">
                        @forelse($modules as $mIndex => $module)
                            <div class="p-3">
                                <div class="text-xs font-bold text-slate-800 mb-2 px-2 flex items-center justify-between">
                                    <span class="truncate">{{ \Illuminate\Support\Str::startsWith($module->title, 'Module') ? $module->title : 'Module ' . ($mIndex + 1) . ': ' . $module->title }}</span>
                                    <span class="text-slate-400 font-medium text-[11px] shrink-0">{{ $module->lessons->count() }} lessons</span>
                                </div>

                                <div class="space-y-1">
                                    @forelse($module->lessons as $lesson)
                                        @php
                                            $lessonCompleted = in_array($lesson->id, array_keys($completedLessonIds));
                                            $isCurrent = $currentLesson && $currentLesson->id === $lesson->id;
                                        @endphp

                                        <a
                                            href="{{ route('student.courses.lessons.show', [$course, $lesson]) }}"
                                            class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ $isCurrent ? 'bg-indigo-50 border-l-4 border-indigo-600 font-bold text-indigo-900 shadow-2xs' : 'text-slate-700 hover:bg-slate-50' }}"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                @if($lessonCompleted)
                                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-[11px] font-bold shadow-2xs" title="Completed">
                                                        ✓
                                                    </div>
                                                @elseif($isCurrent)
                                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600 bg-white text-indigo-600 text-xs font-black">
                                                        &rarr;
                                                    </div>
                                                @else
                                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-slate-300 text-[10px]" title="Incomplete">
                                                        &bull;
                                                    </div>
                                                @endif

                                                <span class="truncate {{ $lessonCompleted && !$isCurrent ? 'text-slate-500 line-through' : '' }}">
                                                    {{ $lesson->title }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-1.5 shrink-0 text-slate-400 text-[11px]">
                                                @if(($lesson->resources_count ?? 0) > 0)
                                                    <span class="inline-flex items-center gap-0.5 rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-600 border border-indigo-100" title="{{ $lesson->resources_count }} {{ \Illuminate\Support\Str::plural('resource', $lesson->resources_count) }}">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                        </svg>
                                                        <span>{{ $lesson->resources_count }}</span>
                                                    </span>
                                                @endif
                                                @if($lesson->duration)
                                                    <span>{{ $lesson->duration }}</span>
                                                @endif
                                                @if($lesson->isVideo())
                                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @elseif($lesson->isPdf())
                                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                @else
                                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
    </main>

    <!-- Mobile Curriculum Drawer (Off-canvas for < lg screens) -->
    <div id="mobile-curriculum-drawer-container" class="fixed inset-0 z-50 lg:hidden hidden" role="dialog" aria-modal="true" aria-labelledby="mobile-curriculum-title">
        <!-- Backdrop -->
        <div id="mobile-curriculum-backdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

        <!-- Drawer Panel -->
        <div class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-xl flex flex-col z-10">
            <!-- Drawer Header -->
            <div class="flex items-center justify-between h-16 px-5 border-b border-slate-100 bg-slate-50/80">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <h3 id="mobile-curriculum-title" class="text-xs font-bold uppercase tracking-wider text-slate-800">Course Curriculum</h3>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                        {{ $progress['completed'] }}/{{ $progress['total'] }}
                    </span>
                    <button id="mobile-curriculum-close" type="button" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition" aria-label="Close curriculum drawer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Drawer Body: Scrollable Modules & Lessons -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 p-3">
                @forelse($modules as $mIndex => $module)
                    <div class="p-2">
                        <div class="text-xs font-bold text-slate-800 mb-2 px-2 flex items-center justify-between">
                            <span class="truncate">{{ \Illuminate\Support\Str::startsWith($module->title, 'Module') ? $module->title : 'Module ' . ($mIndex + 1) . ': ' . $module->title }}</span>
                            <span class="text-slate-400 font-medium text-[11px] shrink-0">{{ $module->lessons->count() }} lessons</span>
                        </div>

                        <div class="space-y-1">
                            @forelse($module->lessons as $lesson)
                                @php
                                    $lessonCompleted = in_array($lesson->id, array_keys($completedLessonIds));
                                    $isCurrent = $currentLesson && $currentLesson->id === $lesson->id;
                                @endphp

                                <a
                                    href="{{ route('student.courses.lessons.show', [$course, $lesson]) }}"
                                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ $isCurrent ? 'bg-indigo-50 border-l-4 border-indigo-600 font-bold text-indigo-900 shadow-2xs' : 'text-slate-700 hover:bg-slate-50' }}"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        @if($lessonCompleted)
                                            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-[11px] font-bold shadow-2xs">
                                                ✓
                                            </div>
                                        @elseif($isCurrent)
                                            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600 bg-white text-indigo-600 text-xs font-black">
                                                &rarr;
                                            </div>
                                        @else
                                            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-slate-300 text-[10px]">
                                                &bull;
                                            </div>
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
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($lesson->isPdf())
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @else
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('mobile-curriculum-toggle');
    const drawerContainer = document.getElementById('mobile-curriculum-drawer-container');
    const backdrop = document.getElementById('mobile-curriculum-backdrop');
    const closeBtn = document.getElementById('mobile-curriculum-close');

    function openDrawer() {
        if (!drawerContainer) return;
        drawerContainer.classList.remove('hidden');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
    }

    function closeDrawer() {
        if (!drawerContainer) return;
        drawerContainer.classList.add('hidden');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawerContainer && !drawerContainer.classList.contains('hidden')) {
            closeDrawer();
        }
    });
});
</script>
@endpush