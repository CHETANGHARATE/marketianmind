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
                <span class="text-xs text-slate-500 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live Data &bull; Asia/Kolkata (IST)
                </span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Admin Dashboard
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                A concise overview of the Marketian Mind platform, course performance, enrollments, and revenue.
            </p>
        </div>

        <!-- Quick Action Shortcuts -->
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
            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Orders
            </a>
            <a href="{{ route('admin.reports.sales') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Reports
            </a>
            <a href="{{ route('admin.reports.export.sales', ['date_range' => $dateFilter['range'] ?? '30d']) }}"
               class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Platform KPI Metrics Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
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

        <!-- Metric: Course Renewals -->
        <a href="{{ route('admin.reports.renewals') }}" class="group block rounded-2xl border border-slate-800 bg-slate-900/90 p-5 shadow-sm hover:border-amber-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400 group-hover:text-amber-300 transition">Renewals</span>
                <span class="rounded-lg bg-amber-500/10 p-2 text-amber-400 group-hover:bg-amber-500/20 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black tracking-tight text-white">
                    {{ $metrics['renewals_this_month'] ?? 0 }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span class="text-emerald-400 font-medium">{{ $metrics['formatted_renewal_revenue'] ?? '₹0.00' }}</span>
                    <span class="text-amber-400">{{ $metrics['expiring_soon'] ?? 0 }} expiring</span>
                </div>
            </div>
        </a>

        <!-- Metric: Orders Status -->
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

    <!-- Interactive Date Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.dashboard') }}" id="dateFilterForm" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Preset Range Buttons -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                @php
                    $activeRange = $dateFilter['range'] ?? '30d';
                    $presets = [
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        '7d' => '7 Days',
                        '30d' => '30 Days',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'all' => 'All Time',
                    ];
                @endphp

                @foreach($presets as $key => $label)
                    <a href="{{ route('admin.dashboard', ['date_range' => $key]) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $activeRange === $key ? 'bg-amber-500 text-slate-950 font-bold shadow' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <!-- Custom Date Range Picker & Active Badge -->
            <div class="flex flex-wrap items-center gap-2.5">
                <input type="hidden" name="date_range" value="custom">
                <div class="flex items-center gap-1 text-xs text-slate-400">
                    <label for="start_date" class="sr-only">Start Date</label>
                    <input type="date"
                           id="start_date"
                           name="start_date"
                           value="{{ request('start_date', $dateFilter['start_date'] ?? '') }}"
                           class="rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1 text-xs text-slate-200 focus:border-amber-500 focus:outline-none">
                    <span>to</span>
                    <label for="end_date" class="sr-only">End Date</label>
                    <input type="date"
                           id="end_date"
                           name="end_date"
                           value="{{ request('end_date', $dateFilter['end_date'] ?? '') }}"
                           class="rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1 text-xs text-slate-200 focus:border-amber-500 focus:outline-none">
                    <button type="submit"
                            class="rounded-lg bg-slate-800 border border-slate-700 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-700 transition">
                        Apply
                    </button>
                </div>

                <!-- Current Active Period Badge -->
                <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-800/90 px-2.5 py-1 text-xs font-semibold text-slate-300 border border-slate-700">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ $dateFilter['label'] ?? 'Selected Period' }}
                </span>
            </div>
        </form>
    </div>

    <!-- Operational Health & Safety Alert Card -->
    @php
        $unfulfilledCount = $operationalHealth['unfulfilled_orders_count'] ?? 0;
        $failedCount = $operationalHealth['failed_payments_count'] ?? 0;
        $pendingCount = $operationalHealth['pending_orders_count'] ?? 0;
        $hasHealthAlerts = $unfulfilledCount > 0 || $failedCount > 0;
    @endphp

    <div class="rounded-2xl border {{ $unfulfilledCount > 0 ? 'border-rose-500/50 bg-rose-950/20' : ($hasHealthAlerts ? 'border-amber-500/40 bg-amber-950/20' : 'border-emerald-500/30 bg-emerald-950/10') }} p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="p-2 rounded-xl {{ $unfulfilledCount > 0 ? 'bg-rose-500/20 text-rose-400' : ($hasHealthAlerts ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400') }}">
                    @if($unfulfilledCount > 0)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    @elseif($hasHealthAlerts)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </span>
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        Operational &amp; Fulfillment Health
                        @if($unfulfilledCount === 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                Healthy
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                Action Required
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($unfulfilledCount > 0)
                            <span class="text-rose-400 font-semibold">{{ $unfulfilledCount }} paid order(s) require access period fulfillment!</span>
                        @else
                            All paid student orders are 100% fulfilled with valid access periods.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Mini Status Badges -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300">
                    <span class="font-semibold text-white">{{ $pendingCount }}</span> Pending Checkouts
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300">
                    <span class="font-semibold {{ $failedCount > 0 ? 'text-rose-400' : 'text-slate-400' }}">{{ $failedCount }}</span> Recent Payment Failures
                </span>
                @if($unfulfilledCount > 0)
                    <a href="{{ route('admin.orders.index', ['status' => 'paid']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-rose-500 text-slate-950 font-bold hover:bg-rose-400 transition">
                        Inspect Unfulfilled &rarr;
                    </a>
                @endif
            </div>
        </div>

        <!-- Failed Payment Log (if any) -->
        @if(!empty($operationalHealth['recent_failed_payments']) && count($operationalHealth['recent_failed_payments']) > 0)
            <div class="mt-4 pt-4 border-t border-slate-800/80">
                <h4 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Recent Failed Payment Attempts (Requires Diagnostic Review)</h4>
                <div class="divide-y divide-slate-800/60 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-[10px] text-slate-500 uppercase">
                                <th class="py-1.5 pr-3">Payment ID / Order</th>
                                <th class="py-1.5 px-3">Student</th>
                                <th class="py-1.5 px-3">Course</th>
                                <th class="py-1.5 px-3">Amount</th>
                                <th class="py-1.5 px-3">Reason / Error</th>
                                <th class="py-1.5 pl-3 text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-slate-300">
                            @foreach($operationalHealth['recent_failed_payments'] as $fp)
                                <tr class="hover:bg-slate-800/20">
                                    <td class="py-2 pr-3 font-mono text-white text-[11px]">
                                        <a href="{{ route('admin.orders.show', $fp['order_id']) }}" class="text-amber-400 hover:underline">
                                            {{ $fp['order_number'] }}
                                        </a>
                                        <span class="text-slate-500 block text-[10px]">{{ $fp['payment_id'] ?? 'No Gateway ID' }}</span>
                                    </td>
                                    <td class="py-2 px-3 font-medium text-slate-200">
                                        {{ $fp['user_name'] }}
                                        <span class="text-slate-500 block text-[10px]">{{ $fp['user_email'] }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-slate-300 truncate max-w-[150px]">{{ $fp['course_title'] }}</td>
                                    <td class="py-2 px-3 font-semibold text-white">{{ $fp['formatted_amount'] }}</td>
                                    <td class="py-2 px-3 text-rose-400 max-w-[200px] truncate" title="{{ $fp['error_description'] }}">
                                        {{ $fp['error_description'] }}
                                    </td>
                                    <td class="py-2 pl-3 text-right text-slate-400 text-[11px]">{{ $fp['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Period Verified Financial Intelligence Summary Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-400">Period Net Revenue</span>
            <div class="mt-1 text-xl font-bold text-white">{{ $revenueMetrics['formatted_net_revenue'] ?? '₹0.00' }}</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">{{ $revenueMetrics['paid_orders_count'] ?? 0 }} paid checkouts</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Gross Sales</span>
            <div class="mt-1 text-xl font-bold text-white">{{ $revenueMetrics['formatted_gross_sales'] ?? '₹0.00' }}</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">Pre-discount volume</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Average Order Value</span>
            <div class="mt-1 text-xl font-bold text-amber-400">{{ $revenueMetrics['formatted_aov'] ?? '₹0.00' }}</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">Per verified purchase</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Paying Customers</span>
            <div class="mt-1 text-xl font-bold text-white">{{ number_format($revenueMetrics['unique_paying_customers'] ?? 0) }}</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">{{ $revenueMetrics['first_time_buyers_count'] ?? 0 }} new &bull; {{ $revenueMetrics['returning_buyers_count'] ?? 0 }} repeat</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-400">Period Renewals</span>
            <div class="mt-1 text-xl font-bold text-emerald-400">{{ $revenueMetrics['renewals_count'] ?? 0 }}</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">{{ $revenueMetrics['formatted_renewal_revenue'] ?? '₹0.00' }} renewed</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-400">Repeat Purchase Rate</span>
            <div class="mt-1 text-xl font-bold text-indigo-400">{{ $customerMetrics['repeat_purchase_rate'] ?? 0 }}%</div>
            <span class="text-[10px] text-slate-500 block mt-0.5">Customer retention</span>
        </div>
    </div>

    <!-- Revenue & Daily Sales Trend Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Revenue &amp; Sales Trend</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Daily breakdown of verified revenue, volume, and customer acquisition mix for {{ $dateFilter['label'] ?? 'the period' }}.
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-amber-500"></span>
                    <span class="text-slate-300">Net Revenue (₹)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-emerald-400"></span>
                    <span class="text-slate-300">First-Time Revenue</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-indigo-400"></span>
                    <span class="text-slate-300">Returning Revenue</span>
                </div>
            </div>
        </div>

        @if(empty($revenueTrend['days']) || count($revenueTrend['days']) === 0)
            <div class="py-12 text-center text-xs text-slate-500">
                No revenue recorded in this date range.
            </div>
        @else
            <!-- CSS Bar Chart (Responsive) -->
            <div class="mt-6">
                <div class="flex items-end gap-1.5 sm:gap-2 h-48 sm:h-56 pt-6 pb-2 border-b border-slate-800 overflow-x-auto">
                    @foreach($revenueTrend['days'] as $day)
                        <div class="flex-1 min-w-[28px] max-w-[48px] flex flex-col items-center h-full justify-end group relative">
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none">
                                <div class="rounded-lg bg-slate-950 border border-slate-700 px-2.5 py-1.5 text-[11px] shadow-xl whitespace-nowrap text-left">
                                    <div class="font-bold text-white">{{ $day['date'] }}</div>
                                    <div class="text-amber-400 font-semibold mt-0.5">Net: {{ $day['formatted_revenue'] }}</div>
                                    <div class="text-slate-400 text-[10px] mt-0.5">
                                        Orders: {{ $day['orders_count'] }} &bull; 
                                        New: ₹{{ number_format($day['first_time_revenue'], 2) }} &bull; 
                                        Repeat: ₹{{ number_format($day['returning_revenue'], 2) }}
                                    </div>
                                </div>
                                <div class="w-2 h-2 bg-slate-950 border-r border-b border-slate-700 transform rotate-45 -mt-1"></div>
                            </div>

                            <!-- Bar -->
                            <div class="w-full rounded-t-md transition-all duration-300 flex flex-col justify-end overflow-hidden {{ $day['revenue'] > 0 ? 'bg-slate-800' : 'bg-slate-800/20' }}"
                                 style="height: {{ max(4, $day['bar_height_percentage']) }}%;">
                                @if($day['revenue'] > 0)
                                    @php
                                        $newShare = $day['revenue'] > 0 ? ($day['first_time_revenue'] / $day['revenue']) * 100 : 0;
                                        $repShare = 100 - $newShare;
                                    @endphp
                                    <div class="w-full bg-emerald-500/80" style="height: {{ $newShare }}%;"></div>
                                    <div class="w-full bg-amber-500" style="height: {{ $repShare }}%;"></div>
                                @endif
                            </div>

                            <!-- Label -->
                            <span class="mt-2 text-[10px] text-slate-400 truncate w-full text-center">
                                {{ $day['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>Period Total: <strong class="text-white">{{ $revenueTrend['total_formatted_revenue'] ?? $revenueMetrics['formatted_net_revenue'] }}</strong></span>
                    <span>Peak Day: <strong class="text-amber-400">₹{{ number_format($revenueTrend['max_daily_revenue'] ?? 0, 2) }}</strong></span>
                </div>
            </div>
        @endif
    </div>

    <!-- Course-Level Performance Table (Authoritative Breakdown) -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Course Performance Overview</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Strictly paid orders, net collected revenue, unique paying buyers, access validity distribution, and completion rates.
                </p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                Manage Courses ({{ count($coursePerformance) }}) &rarr;
            </a>
        </div>

        @if($coursesOverview->isEmpty() || empty($coursePerformance) || count($coursePerformance) === 0)
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
                            <th class="py-3 px-3">Price / Tier</th>
                            <th class="py-3 px-3 text-center">Paid Orders</th>
                            <th class="py-3 px-3 text-right">Net Revenue</th>
                            <th class="py-3 px-3 text-center">Unique Buyers</th>
                            <th class="py-3 px-3 text-center">Active Access</th>
                            <th class="py-3 px-3 text-center">Expired Access</th>
                            <th class="py-3 pl-3 text-right">Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($coursePerformance as $course)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pr-4">
                                    <a href="{{ route('admin.courses.edit', $course['id']) }}" class="font-bold text-white hover:text-amber-400 transition block truncate max-w-[220px]">
                                        {{ $course['title'] }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] text-slate-400">{{ $course['category'] }}</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded font-medium {{ $course['status'] === 'published' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                                            {{ ucfirst($course['status']) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-200">
                                    {{ $course['formatted_price'] }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-white">
                                    {{ $course['period_paid_orders_count'] }}
                                </td>
                                <td class="py-3.5 px-3 text-right font-mono font-bold text-amber-400">
                                    {{ $course['formatted_period_revenue'] }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-medium text-slate-300">
                                    {{ $course['unique_buyers_count'] }}
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        {{ $course['active_access_count'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $course['expired_access_count'] > 0 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $course['expired_access_count'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 pl-3 text-right font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <span class="{{ $course['completion_rate'] >= 50 ? 'text-emerald-400 font-bold' : ($course['completion_rate'] > 0 ? 'text-amber-400' : 'text-slate-400') }}">
                                            {{ $course['completion_rate'] }}%
                                        </span>
                                        <span class="text-[10px] text-slate-500">({{ $course['completed_enrollments_count'] }}/{{ $course['total_enrollments_count'] }})</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Customer Acquisition & Mini CRM Funnel -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <!-- Customer Acquisition & Lifecycle Intelligence (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Customer Acquisition</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Cohort growth and repurchase loyalty</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $dateFilter['label'] }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="rounded-xl bg-slate-950/60 p-4 border border-slate-800">
                        <span class="text-[11px] text-slate-400 font-semibold uppercase">New Students</span>
                        <div class="text-2xl font-bold text-white mt-1">{{ number_format($customerMetrics['period_registered_students'] ?? 0) }}</div>
                        <span class="text-[10px] text-slate-500 block mt-1">Platform signups in period</span>
                    </div>
                    <div class="rounded-xl bg-slate-950/60 p-4 border border-slate-800">
                        <span class="text-[11px] text-amber-400 font-semibold uppercase">Repeat Purchase Rate</span>
                        <div class="text-2xl font-bold text-amber-400 mt-1">{{ $customerMetrics['repeat_purchase_rate'] ?? 0 }}%</div>
                        <span class="text-[10px] text-slate-500 block mt-1">Multi-course / renewal buyers</span>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between text-xs py-2 border-b border-slate-800/60">
                        <span class="text-slate-400">Total Registered Learners (Lifetime)</span>
                        <span class="font-bold text-white">{{ number_format($customerMetrics['total_registered_students'] ?? $metrics['total_students']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-2 border-b border-slate-800/60">
                        <span class="text-slate-400">Platform-Wide Active Valid Access</span>
                        <span class="font-bold text-emerald-400">{{ number_format($customerMetrics['active_access_count'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-2 border-b border-slate-800/60">
                        <span class="text-slate-400">Platform-Wide Expired Access</span>
                        <span class="font-bold text-slate-400">{{ number_format($customerMetrics['expired_access_count'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-2">
                        <span class="text-slate-400">Access Expiring in &le; 30 Days</span>
                        <span class="font-bold text-amber-400">{{ number_format($customerMetrics['expiring_soon_count'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800/60">
                <a href="{{ route('admin.reports.renewals') }}" class="block text-center rounded-xl bg-slate-800 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                    View Access Expiry &amp; Renewal Retention &rarr;
                </a>
            </div>
        </div>

        <!-- Lead-to-Purchase Funnel & Attribution (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                                Funnel &amp; Attribution
                            </span>
                            <span class="text-xs text-slate-400">Inbound Lead Conversion</span>
                        </div>
                        <h2 class="text-base font-bold text-white tracking-tight mt-1">
                            Inbound Leads &amp; Mini CRM Overview
                        </h2>
                    </div>
                    <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        Full CRM Pipeline &rarr;
                    </a>
                </div>

                <!-- Funnel 4-stage horizontal cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-4 border-b border-slate-800/60">
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase">Total Leads</span>
                        <div class="text-lg font-bold text-white mt-1">{{ number_format($leadFunnel['period_leads_count'] ?? $metrics['total_leads'] ?? 0) }}</div>
                        <span class="text-[10px] text-slate-500 block">Period Leads</span>
                    </div>
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800">
                        <span class="text-[10px] text-amber-400 font-semibold uppercase">New / Uncontacted</span>
                        <div class="text-lg font-bold text-amber-400 mt-1">{{ number_format($metrics['new_leads'] ?? 0) }}</div>
                        <span class="text-[10px] text-slate-500 block">Pending Outreach</span>
                    </div>
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800">
                        <span class="text-[10px] text-emerald-400 font-semibold uppercase">Converted</span>
                        <div class="text-lg font-bold text-emerald-400 mt-1">{{ number_format($leadFunnel['converted_leads_count'] ?? 0) }}</div>
                        <span class="text-[10px] text-slate-500 block">Paid Students</span>
                    </div>
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800">
                        <span class="text-[10px] text-amber-400 font-semibold uppercase">Conversion Rate</span>
                        <div class="text-lg font-bold text-amber-400 mt-1">{{ $leadFunnel['conversion_rate'] ?? 0 }}%</div>
                        <span class="text-[10px] text-slate-500 block">Lead &rarr; Purchase</span>
                    </div>
                </div>

                <!-- Attribution Source Distribution -->
                <div class="mt-4">
                    <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Top Lead Acquisition Channels</h3>
                    @if(empty($leadFunnel['top_sources']) || count($leadFunnel['top_sources']) === 0)
                        <div class="py-4 text-center text-xs text-slate-500">
                            No lead attribution data recorded for this period.
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach($leadFunnel['top_sources'] as $source)
                                <div class="rounded-lg bg-slate-800/40 p-2.5 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold block truncate">{{ $source['source'] }}</span>
                                    <div class="text-sm font-bold text-white mt-0.5">{{ $source['count'] }} leads</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800/60 flex items-center justify-between text-xs">
                <span class="text-slate-400">CRM Follow-ups today / overdue:</span>
                <span class="font-bold text-rose-400">{{ $metrics['due_follow_ups'] ?? 0 }} due &bull; {{ $metrics['overdue_follow_ups'] ?? 0 }} overdue</span>
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

    <!-- Lower Section: Recent Enrollments & Recent Leads -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <!-- Recent Enrollments (6 cols) -->
        <div class="lg:col-span-6 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
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

        <!-- Recent Inbound Leads (6 cols) -->
        <div class="lg:col-span-6 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Recent Inbound Leads</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Prospective students inquiring about courses</p>
                </div>
                <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                    View All Leads &rarr;
                </a>
            </div>

            @if($recentLeads->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-300">No leads recorded yet</p>
                    <p class="text-xs text-slate-500 mt-1">Website inquiries will appear here automatically.</p>
                </div>
            @else
                <div class="mt-4 divide-y divide-slate-800/60">
                    @foreach($recentLeads as $lead)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-white text-xs truncate">{{ $lead->name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ $lead->email }}</div>
                                <div class="text-[10px] text-indigo-400 truncate mt-0.5">
                                    {{ $lead->course?->title ?? ($lead->bundle?->title ?? 'General Inquiry') }}
                                </div>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border {{ $lead->status->badgeClasses() }}">
                                    {{ $lead->status->label() }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ $lead->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
