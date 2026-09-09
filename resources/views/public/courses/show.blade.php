@extends('layouts.public')

@section('subcontent')
<div>
    @if($course)
        <!-- Dynamic Course Hero -->
        <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 via-white to-white border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
                    <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">&larr; Back to Courses</a>
                    <span>/</span>
                    <span class="text-indigo-600">{{ $course->title }}</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    <div class="lg:col-span-8">
                        <div class="flex flex-wrap items-center gap-2.5 mb-4">
                            @if($course->category)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15">
                                    {{ $course->category->name }}
                                </span>
                            @endif

                            @if($course->is_free)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    Free Course
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">
                                    ₹{{ number_format($course->effectivePrice(), 2) }}
                                </span>
                            @endif

                            @if($course->featured)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Featured
                                </span>
                            @endif
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            {{ $course->title }}
                        </h1>

                        <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                            {{ $course->short_description ?? $course->description }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-6 text-xs text-slate-500">
                            @if($course->estimated_duration)
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">Duration:</span>
                                    <span>{{ $course->estimated_duration }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Modules:</span>
                                <span>{{ $course->modules->count() }} {{ Str::plural('Module', $course->modules->count()) }}</span>
                            </div>

                            @php
                                $totalLessonsCount = $course->modules->sum(fn($m) => $m->lessons->count());
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Lessons:</span>
                                <span>{{ $totalLessonsCount }} {{ Str::plural('Lesson', $totalLessonsCount) }}</span>
                            </div>

                            @if($course->instructor_name)
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">Instructor:</span>
                                    <span>{{ $course->instructor_name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- CTA Enrollment Card -->
                    <div class="lg:col-span-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sticky top-6">
                            @if($course->thumbnailUrl())
                                <div class="aspect-video rounded-xl overflow-hidden mb-6 bg-slate-100">
                                    <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                                </div>
                            @endif

                            <div class="text-center pb-5 border-b border-slate-100">
                                <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Tuition & Access</span>
                                <div class="mt-2 text-3xl font-black text-slate-900">
                                    @if($course->is_free)
                                        <span class="text-emerald-600">Free</span>
                                    @else
                                        <span>₹{{ number_format($course->effectivePrice(), 2) }}</span>
                                        @if($course->hasDiscount())
                                            <span class="text-sm font-normal text-slate-400 line-through ml-2">₹{{ number_format($course->price, 2) }}</span>
                                        @endif
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $course->is_free ? 'Full access at zero cost for business owners' : 'Lifetime access with all upcoming module updates' }}
                                </p>
                            </div>

                            <div class="py-5 space-y-2.5 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Full curriculum access</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Actionable video & text lessons</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Downloadable frameworks & templates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Self-paced on desktop & mobile</span>
                                </div>
                            </div>

                            <div class="pt-2">
                                @if($isEnrolled)
                                    <a href="{{ route('student.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition text-center">
                                        Continue Learning &rarr;
                                    </a>
                                @elseif($course->is_free)
                                    @auth
                                        @if(auth()->user()->isStudent())
                                            <form action="{{ route('student.courses.enroll', $course) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                                    Enroll for Free &rarr;
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('student.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                                Access Course Player &rarr;
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition text-center">
                                            Login to Enroll Free &rarr;
                                        </a>
                                        <p class="mt-2 text-center text-xs text-slate-400">
                                            New here? <a href="{{ route('register') }}" class="text-indigo-600 font-semibold underline">Register free account</a>
                                        </p>
                                    @endauth
                                @else
                                    <div class="space-y-3 text-center">
                                        <button type="button" disabled class="inline-flex w-full items-center justify-center rounded-lg bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-500 cursor-not-allowed">
                                            Purchase Course
                                        </button>
                                        <div class="rounded-lg bg-amber-50 p-2.5 text-xs text-amber-800 border border-amber-200">
                                            Purchase functionality coming soon.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Course Description -->
        @if($course->description)
            <section class="py-14 bg-white border-b border-slate-200">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Overview</span>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                            About This Course
                        </h2>
                        <div class="mt-4 text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ $course->description }}
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!-- Course Curriculum Breakdown -->
        <section class="py-16 bg-slate-50 border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Curriculum Breakdown</span>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Course Modules Preview
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Structured step-by-step modules designed to build actionable marketing competence.
                    </p>
                </div>

                @if($course->modules->count() > 0)
                    <div class="space-y-4">
                        @foreach($course->modules as $index => $module)
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <div class="p-5 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">
                                            Module {{ sprintf('%02d', $index + 1) }}
                                        </span>
                                        <h3 class="text-base font-bold text-slate-900 mt-0.5">{{ $module->title }}</h3>
                                        @if($module->description)
                                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $module->description }}</p>
                                        @endif
                                    </div>
                                    <span class="text-xs font-semibold text-slate-400 shrink-0">
                                        {{ $module->lessons->count() }} {{ Str::plural('Lesson', $module->lessons->count()) }}
                                    </span>
                                </div>

                                @if($module->lessons->count() > 0)
                                    <div class="divide-y divide-slate-100">
                                        @foreach($module->lessons as $lesson)
                                            <div class="px-5 py-3.5 flex items-center justify-between gap-4 text-xs hover:bg-slate-50/70 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    @if($lesson->isVideo())
                                                        <svg class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @elseif($lesson->isPdf())
                                                        <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-4 w-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    @endif

                                                    <span class="font-medium text-slate-800 truncate">{{ $lesson->title }}</span>
                                                </div>

                                                <div class="flex items-center gap-2.5 shrink-0">
                                                    @if($lesson->duration)
                                                        <span class="text-slate-400 text-xs">{{ $lesson->duration }}</span>
                                                    @endif

                                                    @if($lesson->isPreview())
                                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                            Preview
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-slate-400" title="Enrolled students only">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                            </svg>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-4 text-center text-xs text-slate-400">
                                        Lessons coming soon.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-500">
                        Curriculum structure is currently being finalized.
                    </div>
                @endif
            </div>
        </section>
    @else
        <!-- Static Course Hero & Preview Fallback -->
        <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 via-white to-white border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
                    <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">&larr; Back to Courses</a>
                    <span>/</span>
                    <span class="text-indigo-600">Digital Marketing for Business Owners</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <div class="lg:col-span-8">
                        <div class="flex flex-wrap items-center gap-2.5 mb-4">
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15">
                                Comprehensive Program
                            </span>
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                Coming Soon
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            Digital Marketing for Business Owners
                        </h1>

                        <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed">
                            Learn how to grow your business online with limited time and budget. Gain complete marketing independence and master customer acquisition without relying on expensive monthly agency retainers.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-6 text-xs text-slate-500">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Format:</span>
                                <span>Self-Paced Video Lessons</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Structure:</span>
                                <span>6 Core Modules</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Level:</span>
                                <span>Designed for Business Owners</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="text-center pb-6 border-b border-slate-100">
                                <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Program Status</span>
                                <div class="mt-2 text-2xl font-black text-indigo-600">Coming Soon</div>
                                <p class="mt-1 text-xs text-slate-500">Curriculum finalized & currently in production</p>
                            </div>

                            <div class="py-6 space-y-3 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Actionable step-by-step video lessons</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Fill-in marketing plan templates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>No technical background required</span>
                                </div>
                            </div>

                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                Register Interest &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Static Curriculum Breakdown Fallback -->
        <section class="py-16 bg-slate-50/70 border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-12">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Curriculum Breakdown</span>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Course Modules Preview
                    </h2>
                    <p class="mt-3 text-sm text-slate-600">
                        Six structured modules covering the end-to-end journey of growing a business online:
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 01</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Understanding Digital Marketing</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Demystifying online channels, defining digital marketing without jargon, and mapping how online channels directly connect to business revenue.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 02</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Customer Understanding</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Building realistic customer personas, identifying buyer triggers, and knowing where customers look for answers.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 03</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Social Media Marketing</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Selecting the right platforms, converting profiles, and maintaining consistent publishing calendars.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 04</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Content Strategy</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Framing clear content pillars, avoiding burn-out, and producing content that solves prospect problems.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 05</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Creating Marketing Content</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Simple copywriting templates, hooks, calls to action, and batch workflow methods for busy founders.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 06</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Growing Your Business Online</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Measuring key acquisition channels, tracking conversion metrics, and compounding audience trust over time.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection