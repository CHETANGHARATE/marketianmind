@extends('layouts.student')

@section('subcontent')
<div class="space-y-8" x-data="{ filter: 'all' }">
    <!-- Header & Quick Resume Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                Learning Analytics
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Track your course completions, milestones, remaining lessons, and learning momentum.
            </p>
        </div>

        @if($nextContinueCourse)
            <div class="shrink-0">
                <a href="{{ $nextContinueCourse['action_url'] }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                    <span>{{ $nextContinueCourse['action_label'] }}</span>
                    <span class="truncate max-w-[140px] font-normal opacity-80 hidden sm:inline">&bull; {{ $nextContinueCourse['title'] }}</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @endif
    </div>

    <!-- 1. Learning Overview 4-Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Overall Progress -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Overall Progress</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ $overview['overall_progress'] }}%</span>
                <span class="text-xs font-medium text-slate-500">completed</span>
            </div>
            <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ $overview['overall_progress'] }}%"></div>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">
                {{ $overview['completed_lessons'] }} of {{ $overview['total_lessons'] }} total lessons finished
            </p>
        </div>

        <!-- 2. Enrolled Courses -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Courses</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ $overview['total_courses'] }}</span>
                <span class="text-xs font-medium text-slate-500">courses</span>
            </div>
            <div class="mt-4 flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1 font-semibold text-sky-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    {{ $overview['in_progress_courses'] }} In Progress
                </span>
                <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $overview['completed_courses'] }} Done
                </span>
            </div>
        </div>

        <!-- 3. Completed Lessons -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lessons Finished</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ $overview['completed_lessons'] }}</span>
                <span class="text-xs font-medium text-slate-500">lessons</span>
            </div>
            <p class="mt-4 text-xs font-semibold text-slate-600 flex items-center gap-1.5">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>~{{ $overview['total_learning_hours'] }} hours invested</span>
            </p>
        </div>

        <!-- 4. Certificates Earned -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Certificates</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ $overview['certificates_earned'] }}</span>
                <span class="text-xs font-medium text-slate-500">earned</span>
            </div>
            <p class="mt-4 text-xs font-semibold text-amber-700">
                @if($overview['certificates_earned'] > 0)
                    Verified digital credentials
                @else
                    Earned on 100% course completion
                @endif
            </p>
        </div>
    </div>

    <!-- 2. Main Content Grid: Course-by-Course Analytics (8 cols) & Recent Activity (4 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Course-Level Breakdown (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Course Progress Breakdown
                    </h2>
                    <p class="text-xs text-slate-500">
                        Detailed lesson completion status across all your enrolled tracks.
                    </p>
                </div>

                <!-- Filter Tabs -->
                @if($overview['total_courses'] > 0)
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-bold text-slate-600">
                        <button type="button"
                                @click="filter = 'all'"
                                :class="filter === 'all' ? 'bg-white text-indigo-700 shadow-2xs' : 'hover:text-slate-900'"
                                class="rounded-lg px-3 py-1.5 transition">
                            All ({{ $overview['total_courses'] }})
                        </button>
                        <button type="button"
                                @click="filter = 'in_progress'"
                                :class="filter === 'in_progress' ? 'bg-white text-indigo-700 shadow-2xs' : 'hover:text-slate-900'"
                                class="rounded-lg px-3 py-1.5 transition">
                            In Progress ({{ $overview['in_progress_courses'] }})
                        </button>
                        <button type="button"
                                @click="filter = 'completed'"
                                :class="filter === 'completed' ? 'bg-white text-indigo-700 shadow-2xs' : 'hover:text-slate-900'"
                                class="rounded-lg px-3 py-1.5 transition">
                            Completed ({{ $overview['completed_courses'] }})
                        </button>
                    </div>
                @endif
            </div>

            @if(count($courseAnalytics) > 0)
                <div class="space-y-4">
                    @foreach($courseAnalytics as $course)
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs hover:shadow-xs transition"
                             x-show="filter === 'all' || (filter === 'in_progress' && {{ !$course['is_completed'] && $course['percentage'] > 0 ? 'true' : 'false' }}) || (filter === 'completed' && {{ $course['is_completed'] ? 'true' : 'false' }})"
                             x-transition>
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="flex items-start gap-4 min-w-0">
                                    <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/80">
                                        @if($course['thumbnail'])
                                            <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="h-full w-full flex items-center justify-center bg-indigo-50 text-indigo-600 font-bold text-xs">
                                                Course
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($course['category'])
                                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 uppercase tracking-wider border border-indigo-100">
                                                    {{ $course['category'] }}
                                                </span>
                                            @endif

                                            @if($course['is_completed'])
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                                                    ✓ Completed
                                                </span>
                                            @elseif($course['percentage'] > 0)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-bold text-sky-700 border border-sky-200">
                                                    In Progress
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                                    Not Started
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-base font-bold text-slate-900 mt-1 truncate">
                                            <a href="{{ route('student.courses.show', $course['model']) }}" class="hover:text-indigo-600 transition">
                                                {{ $course['title'] }}
                                            </a>
                                        </h3>

                                        <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                            <span>{{ $course['modules_count'] }} {{ \Illuminate\Support\Str::plural('Module', $course['modules_count']) }}</span>
                                            <span>&bull;</span>
                                            <span>{{ $course['duration'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5 self-start shrink-0">
                                    @if($course['certificate'])
                                        <a href="{{ route('student.certificates.show', $course['certificate']) }}"
                                           class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100 transition shadow-2xs"
                                           title="View Certificate">
                                            <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                            </svg>
                                            <span>Certificate</span>
                                        </a>
                                    @endif

                                    <a href="{{ $course['action_url'] }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl {{ $course['is_completed'] ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'bg-indigo-600 text-white hover:bg-indigo-500' }} px-3.5 py-2 text-xs font-bold transition shadow-2xs">
                                        <span>{{ $course['action_label'] }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Progress Track Bar & Stats -->
                            <div class="mt-5 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                                    <span class="text-slate-700">
                                        {{ $course['completed_lessons'] }} of {{ $course['total_lessons'] }} lessons completed
                                    </span>
                                    <span class="{{ $course['is_completed'] ? 'text-emerald-600' : 'text-indigo-600' }} font-bold">
                                        {{ $course['percentage'] }}%
                                    </span>
                                </div>

                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div class="{{ $course['is_completed'] ? 'bg-emerald-500' : 'bg-indigo-600' }} h-2.5 rounded-full transition-all duration-300"
                                         style="width: {{ $course['percentage'] }}%"></div>
                                </div>

                                <div class="flex items-center justify-between mt-2.5 text-[11px] text-slate-500">
                                    <span>
                                        @if($course['is_completed'])
                                            All {{ $course['total_lessons'] }} lessons completed
                                        @else
                                            {{ $course['remaining_lessons'] }} {{ \Illuminate\Support\Str::plural('lesson', $course['remaining_lessons']) }} remaining
                                        @endif
                                    </span>

                                    @if($course['next_lesson'] && !$course['is_completed'])
                                        <span class="truncate max-w-[200px] text-indigo-600 font-medium">
                                            Next: {{ $course['next_lesson']->title }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-2xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Your learning journey begins here</h3>
                    <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        Enroll in a course to start tracking your module completions, marketing skills, and certificates.
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('courses') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition">
                            Explore Courses &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right: Recent Learning Activity (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs space-y-4 sticky top-24">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Recent Learning Activity
                        </h3>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400">Timeline</span>
                </div>

                @if($recentActivity->count() > 0)
                    <div class="space-y-3.5">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-start gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $activity['completed'] ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600' }}">
                                    @if($activity['completed'])
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <h4 class="text-xs font-bold text-slate-900 truncate" title="{{ $activity['lesson_title'] }}">
                                            {{ $activity['lesson_title'] }}
                                        </h4>
                                        <span class="text-[10px] text-slate-400 shrink-0">
                                            {{ $activity['relative_time'] }}
                                        </span>
                                    </div>

                                    <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                        {{ $activity['course_title'] }}
                                    </p>

                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider {{ $activity['completed'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-indigo-50 text-indigo-700 border border-indigo-100' }}">
                                            {{ $activity['completed'] ? 'Completed' : 'Watched' }}
                                        </span>

                                        <a href="{{ $activity['resume_url'] }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-500 transition">
                                            Resume &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-slate-400">
                        <svg class="h-8 w-8 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-medium text-slate-500">No recent activity yet</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Start watching lessons to see your study timeline here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection