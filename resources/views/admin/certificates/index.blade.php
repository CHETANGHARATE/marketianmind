@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Credential Management
                </span>
                <span class="text-xs text-slate-500">Course Completion Recognition</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Issued Certificates
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Review student course completion certificates, historical credential snapshots, and issuance audit logs.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($metrics['total_certificates']) }}</span> Total Certificates
            </span>
        </div>
    </div>

    <!-- KPI Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Certificates Card -->
        <div class="rounded-2xl border border-amber-500/20 bg-amber-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Issued Certificates</span>
                <div class="rounded-lg bg-amber-500/20 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['total_certificates']) }}</p>
                <p class="mt-1 text-[11px] text-amber-400/80">Total credentials conferred</p>
            </div>
        </div>

        <!-- Unique Certified Students Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Certified Learners</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['unique_students']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Students with at least 1 certificate</p>
            </div>
        </div>

        <!-- Certified Courses Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Certified Courses</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.832 5.477 15.426 5 17.5 5s3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18s-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['certified_courses']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Curricula with graduates</p>
            </div>
        </div>
    </div>

    <!-- Search & Filters Form -->
    <div class="space-y-4">
        <form method="GET" action="{{ route('admin.certificates.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <!-- Search Input (5 cols) -->
            <div class="sm:col-span-5 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by Certificate #, Student, Email, or Course..."
                       class="w-full rounded-xl border border-slate-800 bg-slate-900/90 pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @if($search !== '')
                    <a href="{{ route('admin.certificates.index', request()->except(['search', 'page'])) }}"
                       class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white"
                       title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>

            <!-- Course Filter Select (3 cols) -->
            <div class="sm:col-span-3">
                <select name="course"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->slug }}" {{ $currentCourse === $c->slug ? 'selected' : '' }}>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter (2 cols) -->
            <div class="sm:col-span-2">
                <select name="date"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Dates</option>
                    <option value="today" {{ $currentDate === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ $currentDate === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $currentDate === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>
            </div>

            <!-- Sort Select (2 cols) -->
            <div class="sm:col-span-2">
                <select name="sort"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>Newest Issued</option>
                    <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Oldest Issued</option>
                    <option value="student_asc" {{ $currentSort === 'student_asc' ? 'selected' : '' }}>Student (A-Z)</option>
                    <option value="student_desc" {{ $currentSort === 'student_desc' ? 'selected' : '' }}>Student (Z-A)</option>
                    <option value="course_asc" {{ $currentSort === 'course_asc' ? 'selected' : '' }}>Course (A-Z)</option>
                    <option value="course_desc" {{ $currentSort === 'course_desc' ? 'selected' : '' }}>Course (Z-A)</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Certificates Table Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        @if($certificates->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-6 py-4">Certificate ID</th>
                            <th class="px-6 py-4">Certified Learner</th>
                            <th class="px-6 py-4">Completed Course</th>
                            <th class="px-6 py-4">Issued Date</th>
                            <th class="px-6 py-4">Completion Date</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($certificates as $cert)
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Certificate Number -->
                                <td class="px-6 py-4">
                                    <div class="font-mono font-bold text-amber-400 text-sm">
                                        {{ $cert->certificate_number }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        Internal ID: #{{ $cert->id }}
                                    </div>
                                </td>

                                <!-- Certified Student -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-amber-400 border border-slate-700 shrink-0">
                                            {{ strtoupper(substr($cert->student_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.students.show', $cert->user) }}" class="font-semibold text-white hover:text-amber-400 transition truncate block">
                                                {{ $cert->student_name }}
                                            </a>
                                            <span class="text-[11px] text-slate-400 truncate block">
                                                {{ $cert->user->email }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Course -->
                                <td class="px-6 py-4 max-w-[240px]">
                                    <a href="{{ route('admin.courses.edit', $cert->course) }}" class="font-medium text-slate-200 hover:text-amber-400 transition block truncate" title="{{ $cert->course_title }}">
                                        {{ $cert->course_title }}
                                    </a>
                                    @if($cert->course->category)
                                        <span class="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 text-[10px] font-semibold text-slate-400 mt-1">
                                            {{ $cert->course->category->name }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Issued Date -->
                                <td class="px-6 py-4">
                                    <div class="text-white font-medium">
                                        {{ $cert->issued_at ? $cert->issued_at->format('M d, Y') : 'N/A' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $cert->issued_at ? $cert->issued_at->diffForHumans() : '' }}
                                    </div>
                                </td>

                                <!-- Completion Date -->
                                <td class="px-6 py-4 text-slate-300">
                                    {{ $cert->course_completion_date ? $cert->course_completion_date->format('M d, Y') : 'N/A' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.certificates.show', $cert) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                        Inspect &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($certificates->hasPages())
                <div class="border-t border-slate-800 p-4">
                    {{ $certificates->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-800/80 flex items-center justify-center text-slate-500 mb-4 border border-slate-700/50">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">No Certificates Found</h3>
                <p class="mt-1 text-xs text-slate-400 max-w-sm mx-auto">
                    No completion certificates match your active filter and search criteria.
                </p>
                <div class="mt-5">
                    <a href="{{ route('admin.certificates.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition border border-slate-700">
                        Reset All Filters
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection