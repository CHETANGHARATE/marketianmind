@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.analytics.courses') }}" class="text-slate-400 hover:text-white transition flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Course Analytics
            </a>
            <span class="text-slate-600">/</span>
            <span class="text-amber-400 truncate max-w-xs">{{ $course->title }}</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses.edit', $course) }}" class="rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Edit Course
            </a>
            <a href="{{ route('admin.courses.modules.index', $course) }}" class="rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Manage Curriculum
            </a>
        </div>
    </div>

    <!-- Course Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                        {{ $course->category->name ?? 'General Marketing' }}
                    </span>
                    @if($course->status === \App\Enums\CourseStatus::PUBLISHED)
                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                            Published
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-slate-500/10 px-2 py-0.5 text-xs font-semibold text-slate-400 ring-1 ring-inset ring-slate-500/20">
                            {{ ucfirst($course->status->value ?? 'Draft') }}
                        </span>
                    @endif
                    <span class="text-xs text-slate-400">
                        Price: <strong class="text-white">{{ $course->is_free ? 'Free' : '₹' . number_format($course->price, 2) }}</strong>
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                    {{ $course->title }}
                </h1>
                <p class="text-xs text-slate-400">
                    Created {{ $course->created_at->format('M d, Y') }} &bull; {{ $stats['published_modules_count'] }} Modules &bull; {{ $stats['published_lessons_count'] }} Published Lessons
                </p>
            </div>

            <!-- Period Switcher Filter -->
            <div class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 p-1">
                <a href="{{ route('admin.analytics.courses.show', ['course' => $course, 'period' => '7d']) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period === '7d' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('admin.analytics.courses.show', ['course' => $course, 'period' => '30d']) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period === '30d' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Last 30 Days
                </a>
                <a href="{{ route('admin.analytics.courses.show', ['course' => $course, 'period' => '90d']) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period === '90d' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Last 90 Days
                </a>
            </div>
        </div>
    </div>

    <!-- 6 KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Enrollments -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Enrolled</span>
            <p class="mt-2 text-2xl font-black text-white">{{ number_format($stats['total_enrollments']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">+{{ $stats['enrollments_last_30_days'] }} last 30d</p>
        </div>

        <!-- Active Learners -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-400">Active Learners</span>
            <p class="mt-2 text-2xl font-black text-amber-400">{{ number_format($stats['active_enrollments']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Currently studying</p>
        </div>

        <!-- Completed Learners -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-400">Completions</span>
            <p class="mt-2 text-2xl font-black text-emerald-400">{{ number_format($stats['completed_enrollments']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Finished 100%</p>
        </div>

        <!-- Completion Rate -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-400">Completion Rate</span>
            <p class="mt-2 text-2xl font-black text-indigo-400">{{ $stats['completion_rate'] }}%</p>
            <p class="mt-1 text-[11px] text-slate-500">Of total enrolled</p>
        </div>

        <!-- Average Progress -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-sky-400">Avg Progress</span>
            <p class="mt-2 text-2xl font-black text-sky-400">{{ $stats['avg_progress'] }}%</p>
            <p class="mt-1 text-[11px] text-slate-500">Across enrolled users</p>
        </div>

        <!-- Course Revenue -->
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/10 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-400">Total Revenue</span>
            <p class="mt-2 text-2xl font-black text-white">₹{{ number_format($stats['total_revenue'], 2) }}</p>
            <p class="mt-1 text-[11px] text-emerald-400/80">Paid orders</p>
        </div>
    </div>

    <!-- Progress Distribution & Learner Funnel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Progress Distribution Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    Learner Progress Distribution
                </h3>
                <span class="text-xs text-slate-400">{{ number_format($stats['total_enrollments']) }} Students</span>
            </div>

            <!-- Segment Bars -->
            <div class="space-y-3">
                <!-- Not Started -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-400 font-medium">Not Started (0%)</span>
                        <span class="font-bold text-slate-300">{{ $progressDistribution['not_started']['count'] }} students ({{ $progressDistribution['not_started']['percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-slate-600 h-2 rounded-full" style="width: {{ $progressDistribution['not_started']['percentage'] }}%"></div>
                    </div>
                </div>

                <!-- Early Stage (1-49%) -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-amber-400 font-medium">Early Stage (1% – 49%)</span>
                        <span class="font-bold text-amber-400">{{ $progressDistribution['started']['count'] }} students ({{ $progressDistribution['started']['percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $progressDistribution['started']['percentage'] }}%"></div>
                    </div>
                </div>

                <!-- Advanced (50-99%) -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-sky-400 font-medium">Advanced (50% – 99%)</span>
                        <span class="font-bold text-sky-400">{{ $progressDistribution['in_progress']['count'] }} students ({{ $progressDistribution['in_progress']['percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-sky-500 h-2 rounded-full" style="width: {{ $progressDistribution['in_progress']['percentage'] }}%"></div>
                    </div>
                </div>

                <!-- Completed (100%) -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-emerald-400 font-medium">Completed (100%)</span>
                        <span class="font-bold text-emerald-400">{{ $progressDistribution['completed']['count'] }} students ({{ $progressDistribution['completed']['percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $progressDistribution['completed']['percentage'] }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Certificates count -->
            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs">
                <span class="text-slate-400">Certificates Issued</span>
                <span class="font-bold text-amber-400">{{ number_format($stats['certificates_issued']) }} issued</span>
            </div>
        </div>

        <!-- Period Trend Overview -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Enrollment & Completion Activity ({{ $trends['days_count'] }} Days)
                </h3>
                <span class="text-xs text-slate-500">Daily Trajectory</span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <span class="text-[11px] text-slate-400 font-semibold uppercase">New Enrollees</span>
                    <p class="text-xl font-black text-amber-400 mt-1">{{ array_sum($trends['enrollment_history']) }}</p>
                    <p class="text-[11px] text-slate-500">In selected period</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <span class="text-[11px] text-slate-400 font-semibold uppercase">New Graduates</span>
                    <p class="text-xl font-black text-emerald-400 mt-1">{{ array_sum($trends['completion_history']) }}</p>
                    <p class="text-[11px] text-slate-500">In selected period</p>
                </div>
            </div>

            <!-- Mini Daily Histogram/Table (Last 7 active days) -->
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 overflow-hidden">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950 text-[10px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3 text-right">Enrollments</th>
                            <th class="py-2 px-3 text-right">Completions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @php
                            $dates = array_reverse(array_keys($trends['enrollment_history']));
                            $recentDates = array_slice($dates, 0, 7);
                        @endphp
                        @foreach($recentDates as $date)
                            <tr>
                                <td class="py-1.5 px-3 font-medium text-slate-300">
                                    {{ \Carbon\Carbon::parse($date)->format('M d, D') }}
                                </td>
                                <td class="py-1.5 px-3 text-right font-bold text-amber-400">
                                    {{ $trends['enrollment_history'][$date] ?? 0 }}
                                </td>
                                <td class="py-1.5 px-3 text-right font-bold text-emerald-400">
                                    {{ $trends['completion_history'][$date] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Curriculum Breakdown & Drop-off Analysis -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden space-y-4 p-5">
        <div class="border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Lesson-Level Engagement & Drop-off Analysis
            </h3>
            <p class="text-xs text-slate-400 mt-1">
                Analyze student completion rates for individual lessons to identify bottlenecks where students drop off.
            </p>
        </div>

        @forelse($moduleAnalytics as $module)
            <div class="rounded-xl border border-slate-800 bg-slate-950/80 overflow-hidden">
                <div class="bg-slate-900 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-white">
                        Module {{ $module['order'] }}: {{ $module['title'] }}
                    </span>
                    <span class="text-[11px] text-slate-400">
                        {{ count($module['lessons']) }} published lessons
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 bg-slate-950 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-2.5 pl-4 pr-2">Lesson</th>
                                <th class="px-2 py-2.5">Type</th>
                                <th class="px-2 py-2.5 text-right">Completions</th>
                                <th class="px-2 py-2.5 text-right">Completion Rate</th>
                                <th class="py-2.5 pl-2 pr-4 text-right">Drop-off vs Previous</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($module['lessons'] as $lesson)
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="py-2.5 pl-4 pr-2 font-medium text-white">
                                        {{ $lesson['order'] }}. {{ $lesson['title'] }}
                                    </td>
                                    <td class="px-2 py-2.5">
                                        <span class="inline-flex items-center rounded-md bg-slate-800 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">
                                            {{ ucfirst($lesson['content_type'] instanceof \BackedEnum ? $lesson['content_type']->value : (string) $lesson['content_type']) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2.5 text-right font-bold text-slate-200">
                                        {{ number_format($lesson['completions_count']) }}
                                    </td>
                                    <td class="px-2 py-2.5 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <div class="w-12 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ min(100, $lesson['completion_rate']) }}%"></div>
                                            </div>
                                            <span class="font-bold text-amber-400">{{ $lesson['completion_rate'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 pl-2 pr-4 text-right">
                                        @if($lesson['dropoff_from_previous'] !== null)
                                            @if($lesson['dropoff_from_previous'] > 0)
                                                <span class="inline-flex items-center text-rose-400 font-semibold text-[11px]">
                                                    -{{ $lesson['dropoff_from_previous'] }}%
                                                </span>
                                            @elseif($lesson['dropoff_from_previous'] < 0)
                                                <span class="inline-flex items-center text-emerald-400 font-semibold text-[11px]">
                                                    +{{ abs($lesson['dropoff_from_previous']) }}%
                                                </span>
                                            @else
                                                <span class="text-slate-500 font-semibold text-[11px]">0%</span>
                                            @endif
                                        @else
                                            <span class="text-slate-600 text-[11px]">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 text-center text-slate-500 italic">
                                        No published lessons in this module
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-slate-500 italic">
                No modules or lessons have been published for this course yet.
            </div>
        @endforelse
    </div>
</div>
@endsection