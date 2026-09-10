@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Curriculum Engagement
                </span>
                <span class="text-xs text-slate-500">Student Course Access</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Course Enrollments
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Monitor, search, and inspect learner course enrollments, completion states, and progress.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($counts['all']) }}</span> Total Enrollments
            </span>
        </div>
    </div>

    <!-- Filter Tabs & Controls -->
    <div class="space-y-4">
        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800/80 pb-3">
            <a href="{{ route('admin.enrollments.index', array_merge(request()->except(['status', 'completion', 'page']), ['status' => 'all'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $status === 'all' && $completion === 'all' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                All ({{ number_format($counts['all']) }})
            </a>
            <a href="{{ route('admin.enrollments.index', array_merge(request()->except(['status', 'completion', 'page']), ['status' => 'active'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $status === 'active' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Active ({{ number_format($counts['active']) }})
            </a>
            <a href="{{ route('admin.enrollments.index', array_merge(request()->except(['status', 'completion', 'page']), ['status' => 'completed'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $status === 'completed' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Completed ({{ number_format($counts['completed']) }})
            </a>
            @if($counts['cancelled'] > 0)
                <a href="{{ route('admin.enrollments.index', array_merge(request()->except(['status', 'completion', 'page']), ['status' => 'cancelled'])) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $status === 'cancelled' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    Cancelled ({{ number_format($counts['cancelled']) }})
                </a>
            @endif
        </div>

        <!-- Search & Dropdown Filters Form -->
        <form method="GET" action="{{ route('admin.enrollments.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            <!-- Search Input (6 cols) -->
            <div class="sm:col-span-5 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by student name, email, or course..."
                       class="w-full rounded-xl border border-slate-800 bg-slate-900/90 pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @if($search !== '')
                    <a href="{{ route('admin.enrollments.index', request()->except(['search', 'page'])) }}"
                       class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white"
                       title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>

            <!-- Course Filter Select (4 cols) -->
            <div class="sm:col-span-4">
                <select name="course"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->slug }}" {{ $selectedCourseSlug === $c->slug ? 'selected' : '' }}>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Select (3 cols) -->
            <div class="sm:col-span-3">
                <select name="sort"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest Enrolled</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest Enrolled</option>
                    <option value="student_asc" {{ $sort === 'student_asc' ? 'selected' : '' }}>Student (A &rarr; Z)</option>
                    <option value="student_desc" {{ $sort === 'student_desc' ? 'selected' : '' }}>Student (Z &rarr; A)</option>
                    <option value="course_asc" {{ $sort === 'course_asc' ? 'selected' : '' }}>Course (A &rarr; Z)</option>
                    <option value="course_desc" {{ $sort === 'course_desc' ? 'selected' : '' }}>Course (Z &rarr; A)</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Enrollments Table Container -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm overflow-hidden">
        @if($enrollments->isEmpty())
            <div class="py-16 text-center px-4">
                <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">No enrollments found</h3>
                <p class="mt-1 text-xs text-slate-400">
                    @if($search !== '' || $selectedCourseSlug !== '' || $status !== 'all')
                        No enrollment records matched your active filter criteria. Try resetting your search.
                    @else
                        No students are currently enrolled in any course.
                    @endif
                </p>
                @if($search !== '' || $selectedCourseSlug !== '' || $status !== 'all')
                    <div class="mt-4">
                        <a href="{{ route('admin.enrollments.index') }}"
                           class="inline-flex items-center rounded-lg bg-amber-500/10 border border-amber-500/30 px-3 py-1.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/20 transition">
                            Reset Filters
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                            <th class="py-3.5 pl-6 pr-4">Student</th>
                            <th class="py-3.5 px-4">Course</th>
                            <th class="py-3.5 px-4">Enrolled At</th>
                            <th class="py-3.5 px-4 w-44">Learning Progress</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Certificate</th>
                            <th class="py-3.5 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($enrollments as $enrollment)
                            @php
                                $prog = $enrollment->course_progress;
                                $percentage = $prog['percentage'] ?? 0;
                                $isCompleted = ($enrollment->status?->value ?? $enrollment->status) === 'completed' || ($prog['is_completed'] ?? false);
                                $hasCert = $enrollment->certificate !== null;
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Student Column -->
                                <td class="py-4 pl-6 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-amber-500/20 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/30">
                                            {{ strtoupper(substr($enrollment->user?->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            @if($enrollment->user)
                                                <a href="{{ route('admin.students.show', $enrollment->user) }}"
                                                   class="font-bold text-white hover:text-amber-400 transition block truncate">
                                                    {{ $enrollment->user->name }}
                                                </a>
                                                <p class="text-[11px] text-slate-400 truncate">
                                                    {{ $enrollment->user->email }}
                                                </p>
                                            @else
                                                <span class="text-slate-500">Deleted User</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Course Column -->
                                <td class="py-4 px-4">
                                    <div class="min-w-0 max-w-[200px]">
                                        <span class="font-bold text-slate-200 block truncate" title="{{ $enrollment->course?->title }}">
                                            {{ $enrollment->course?->title ?? 'Deleted Course' }}
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            {{ $enrollment->course?->category?->name ?? 'General' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Enrolled Date -->
                                <td class="py-4 px-4 text-slate-300">
                                    <div>{{ $enrollment->enrolled_at?->format('M d, Y') ?? $enrollment->created_at?->format('M d, Y') }}</div>
                                    <div class="text-[10px] text-slate-500">{{ ($enrollment->enrolled_at ?? $enrollment->created_at)?->diffForHumans() }}</div>
                                </td>

                                <!-- Progress Bar -->
                                <td class="py-4 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-bold text-white">{{ $percentage }}%</span>
                                            <span class="text-slate-400">{{ $prog['completed'] ?? 0 }}/{{ $prog['total'] ?? 0 }} lessons</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                                            <div class="h-full rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                                 style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 text-center">
                                    @php
                                        $enrStatus = $enrollment->status?->value ?? $enrollment->status;
                                        $badgeStyle = match($enrStatus) {
                                            'completed' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                            'cancelled' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                                            default => 'bg-blue-500/10 text-blue-400 border border-blue-500/30',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $badgeStyle }}">
                                        {{ ucfirst($enrStatus) }}
                                    </span>
                                </td>

                                <!-- Certificate Badge -->
                                <td class="py-4 px-4 text-center">
                                    @if($hasCert)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30" title="{{ $enrollment->certificate->certificate_number }}">
                                            Issued
                                        </span>
                                    @else
                                        <span class="text-slate-600 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pr-6 text-right">
                                    <a href="{{ route('admin.enrollments.show', $enrollment) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-amber-500/50 hover:bg-slate-700 hover:text-white transition">
                                        <span>Inspect</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($enrollments->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    {{ $enrollments->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection