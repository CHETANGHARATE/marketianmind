@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="{{ route('admin.experiments.index') }}" class="hover:text-amber-400 transition">&larr; Back to Experiments</a>
                <span>/</span>
                <span class="text-white">{{ $experiment->name }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ $experiment->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $experiment->status->badgeClass() }}">
                    {{ $experiment->status->label() }}
                </span>
            </div>
            <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                <span>Key: <code class="rounded bg-slate-800 px-1.5 py-0.5 text-amber-400 font-mono">{{ $experiment->key }}</code></span>
                <span>&bull;</span>
                <span>Traffic: <strong class="text-white">{{ $experiment->traffic_percentage }}%</strong></span>
                <span>&bull;</span>
                <span>Primary Metric: <strong class="text-white font-mono">{{ $experiment->primary_metric }}</strong></span>
                @if($experiment->started_at)
                    <span>&bull;</span>
                    <span>Started: {{ $experiment->started_at->format('M d, Y H:i') }}</span>
                @endif
                @if($experiment->ended_at)
                    <span>&bull;</span>
                    <span>Ended: {{ $experiment->ended_at->format('M d, Y H:i') }}</span>
                @endif
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            @if($experiment->isDraft())
                <form action="{{ route('admin.experiments.activate', $experiment) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-xl bg-emerald-500 hover:bg-emerald-400 px-4 py-2 text-xs font-bold text-slate-950 transition">
                        Launch Experiment
                    </button>
                </form>
                <a href="{{ route('admin.experiments.edit', $experiment) }}" class="rounded-xl bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition">
                    Edit
                </a>
            @elseif($experiment->isRunning())
                <form action="{{ route('admin.experiments.pause', $experiment) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3.5 py-2 text-xs font-semibold transition">
                        Pause
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('complete-modal').classList.remove('hidden')"
                        class="rounded-xl bg-sky-500 hover:bg-sky-400 px-4 py-2 text-xs font-bold text-slate-950 transition">
                    Complete &amp; Pick Winner
                </button>
            @elseif($experiment->isPaused())
                <form action="{{ route('admin.experiments.activate', $experiment) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-xl bg-emerald-500 hover:bg-emerald-400 px-4 py-2 text-xs font-bold text-slate-950 transition">
                        Resume
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('complete-modal').classList.remove('hidden')"
                        class="rounded-xl bg-sky-500 hover:bg-sky-400 px-4 py-2 text-xs font-bold text-slate-950 transition">
                    Complete
                </button>
            @endif

            @if(!$experiment->isArchived())
                <form action="{{ route('admin.experiments.archive', $experiment) }}" method="POST" class="inline" onsubmit="return confirm('Archive this experiment?');">
                    @csrf
                    <button type="submit" class="rounded-xl bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                        Archive
                    </button>
                </form>
            @endif
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

    <!-- High-level Summary Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="text-xs font-medium text-slate-400">Total Unique Exposures</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($results['total_exposures']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Hash-bucketed visitors</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="text-xs font-medium text-slate-400">Total Primary Conversions</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($results['total_conversions']) }}</div>
            <div class="mt-1 text-[11px] text-emerald-500/80">{{ $experiment->primary_metric }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="text-xs font-medium text-slate-400">Overall Conversion Rate</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ $results['overall_conversion_rate'] }}%</div>
            <div class="mt-1 text-[11px] text-slate-500">Across all variants</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="text-xs font-medium text-slate-400">Winning Variant</div>
            <div class="mt-2 text-base font-bold text-white truncate">
                @if($experiment->winningVariant)
                    <span class="text-emerald-400 flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        {{ $experiment->winningVariant->name }}
                    </span>
                @elseif($experiment->isCompleted())
                    <span class="text-slate-400">Inconclusive / Neutral</span>
                @else
                    <span class="text-slate-500 italic text-sm">Test in progress</span>
                @endif
            </div>
            <div class="mt-1 text-[11px] text-slate-500">Declared at conclusion</div>
        </div>
    </div>

    <!-- Variant Comparison Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Variant Performance Comparison</h2>
            <span class="text-xs text-slate-400">Metric: <code class="text-amber-400 font-mono">{{ $experiment->primary_metric }}</code></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">Variant</th>
                        <th class="px-6 py-3.5 font-semibold">Type</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Target Weight</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Exposures</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Conversions</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Conversion Rate</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Relative Lift</th>
                        <th class="px-6 py-3.5 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($results['variants'] as $v)
                        <tr class="hover:bg-slate-800/30 transition {{ $experiment->winning_variant_id === $v['id'] ? 'bg-emerald-950/20' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white text-sm flex items-center gap-2">
                                    {{ $v['name'] }}
                                    @if($experiment->winning_variant_id === $v['id'])
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-400 border border-emerald-500/30">
                                            Winner
                                        </span>
                                    @endif
                                </div>
                                <code class="text-[10px] text-amber-400 font-mono">{{ $v['key'] }}</code>
                            </td>
                            <td class="px-6 py-4">
                                @if($v['is_control'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                        Baseline / Control
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                        Treatment
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-400">
                                {{ $v['weight'] }}%
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-semibold text-white">{{ number_format($v['exposures']) }}</div>
                                <div class="text-[10px] text-slate-500">{{ $v['exposure_share'] }}% share</div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-400">
                                {{ number_format($v['conversions']) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-sm text-white">{{ $v['conversion_rate'] }}%</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($v['is_control'])
                                    <span class="text-slate-500 text-[11px]">— (Baseline)</span>
                                @else
                                    @php $lift = $v['relative_lift_percent']; @endphp
                                    @if($lift > 0)
                                        <span class="font-bold text-emerald-400">+{{ $lift }}%</span>
                                    @elseif($lift < 0)
                                        <span class="font-bold text-rose-400">{{ $lift }}%</span>
                                    @else
                                        <span class="text-slate-400">0.00%</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($experiment->winning_variant_id === $v['id'])
                                    <span class="text-emerald-400 font-bold text-xs">Declared Winner</span>
                                @elseif($experiment->isCompleted())
                                    <span class="text-slate-500 text-xs">Concluded</span>
                                @else
                                    <span class="text-slate-400 text-xs">Active</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Variant Configurations (Read-Only Safety Inspection) -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Variant Payload &amp; Safe JSON Config</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">
                Safe data-driven payloads consumed by Blade templates. Zero code execution is enforced.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach($experiment->variants as $var)
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-xs text-white">{{ $var->name }} (<code>{{ $var->key }}</code>)</span>
                        @if($var->is_control)
                            <span class="text-[10px] rounded bg-slate-800 px-1.5 py-0.5 text-slate-400">Control</span>
                        @endif
                    </div>
                    <pre class="text-[11px] text-slate-300 font-mono bg-slate-900 rounded p-3 overflow-x-auto border border-slate-800">{{ json_encode($var->config ?? [], JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal for Completing Experiment & Picking Winner -->
<div id="complete-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-xs p-4 hidden">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6 space-y-4 shadow-2xl">
        <h3 class="text-base font-bold text-white">Conclude Experiment</h3>
        <p class="text-xs text-slate-400">
            Conclude this experiment and optionally pick a winning variant. Once completed, new exposures stop being counted.
        </p>

        <form action="{{ route('admin.experiments.complete', $experiment) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Winning Variant (Optional)</label>
                <select name="winning_variant_id" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">No winner (Inconclusive / Keep Control)</option>
                    @foreach($experiment->variants as $variant)
                        <option value="{{ $variant->id }}">
                            {{ $variant->name }} ({{ $variant->key }}) {{ $variant->is_control ? '- Control' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('complete-modal').classList.add('hidden')"
                        class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-xl bg-sky-500 hover:bg-sky-400 px-5 py-2 text-xs font-bold text-slate-950 shadow-sm transition">
                    Confirm Completion
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
