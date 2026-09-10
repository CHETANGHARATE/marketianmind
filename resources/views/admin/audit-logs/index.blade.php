@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Governance & Security
                </span>
                <span class="text-xs text-slate-500">Administrative Activity & Immutable Audit Trail</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Audit Logs
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Track and inspect all administrative state changes, content publications, and resource modifications across the platform.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($auditLogs->total()) }}</span> Total Events
            </span>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Audit Records -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lifetime Logs</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['total_logs']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Total historical actions captured</p>
            </div>
        </div>

        <!-- Today's Events -->
        <div class="rounded-2xl border border-amber-500/20 bg-amber-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Today's Activity</span>
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['today_logs']) }}</p>
                <p class="mt-1 text-[11px] text-amber-400/80">Recorded since 00:00 today</p>
            </div>
        </div>

        <!-- Past 7 Days Activity -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-sky-400">Past 7 Days</span>
                <div class="rounded-lg bg-sky-500/10 p-2 text-sky-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['past_7_days_logs']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Recent administrative operations</p>
            </div>
        </div>

        <!-- Authorized Administrators -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Active Admins</span>
                <div class="rounded-lg bg-indigo-500/10 p-2 text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['total_admins']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Privileged accounts tracked</p>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
        <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Search Logs
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
                           value="{{ $currentSearch }}"
                           placeholder="Description, resource, or admin name..."
                           class="w-full rounded-xl border border-slate-800 bg-slate-950 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <!-- Action Filter -->
            <div>
                <label for="action" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Action Type
                </label>
                <select name="action"
                        id="action"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Actions</option>
                    @foreach($allowedActions as $action)
                        <option value="{{ $action }}" {{ strtolower($currentAction ?? '') === strtolower($action) ? 'selected' : '' }}>
                            {{ ucfirst($action) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Resource Type Filter -->
            <div>
                <label for="resource" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Resource
                </label>
                <select name="resource"
                        id="resource"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Resources</option>
                    @foreach($allowedResources as $key => $label)
                        <option value="{{ $key }}" {{ $currentResource === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range Filter -->
            <div>
                <label for="date_range" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Date Period
                </label>
                <select name="date_range"
                        id="date_range"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Time</option>
                    <option value="today" {{ $currentDateRange === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="7d" {{ $currentDateRange === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30d" {{ $currentDateRange === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                </select>
            </div>

            <!-- Sort -->
            <div>
                <label for="sort" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Order
                </label>
                <select name="sort"
                        id="sort"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <!-- Admin Actor Filter -->
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="admin_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Performed By Admin
                </label>
                <select name="admin_id"
                        id="admin_id"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">Any Administrator</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ (string)$currentAdminId === (string)$admin->id ? 'selected' : '' }}>
                            {{ $admin->name }} ({{ $admin->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 lg:col-span-3 flex items-end justify-end gap-3">
                @if(request()->hasAny(['search', 'action', 'resource', 'date_range', 'admin_id', 'sort']))
                    <a href="{{ route('admin.audit_logs.index') }}"
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

    <!-- Audit Logs Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="border-b border-slate-800 bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th scope="col" class="py-3.5 pl-6 pr-3">Date / Time</th>
                        <th scope="col" class="px-3 py-3.5">Admin / Actor</th>
                        <th scope="col" class="px-3 py-3.5">Action</th>
                        <th scope="col" class="px-3 py-3.5">Resource</th>
                        <th scope="col" class="px-3 py-3.5">Description</th>
                        <th scope="col" class="py-3.5 pl-3 pr-6 text-right">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- Timestamp -->
                            <td class="py-4 pl-6 pr-3 whitespace-nowrap">
                                <span class="font-bold text-white block text-xs">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                </span>
                                <span class="text-[11px] text-slate-500">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <!-- Actor -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-800 text-amber-400 font-bold text-xs border border-slate-700">
                                        {{ substr($log->user->name ?? $log->admin_name ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-white text-xs block truncate">
                                            {{ $log->user->name ?? $log->admin_name ?? 'Unknown Admin' }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 block truncate">
                                            {{ $log->user->email ?? 'Account deleted' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Action Badge -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $log->action_badge_classes }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>

                            <!-- Resource Type & Label -->
                            <td class="px-3 py-4">
                                <span class="inline-flex items-center rounded-md bg-slate-800/80 px-2 py-0.5 text-[11px] font-medium text-slate-300 border border-slate-700">
                                    {{ $log->resource_type_label }}
                                </span>
                                @if($log->resource_label)
                                    <span class="block text-xs text-white font-semibold mt-0.5 truncate max-w-xs">
                                        {{ $log->resource_label }}
                                    </span>
                                @elseif($log->auditable_id)
                                    <span class="block text-[11px] text-slate-500 mt-0.5">
                                        ID: #{{ $log->auditable_id }}
                                    </span>
                                @endif
                            </td>

                            <!-- Description -->
                            <td class="px-3 py-4">
                                <p class="text-xs text-slate-300 line-clamp-2 max-w-md">
                                    {{ $log->description }}
                                </p>
                            </td>

                            <!-- Action / Details Link -->
                            <td class="py-4 pl-3 pr-6 text-right whitespace-nowrap">
                                <a href="{{ route('admin.audit_logs.show', $log) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                    <span>Details</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-800/80 text-slate-500 mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white">
                                    @if(request()->hasAny(['search', 'action', 'resource', 'date_range', 'admin_id']))
                                        No audit records match your current search or filters.
                                    @else
                                        No administrative activity has been recorded yet.
                                    @endif
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    All subsequent administrative actions across courses, categories, and curriculum will appear here.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($auditLogs->hasPages())
            <div class="border-t border-slate-800 bg-slate-950/60 px-6 py-4">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection