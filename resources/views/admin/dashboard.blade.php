@extends('layouts.admin')

@section('subcontent')
<div class="space-y-8">
    <!-- Header & Quick Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Platform Management
                </span>
                <span class="text-xs text-slate-500">Live Business Overview</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Admin Dashboard
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                A concise overview of the Marketian Mind platform, course performance, enrollments, and revenue.
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <a href="{{ route('admin.courses.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Course
            </a>
            <a href="{{ route('admin.courses.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Courses
            </a>
            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Categories
            </a>
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Orders
            </a>
            <a href="{{ route('admin.students.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Students
            </a>
        </div>
    </div>

    <!-- KPI Metrics Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <!-- Metric: Total Revenue -->
        <div class="rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-500/10 via-slate-900 to-slate-950 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Total Revenue</span>
                <span class="rounded-lg bg-amber-500/20 p-2 text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ $metrics['formatted_revenue'] }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ $metrics['paid_orders'] }} paid orders</span>
                    <span class="text-emerald-400 font-medium">100% Captured</span>
                </div>
            </div>
        </div>

        <!-- Metric: Total Students -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Students</span>
                <span class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ number_format($metrics['total_students']) }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Registered learners</span>
                    <span class="text-amber-400 font-medium">+{{ $metrics['new_students_30d'] }} last 30d</span>
                </div>
            </div>
        </div>

        <!-- Metric: Total Courses -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Courses</span>
                <span class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ number_format($metrics['total_courses']) }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span class="text-emerald-400 font-medium">{{ $metrics['published_courses'] }} published</span>
                    <span class="text-slate-500">{{ $metrics['draft_courses'] }} draft</span>
                </div>
            </div>
        </div>

        <!-- Metric: Total Enrollments -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Enrollments</span>
                <span class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ number_format($metrics['total_enrollments']) }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ $metrics['completed_enrollments'] }} completed</span>
                    <span class="text-amber-400 font-medium">{{ $metrics['completion_rate'] }}% rate</span>
                </div>
            </div>
        </div>

        <!-- Metric: Orders Overview -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Orders Status</span>
                <span class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ number_format($metrics['total_orders']) }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px]">
                    <span class="text-emerald-400 font-medium">{{ $metrics['paid_orders'] }} paid</span>
                    <span class="text-amber-400">{{ $metrics['pending_orders'] }} pend</span>
                    <span class="text-rose-400">{{ $metrics['failed_orders'] }} fail</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Recent Orders & Recent Students (Two Columns) -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <!-- Column 1: Recent Orders (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Recent Orders</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Latest purchase attempts across the platform</p>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        View All Orders &rarr;
                    </a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="py-12 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-300">No orders recorded yet</p>
                        <p class="text-xs text-slate-500 mt-1">Paid and pending course orders will appear here automatically.</p>
                    </div>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                    <th class="py-3 pr-4">Order #</th>
                                    <th class="py-3 px-4">Student</th>
                                    <th class="py-3 px-4">Course</th>
                                    <th class="py-3 px-4">Amount</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 pl-4 text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach($recentOrders as $order)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="py-3.5 pr-4 font-mono font-bold text-white">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="text-amber-400 hover:underline">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-slate-200">
                                            {{ $order->user?->name ?? 'Deleted User' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-300 max-w-[160px] truncate" title="{{ $order->course?->title }}">
                                            {{ $order->course?->title ?? 'Deleted Course' }}
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-white">
                                            {{ $order->formattedAmount() }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @php
                                                $status = $order->status;
                                                $badgeClass = match($status?->value ?? $status) {
                                                    'paid' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                                    'pending' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                                                    'failed' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                                                    'cancelled' => 'bg-slate-500/10 text-slate-400 border border-slate-500/30',
                                                    default => 'bg-slate-500/10 text-slate-300 border border-slate-500/30',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                                {{ ucfirst($status?->value ?? $status) }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 pl-4 text-right text-slate-400">
                                            {{ $order->created_at?->format('M d, Y') ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Column 2: Recent Students (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Recent Students</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Newest registered student learners</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">Role: Student</span>
                </div>

                @if($recentStudents->isEmpty())
                    <div class="py-12 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-300">No students registered yet</p>
                        <p class="text-xs text-slate-500 mt-1">New accounts will be tracked automatically upon signup.</p>
                    </div>
                @else
                    <div class="mt-4 divide-y divide-slate-800/60">
                        @foreach($recentStudents as $student)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/30">
                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-white truncate">{{ $student->name }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $student->email }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-300">
                                        {{ $student->created_at?->diffForHumans() ?? 'Recently' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lower Section: Recent Enrollments & Course Overview -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <!-- Recent Enrollments (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Recent Enrollments</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Learners actively joining courses</p>
                </div>
                <a href="{{ route('admin.enrollments.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                    View All ({{ $metrics['total_enrollments'] }}) &rarr;
                </a>
            </div>

            @if($recentEnrollments->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-300">No enrollments yet</p>
                    <p class="text-xs text-slate-500 mt-1">Learner registrations will appear as students join courses.</p>
                </div>
            @else
                <div class="mt-4 divide-y divide-slate-800/60">
                    @foreach($recentEnrollments as $enrollment)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-white truncate">
                                    {{ $enrollment->user?->name ?? 'Learner' }}
                                </p>
                                <p class="text-[11px] text-amber-400 truncate mt-0.5">
                                    {{ $enrollment->course?->title ?? 'Course' }}
                                </p>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                @php
                                    $isCompleted = ($enrollment->status?->value ?? $enrollment->status) === 'completed';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $isCompleted ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/30' }}">
                                    {{ $isCompleted ? 'Completed' : 'Active' }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ $enrollment->created_at?->format('M d') ?? 'Recently' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Course Performance Overview (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Course Performance Overview</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Summary of platform curriculum engagement</p>
                </div>
                <a href="{{ route('admin.courses.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                    Manage Courses &rarr;
                </a>
            </div>

            @if($coursesOverview->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-300">No courses available</p>
                    <p class="text-xs text-slate-500 mt-1">Create your first course to begin building curriculum.</p>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <th class="py-3 pr-4">Course</th>
                                <th class="py-3 px-4">Price / Tier</th>
                                <th class="py-3 px-4 text-center">Students</th>
                                <th class="py-3 px-4 text-center">Finished</th>
                                <th class="py-3 pl-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($coursesOverview as $course)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3.5 pr-4">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="font-bold text-white hover:text-amber-400 transition block truncate max-w-[200px]">
                                            {{ $course->title }}
                                        </a>
                                        <span class="text-[11px] text-slate-400">
                                            {{ $course->category?->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold">
                                        @if($course->is_free)
                                            <span class="text-emerald-400">Free</span>
                                        @else
                                            <span class="text-white">₹{{ number_format($course->effectivePrice(), 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-white">
                                        {{ $course->enrollments_count }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-300">
                                        {{ $course->completed_enrollments_count }}
                                    </td>
                                    <td class="py-3.5 pl-4 text-right">
                                        @php
                                            $courseStatus = $course->status?->value ?? $course->status;
                                        @endphp
                                        @if($courseStatus === 'published')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                Published
                                            </span>
                                        @elseif($courseStatus === 'draft')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/30">
                                                Draft
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                                {{ ucfirst($courseStatus) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
