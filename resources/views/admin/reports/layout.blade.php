@extends('layouts.admin', ['title' => ($title ?? 'Business Reports') . ' - Admin'])

@section('content')
<div class="space-y-6">
    <!-- Header & Date Range Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs">
        <div class="sm:flex sm:items-center sm:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Authoritative Analytics Engine
                </span>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Business Reports &amp; Analytics</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Showing data for <span class="font-semibold text-slate-800">{{ $dateFilter['label'] }}</span>
                </p>
            </div>

            <!-- Date Range Controls -->
            <form method="GET" action="{{ url()->current() }}" class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
                <select
                    name="range"
                    onchange="if(this.value !== 'custom') this.form.submit(); else document.getElementById('custom-dates').classList.remove('hidden');"
                    class="rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-amber-500 px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs focus:outline-none focus:ring-2"
                >
                    <option value="30d" {{ ($dateFilter['range'] ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="7d" {{ ($dateFilter['range'] ?? '') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="today" {{ ($dateFilter['range'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_month" {{ ($dateFilter['range'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ ($dateFilter['range'] ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ ($dateFilter['range'] ?? '') === 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="all" {{ ($dateFilter['range'] ?? '') === 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="custom" {{ ($dateFilter['range'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Range...</option>
                </select>

                <div id="custom-dates" class="{{ ($dateFilter['range'] ?? '') === 'custom' ? '' : 'hidden' }} flex items-center gap-2">
                    <input
                        type="date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        class="rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 focus:border-amber-500"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        type="date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        class="rounded-lg border border-slate-300 text-xs px-2.5 py-1.5 focus:border-amber-500"
                    />
                    <button
                        type="submit"
                        class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800"
                    >
                        Apply
                    </button>
                </div>
            </form>
        </div>

        <!-- Navigation Tabs -->
        <div class="mt-6 border-t border-slate-100 pt-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.index', request()->query()) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.reports.index') ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Executive Overview
            </a>
            <a href="{{ route('admin.reports.sales', request()->query()) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.reports.sales') ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Sales &amp; Revenue
            </a>
            <a href="{{ route('admin.reports.courses', request()->query()) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.reports.courses') ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Course Performance
            </a>
            <a href="{{ route('admin.reports.enrollments', request()->query()) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.reports.enrollments') ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Enrollments &amp; Completions
            </a>
            <a href="{{ route('admin.reports.coupons', request()->query()) }}"
               class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.reports.coupons') ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Coupons &amp; Discounts
            </a>
        </div>
    </div>

    <!-- Tab Content -->
    @yield('report_content')
</div>
@endsection