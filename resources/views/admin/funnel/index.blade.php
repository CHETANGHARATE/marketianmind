@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Analytics &amp; Optimization
                </span>
                <span class="text-xs text-slate-500">Full-Funnel Intelligence</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Conversion Funnel Analytics
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Track visitor progression, detect drop-off bottlenecks, and analyze conversion velocity from page view to enrollment.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.experiments.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                Manage Experiments
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.funnel.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-5 items-end">
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Filter Course</label>
                <select name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $selectedCourseId == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Filter Bundle</label>
                <select name="bundle_id" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">All Bundles</option>
                    @foreach($bundles as $bundle)
                        <option value="{{ $bundle->id }}" {{ $selectedBundleId == $bundle->id ? 'selected' : '' }}>
                            {{ $bundle->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="w-full rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-xs font-bold text-slate-950 transition">
                    Apply Filter
                </button>
                <a href="{{ route('admin.funnel.index') }}"
                   class="rounded-xl bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Overall Funnel Conversion KPI -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">Full-Funnel Conversion Pipeline</h2>
                <p class="text-xs text-slate-400 mt-0.5">Sequential stage progression from first page view to paid student enrollment.</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-400">End-to-End Conversion Rate</div>
                <div class="text-2xl font-bold text-amber-400">{{ $funnelMetrics['overall_conversion_rate'] }}%</div>
            </div>
        </div>

        <!-- Visual Step Funnel -->
        <div class="mt-6 space-y-4">
            @php
                $steps = $funnelMetrics['steps'] ?? [];
                $firstCount = count($steps) > 0 ? max(1, $steps[0]['count']) : 1;
            @endphp

            @foreach($steps as $index => $step)
                @php
                    $widthPercent = $firstCount > 0 ? max(5, round(($step['count'] / $firstCount) * 100, 1)) : 0;
                @endphp
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-slate-800 text-slate-400 font-mono text-[10px] px-1.5 py-0.5">Stage {{ $index + 1 }}</span>
                            <span class="font-semibold text-white">{{ $step['name'] }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-slate-400">
                                <strong class="text-white font-mono">{{ number_format($step['count']) }}</strong> events
                            </span>
                            @if($index > 0)
                                <span class="text-[11px] {{ $step['conversion_from_previous'] >= 50 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $step['conversion_from_previous'] }}% retention ({{ $step['drop_off_from_previous'] }}% drop-off)
                                </span>
                            @else
                                <span class="text-[11px] text-slate-500">100% baseline</span>
                            @endif
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="w-full bg-slate-950 rounded-xl h-4 overflow-hidden p-0.5 border border-slate-800/80">
                        <div class="h-full rounded-lg bg-gradient-to-r {{ $index === 0 ? 'from-indigo-600 to-indigo-400' : ($index === count($steps) - 1 ? 'from-amber-600 to-amber-400' : 'from-indigo-500 to-amber-500') }} transition-all duration-500"
                             style="width: {{ $widthPercent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Event Volume Breakdown Cards -->
    <div>
        <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-3">Event Volume Ingestion</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
            @foreach($eventNames as $eventName)
                @php $cnt = $eventCounts[$eventName->value] ?? 0; @endphp
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                    <div class="text-[11px] text-slate-400 font-mono truncate" title="{{ $eventName->value }}">{{ $eventName->value }}</div>
                    <div class="mt-2 text-xl font-bold {{ $cnt > 0 ? 'text-white' : 'text-slate-600' }}">{{ number_format($cnt) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Conversion Events Stream -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Live Conversion Stream</h2>
            <span class="text-xs text-slate-400">Latest recorded events with experiment attribution</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Event</th>
                        <th class="px-5 py-3 font-semibold">Target Item</th>
                        <th class="px-5 py-3 font-semibold">Visitor / User</th>
                        <th class="px-5 py-3 font-semibold">Experiment Attribution</th>
                        <th class="px-5 py-3 font-semibold">Path</th>
                        <th class="px-5 py-3 font-semibold text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentEvents as $event)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3">
                                <code class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-amber-400 font-mono">{{ $event->event_name }}</code>
                            </td>
                            <td class="px-5 py-3">
                                @if($event->course)
                                    <span class="text-white">{{ $event->course->title }}</span>
                                    <span class="text-[10px] text-slate-500 block">Course</span>
                                @elseif($event->bundle)
                                    <span class="text-white">{{ $event->bundle->title }}</span>
                                    <span class="text-[10px] text-slate-500 block">Bundle</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($event->user)
                                    <span class="text-white font-medium">{{ $event->user->name }}</span>
                                    <span class="text-[10px] text-slate-500 block">{{ $event->user->email }}</span>
                                @else
                                    <span class="text-slate-400 font-mono text-[10px] truncate block max-w-xs">{{ $event->anonymous_id }}</span>
                                    <span class="text-[10px] text-slate-500 block">Guest visitor</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($event->experiment && $event->variant)
                                    <span class="text-white font-medium">{{ $event->experiment->name }}</span>
                                    <span class="text-[10px] text-indigo-400 block">{{ $event->variant->name }} ({{ $event->variant->key }})</span>
                                @else
                                    <span class="text-slate-500 text-[11px]">Direct / Unattributed</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-400 font-mono text-[11px] truncate max-w-xs">
                                {{ $event->url_path ?? '/' }}
                            </td>
                            <td class="px-5 py-3 text-right text-slate-400 whitespace-nowrap">
                                {{ $event->occurred_at ? $event->occurred_at->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                No events recorded in the selected date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentEvents->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $recentEvents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
