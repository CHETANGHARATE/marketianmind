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
                <span class="text-xs text-slate-500">Automation Details &amp; History</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                {{ $automation->name }}
                <span class="text-xs px-2.5 py-1 rounded-md border {{ $automation->status->badgeClasses() }}">
                    {{ $automation->status->label() }}
                </span>
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Trigger: <span class="text-indigo-400 font-semibold">{{ $automation->trigger_type->label() }}</span> &bull;
                Delay: <span class="text-slate-300 font-semibold">{{ $automation->delay_minutes }}m</span> &bull;
                Template: <span class="text-slate-300 font-semibold">{{ $automation->template?->name ?? 'None' }}</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.automations.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                &larr; All Automations
            </a>
            <a href="{{ route('admin.automations.edit', $automation) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                Edit Rule
            </a>
            <form action="{{ route('admin.automations.destroy', $automation) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this automation rule?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 font-semibold text-xs border border-rose-500/20 transition">
                    Delete
                </button>
            </form>
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

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Enqueued</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($executionStats['total']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">All-time triggers recorded</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Delivered / Sent</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($executionStats['sent']) }}</div>
            <div class="mt-1 text-[11px] text-emerald-500/80">Emails sent successfully</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Pending Execution</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($executionStats['pending']) }}</div>
            <div class="mt-1 text-[11px] text-amber-500/80">Waiting for delay expiration</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Skipped (Opt-Outs/Rules)</div>
            <div class="mt-2 text-2xl font-bold text-slate-300">{{ number_format($executionStats['skipped']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Unsubscribed / filtered</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Failed Deliveries</div>
            <div class="mt-2 text-2xl font-bold text-rose-400">{{ number_format($executionStats['failed']) }}</div>
            <div class="mt-1 text-[11px] text-rose-500/80">SMTP or system errors</div>
        </div>
    </div>

    <!-- Execution Logs Filter & Table -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-base font-bold text-white">Execution Logs</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.automations.show', $automation) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    All
                </a>
                <a href="{{ route('admin.automations.show', [$automation, 'status' => 'sent']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'sent' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    Sent
                </a>
                <a href="{{ route('admin.automations.show', [$automation, 'status' => 'pending']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'pending' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    Pending
                </a>
                <a href="{{ route('admin.automations.show', [$automation, 'status' => 'skipped']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'skipped' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    Skipped
                </a>
                <a href="{{ route('admin.automations.show', [$automation, 'status' => 'failed']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'failed' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    Failed
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-semibold">ID</th>
                            <th class="py-3 px-4 font-semibold">Recipient</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold">Scheduled For</th>
                            <th class="py-3 px-4 font-semibold">Executed At</th>
                            <th class="py-3 px-4 font-semibold">Notes / Failure Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($executions as $exec)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">
                                    #{{ $exec->id }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">
                                        {{ ucfirst($exec->recipient_type) }} #{{ $exec->recipient_id }}
                                    </div>
                                    @php
                                        $recipient = $exec->getRecipient();
                                    @endphp
                                    @if($recipient)
                                        <div class="text-[11px] text-slate-400">{{ $recipient->name }} ({{ $recipient->email }})</div>
                                    @else
                                        <div class="text-[11px] text-slate-500">Entity removed</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $exec->status->badgeClasses() }}">
                                        {{ $exec->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 font-mono">
                                    {{ $exec->scheduled_at->format('M d, Y H:i') }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 font-mono">
                                    {{ $exec->executed_at ? $exec->executed_at->format('M d, Y H:i') : '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 max-w-xs truncate">
                                    @if($exec->failure_reason)
                                        <span class="text-rose-400" title="{{ $exec->failure_reason }}">{{ $exec->failure_reason }}</span>
                                    @elseif($exec->status->value === 'sent')
                                        <span class="text-emerald-400">Delivered</span>
                                    @else
                                        <span class="text-slate-500">Awaiting cron batch</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <p class="text-sm">No executions recorded for this filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($executions->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $executions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
