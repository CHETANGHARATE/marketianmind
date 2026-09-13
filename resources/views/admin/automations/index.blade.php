@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-400 ring-1 ring-inset ring-indigo-500/20">
                    Marketing Automation
                </span>
                <span class="text-xs text-slate-500">Workflows &amp; Triggers</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Automations Engine
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Automate linear marketing campaigns triggered by inbound leads, user registration, enrollments, and course completions.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('admin.automations.process-now') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Process Due Executions
                </button>
            </form>
            <a href="{{ route('admin.marketing-templates.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Manage Templates
            </a>
            <a href="{{ route('admin.automations.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Automation
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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Rules</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($stats['total']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Configured in system</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Active Automations</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($stats['active']) }}</div>
            <div class="mt-1 text-[11px] text-emerald-500/80">Listening for triggers</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Paused Automations</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($stats['paused']) }}</div>
            <div class="mt-1 text-[11px] text-amber-500/80">Temporarily halted</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Sent Executions</div>
            <div class="mt-2 text-2xl font-bold text-indigo-400">{{ number_format($stats['total_sent']) }}</div>
            <div class="mt-1 text-[11px] text-indigo-400/80">Delivered successfully</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Pending Queue</div>
            <div class="mt-2 text-2xl font-bold text-blue-400">{{ number_format($stats['total_pending']) }}</div>
            <div class="mt-1 text-[11px] text-blue-400/80">Awaiting delay expiration</div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.automations.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search automations by name..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <select name="status" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="trigger" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Triggers</option>
                    @foreach($triggers as $trg)
                        <option value="{{ $trg->value }}" {{ request('trigger') === $trg->value ? 'selected' : '' }}>
                            {{ $trg->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl transition">
                Filter
            </button>

            @if(request()->anyFilled(['search', 'status', 'trigger']))
                <a href="{{ route('admin.automations.index') }}" class="px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Automations Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Rule Name</th>
                        <th class="py-3 px-4 font-semibold">Trigger Event</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Template</th>
                        <th class="py-3 px-4 font-semibold">Delay</th>
                        <th class="py-3 px-4 font-semibold">Sent / Pending</th>
                        <th class="py-3 px-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($automations as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.automations.show', $item) }}" class="font-bold text-white text-sm hover:text-indigo-400 transition">
                                    {{ $item->name }}
                                </a>
                                @if($item->description)
                                    <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ $item->trigger_type->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $item->status->badgeClasses() }}">
                                    {{ $item->status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($item->template)
                                    <a href="{{ route('admin.marketing-templates.edit', $item->template) }}" class="text-indigo-400 hover:underline">
                                        {{ $item->template->name }}
                                    </a>
                                @else
                                    <span class="text-rose-400">Missing Template</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-300 font-mono">
                                @if($item->delay_minutes === 0)
                                    <span class="text-emerald-400">Immediate</span>
                                @elseif($item->delay_minutes < 60)
                                    {{ $item->delay_minutes }}m
                                @elseif($item->delay_minutes < 1440)
                                    {{ round($item->delay_minutes / 60, 1) }}h
                                @else
                                    {{ round($item->delay_minutes / 1440, 1) }}d
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-400 font-bold">{{ $item->sent_executions_count }}</span>
                                    <span class="text-slate-600">/</span>
                                    <span class="text-amber-400 font-bold">{{ $item->pending_executions_count }}</span>
                                    @if($item->failed_executions_count > 0)
                                        <span class="text-rose-400 font-bold">({{ $item->failed_executions_count }} fail)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.automations.show', $item) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs transition">
                                        View
                                    </a>
                                    <a href="{{ route('admin.automations.edit', $item) }}"
                                       class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-400 text-xs transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.automations.toggle', $item) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($item->status === \App\Enums\AutomationStatus::ACTIVE)
                                            <input type="hidden" name="status" value="paused">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-xs transition">
                                                Pause
                                            </button>
                                        @else
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-xs transition">
                                                Activate
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <p class="text-sm">No automations configured yet.</p>
                                <a href="{{ route('admin.automations.create') }}" class="mt-3 inline-block text-xs font-semibold text-indigo-400 hover:underline">
                                    Create your first marketing automation &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($automations->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $automations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
