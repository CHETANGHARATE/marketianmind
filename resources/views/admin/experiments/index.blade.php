@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Growth &amp; Optimization
                </span>
                <span class="text-xs text-slate-500">Conversion Experiments</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                A/B Testing &amp; Experiments
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Run deterministic, privacy-safe A/B split tests with zero code execution and automatic variant attribution.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.funnel.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Conversion Funnel
            </a>
            <a href="{{ route('admin.experiments.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Experiment
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-xs font-medium text-emerald-400 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-xs font-medium text-rose-400 flex items-center gap-2">
            <svg class="h-4 w-4 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Experiments</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($stats['total']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Configured in system</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Running</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($stats['running']) }}</div>
            <div class="mt-1 text-[11px] text-emerald-500/80">Actively splitting traffic</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Paused</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($stats['paused']) }}</div>
            <div class="mt-1 text-[11px] text-amber-500/80">Serving control variant</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Completed</div>
            <div class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['completed']) }}</div>
            <div class="mt-1 text-[11px] text-sky-500/80">Concluded tests</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Draft</div>
            <div class="mt-2 text-2xl font-bold text-slate-300">{{ number_format($stats['draft']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Pending activation</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.experiments.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1 max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search by experiment name or key..."
                           class="w-full rounded-xl border border-slate-700/60 bg-slate-800/80 py-2 pl-9 pr-3 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                </div>

                <select name="status"
                        class="rounded-xl border border-slate-700/60 bg-slate-800/80 py-2 px-3 text-xs text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-white transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.experiments.index') }}"
                       class="rounded-xl bg-slate-800/60 hover:bg-slate-800 px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Experiments Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Experiment</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                        <th class="px-5 py-3.5 font-semibold">Traffic</th>
                        <th class="px-5 py-3.5 font-semibold">Variants</th>
                        <th class="px-5 py-3.5 font-semibold">Primary Metric</th>
                        <th class="px-5 py-3.5 font-semibold text-right">Exposures</th>
                        <th class="px-5 py-3.5 font-semibold text-right">Conversions</th>
                        <th class="px-5 py-3.5 font-semibold text-right">CR %</th>
                        <th class="px-5 py-3.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($experiments as $exp)
                        @php
                            $cr = $exp->exposures_count > 0 ? round(($exp->conversions_count / $exp->exposures_count) * 100, 2) : 0;
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white text-sm">
                                    <a href="{{ route('admin.experiments.show', $exp) }}" class="hover:text-amber-400 transition">
                                        {{ $exp->name }}
                                    </a>
                                </div>
                                <div class="mt-0.5 flex items-center gap-2">
                                    <code class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-amber-400/90 font-mono">{{ $exp->key }}</code>
                                    @if($exp->description)
                                        <span class="text-[11px] text-slate-500 truncate max-w-xs">{{ $exp->description }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $exp->status->badgeClass() }}">
                                    {{ $exp->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-white">{{ $exp->traffic_percentage }}%</span>
                                <span class="text-[11px] text-slate-500 block">Audience: {{ $exp->target_audience }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @foreach($exp->variants as $v)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] {{ $v->is_control ? 'bg-slate-800 text-slate-300 font-semibold border border-slate-700' : 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' }}">
                                            {{ $v->name }} ({{ $v->weight }}%)
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-slate-300 text-[11px]">{{ $exp->primary_metric }}</span>
                            </td>
                            <td class="px-5 py-4 text-right font-medium text-white">
                                {{ number_format($exp->exposures_count) }}
                            </td>
                            <td class="px-5 py-4 text-right font-medium text-emerald-400">
                                {{ number_format($exp->conversions_count) }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-amber-400">
                                {{ $cr }}%
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.experiments.show', $exp) }}"
                                       class="rounded-lg bg-slate-800 hover:bg-slate-700 px-2.5 py-1 text-[11px] font-semibold text-slate-200 transition"
                                       title="View Results &amp; Analytics">
                                        Results
                                    </a>

                                    @if($exp->isDraft())
                                        <form action="{{ route('admin.experiments.activate', $exp) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2.5 py-1 text-[11px] font-semibold transition">
                                                Start
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.experiments.edit', $exp) }}"
                                           class="rounded-lg bg-slate-800 hover:bg-slate-700 px-2.5 py-1 text-[11px] text-slate-400 hover:text-white transition">
                                            Edit
                                        </a>
                                    @elseif($exp->isRunning())
                                        <form action="{{ route('admin.experiments.pause', $exp) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2.5 py-1 text-[11px] font-semibold transition">
                                                Pause
                                            </button>
                                        </form>
                                    @elseif($exp->isPaused())
                                        <form action="{{ route('admin.experiments.activate', $exp) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2.5 py-1 text-[11px] font-semibold transition">
                                                Resume
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                <svg class="mx-auto h-8 w-8 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                No experiments found. Click <strong>"New Experiment"</strong> to start testing your first hypothesis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($experiments->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $experiments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
