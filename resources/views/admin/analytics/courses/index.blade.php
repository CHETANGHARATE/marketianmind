@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Course Intelligence
                </span>
                <span class="text-xs text-slate-500">Real-time Performance & Learning Analytics</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Course Analytics
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Track enrollment velocity, completion rates, learner engagement, and monetization across the course catalog.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($courses->total()) }}</span> Filtered Courses
            </span>
        </div>
    </div>

    <!-- Catalog-Wide KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Catalog Revenue -->
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Catalog Revenue</span>
                <div class="rounded-lg bg-emerald-500/20 p-2 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">₹{{ number_format($totalCatalogRevenue, 2) }}</p>
                <p class="mt-1 text-[11px] text-emerald-400/80">From completed, verified orders</p>
            </div>
        </div>

        <!-- Total Enrollments -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Enrollments</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($totalEnrollments) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">All-time course registrations</p>
            </div>
        </div>

        <!-- Active Learners -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Active Learners</span>
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($activeLearnersCount) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Currently in progress</p>
            </div>
        </div>

        <!-- Overall Completion Rate -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Avg Completion Rate</span>
                <div class="rounded-lg bg-indigo-500/10 p-2 text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ $overallCompletionRate }}%</p>
                <p class="mt-1 text-[11px] text-slate-500">Catalog-wide completed ratio</p>
            </div>
        </div>
    </div>

    <!-- Top Ranking Highlights (Top by Enrollments, Completion, Revenue) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Most Enrolled -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                Most Enrolled Course
            </div>
            @if($topEnrollmentCourse)
                <div class="flex items-center justify-between">
                    <div class="truncate mr-2">
                        <a href="{{ route('admin.analytics.courses.show', $topEnrollmentCourse) }}" class="text-sm font-bold text-white hover:text-amber-400 transition truncate block">
                            {{ $topEnrollmentCourse->title }}
                        </a>
                        <p class="text-xs text-slate-400">{{ $topEnrollmentCourse->category->name ?? 'General' }}</p>
                    </div>
                    <span class="text-sm font-black text-amber-400 shrink-0">{{ number_format($topEnrollmentCourse->total_enrollments) }} enrolled</span>
                </div>
            @else
                <p class="text-xs text-slate-500 italic">No enrollment data available</p>
            @endif
        </div>

        <!-- Highest Completion -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Highest Completion Rate
            </div>
            @if($topCompletionCourse)
                <div class="flex items-center justify-between">
                    <div class="truncate mr-2">
                        <a href="{{ route('admin.analytics.courses.show', $topCompletionCourse) }}" class="text-sm font-bold text-white hover:text-emerald-400 transition truncate block">
                            {{ $topCompletionCourse->title }}
                        </a>
                        <p class="text-xs text-slate-400">{{ $topCompletionCourse->completed_enrollments }} of {{ $topCompletionCourse->total_enrollments }} completed</p>
                    </div>
                    <span class="text-sm font-black text-emerald-400 shrink-0">{{ $topCompletionCourse->completion_rate }}%</span>
                </div>
            @else
                <p class="text-xs text-slate-500 italic">No completion data available</p>
            @endif
        </div>

        <!-- Top Revenue -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Top Grossing Course
            </div>
            @if($topRevenueCourse && $topRevenueCourse->total_revenue > 0)
                <div class="flex items-center justify-between">
                    <div class="truncate mr-2">
                        <a href="{{ route('admin.analytics.courses.show', $topRevenueCourse) }}" class="text-sm font-bold text-white hover:text-indigo-400 transition truncate block">
                            {{ $topRevenueCourse->title }}
                        </a>
                        <p class="text-xs text-slate-400">{{ $topRevenueCourse->is_free ? 'Free' : '₹' . number_format($topRevenueCourse->price, 2) }}</p>
                    </div>
                    <span class="text-sm font-black text-indigo-400 shrink-0">₹{{ number_format($topRevenueCourse->total_revenue, 2) }}</span>
                </div>
            @else
                <p class="text-xs text-slate-500 italic">No paid course revenue yet</p>
            @endif
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
        <form method="GET" action="{{ route('admin.analytics.courses') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Search Course
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Course title or description..."
                           class="w-full rounded-xl border border-slate-800 bg-slate-950 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Publication Status
                </label>
                <select name="status"
                        id="status"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Category
                </label>
                <select name="category"
                        id="category"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Option -->
            <div>
                <label for="sort" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Sort Metric
                </label>
                <select name="sort"
                        id="sort"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="enrollments_desc" {{ request('sort', 'enrollments_desc') === 'enrollments_desc' ? 'selected' : '' }}>Enrollments (High to Low)</option>
                    <option value="completion_desc" {{ request('sort') === 'completion_desc' ? 'selected' : '' }}>Completion Rate (High to Low)</option>
                    <option value="revenue_desc" {{ request('sort') === 'revenue_desc' ? 'selected' : '' }}>Revenue (High to Low)</option>
                    <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                    <option value="created_desc" {{ request('sort') === 'created_desc' ? 'selected' : '' }}>Newest First</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 lg:col-span-5 flex items-center justify-end gap-3 pt-2">
                @if(request()->hasAny(['search', 'status', 'category', 'sort']))
                    <a href="{{ route('admin.analytics.courses') }}"
                       class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:border-slate-700 transition">
                        Reset Filters
                    </a>
                @endif
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-xs">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Course Comparison Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="border-b border-slate-800 bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th scope="col" class="py-3.5 pl-6 pr-3">Course</th>
                        <th scope="col" class="px-3 py-3.5">Status</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Enrollments</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Active</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Completed</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Completion %</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Avg Progress</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Revenue</th>
                        <th scope="col" class="py-3.5 pl-3 pr-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($courses as $course)
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- Course Title & Category -->
                            <td class="py-4 pl-6 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-slate-300 font-bold text-xs border border-slate-700">
                                        {{ substr($course->title, 0, 2) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.analytics.courses.show', $course) }}" class="font-bold text-white hover:text-amber-400 transition block truncate max-w-xs sm:max-w-sm">
                                            {{ $course->title }}
                                        </a>
                                        <span class="text-xs text-slate-500">
                                            {{ $course->category->name ?? 'Uncategorized' }} &bull; {{ $course->is_free ? 'Free' : '₹' . number_format($course->price, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-3 py-4">
                                @if($course->status === \App\Enums\CourseStatus::PUBLISHED)
                                    <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                        Published
                                    </span>
                                @elseif($course->status === \App\Enums\CourseStatus::DRAFT)
                                    <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                                        Draft
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-500/10 px-2 py-0.5 text-xs font-semibold text-slate-400 ring-1 ring-inset ring-slate-500/20">
                                        {{ ucfirst($course->status->value ?? 'Archived') }}
                                    </span>
                                @endif
                            </td>

                            <!-- Total Enrollments -->
                            <td class="px-3 py-4 text-right font-semibold text-white">
                                {{ number_format($course->total_enrollments) }}
                            </td>

                            <!-- Active Enrollments -->
                            <td class="px-3 py-4 text-right text-amber-400 font-medium">
                                {{ number_format($course->active_enrollments) }}
                            </td>

                            <!-- Completed Enrollments -->
                            <td class="px-3 py-4 text-right text-emerald-400 font-medium">
                                {{ number_format($course->completed_enrollments) }}
                            </td>

                            <!-- Completion Rate -->
                            <td class="px-3 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <span class="font-bold {{ $course->completion_rate >= 50 ? 'text-emerald-400' : ($course->completion_rate >= 20 ? 'text-amber-400' : 'text-slate-400') }}">
                                        {{ $course->completion_rate }}%
                                    </span>
                                </div>
                            </td>

                            <!-- Average Progress -->
                            <td class="px-3 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ min(100, $course->avg_progress) }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium">{{ $course->avg_progress }}%</span>
                                </div>
                            </td>

                            <!-- Revenue -->
                            <td class="px-3 py-4 text-right font-black {{ $course->total_revenue > 0 ? 'text-emerald-400' : 'text-slate-500' }}">
                                ₹{{ number_format($course->total_revenue, 2) }}
                            </td>

                            <!-- Action -->
                            <td class="py-4 pl-3 pr-6 text-right">
                                <a href="{{ route('admin.analytics.courses.show', $course) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                    <span>Deep Dive</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-800/80 text-slate-500 mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white">No courses match your filter criteria</h3>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your search terms or filters to view course performance metrics.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($courses->hasPages())
            <div class="border-t border-slate-800 bg-slate-950/60 px-6 py-4">
                {{ $courses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection