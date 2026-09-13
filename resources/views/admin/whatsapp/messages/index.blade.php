@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.whatsapp.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-400">
                    &larr; WhatsApp Dashboard
                </a>
                <span class="text-xs text-slate-600">/</span>
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                    Messages Log
                </span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                WhatsApp Message Logs
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Detailed audit trail of inbound and outbound WhatsApp dispatches, Meta provider message IDs, and delivery statuses.
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
        <form method="GET" action="{{ route('admin.whatsapp.messages.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or wamid..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <select name="status" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ ucfirst($st->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="direction" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">All Directions</option>
                    <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound Only</option>
                    <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound Only</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'direction']))
                    <a href="{{ route('admin.whatsapp.messages.index') }}" class="rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Messages Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="border-b border-slate-800 bg-slate-800/30 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4">Recipient</th>
                        <th class="py-3 px-4">Phone Number</th>
                        <th class="py-3 px-4">Template / Content</th>
                        <th class="py-3 px-4">Direction</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Provider ID (WAMID)</th>
                        <th class="py-3 px-4">Dispatched At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($messages as $msg)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4">
                                @if($msg->lead)
                                    <a href="{{ route('admin.leads.show', $msg->lead) }}" class="font-medium text-emerald-400 hover:underline">
                                        {{ $msg->lead->name }}
                                    </a>
                                    <span class="block text-[10px] text-slate-500">Lead #{{ $msg->lead->id }}</span>
                                @elseif($msg->user)
                                    <span class="font-medium text-white">{{ $msg->user->name }}</span>
                                    <span class="block text-[10px] text-slate-500">User #{{ $msg->user->id }}</span>
                                @else
                                    <span class="text-slate-500">Direct Recipient</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 font-mono text-slate-300">
                                {{ $msg->phone_normalized ?: $msg->phone_number }}
                            </td>
                            <td class="py-4 px-4">
                                @if($msg->template)
                                    <span class="font-medium text-white block">{{ $msg->template->name }}</span>
                                    <span class="text-[10px] font-mono text-emerald-400">{{ $msg->template->template_name }}</span>
                                @else
                                    <span class="text-slate-400 block">{{ Str::limit($msg->metadata['body'] ?? 'Custom payload', 50) }}</span>
                                    <span class="text-[10px] text-slate-500 capitalize">{{ $msg->message_type }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 capitalize text-slate-400">
                                {{ $msg->direction }}
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $badgeColors = [
                                        'pending' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                        'queued' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'sent' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                        'delivered' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'read' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                        'failed' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'cancelled' => 'bg-slate-600/10 text-slate-400 border-slate-600/20',
                                    ];
                                    $st = $msg->status instanceof \BackedEnum ? $msg->status->value : (string)$msg->status;
                                    $color = $badgeColors[$st] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium border {{ $color }}">
                                    {{ ucfirst($st) }}
                                </span>
                                @if($msg->error_message)
                                    <span class="block mt-1 text-[10px] text-rose-400 max-w-xs truncate" title="{{ $msg->error_message }}">
                                        {{ $msg->error_message }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 font-mono text-[11px] text-slate-400 truncate max-w-[140px]" title="{{ $msg->provider_message_id }}">
                                {{ $msg->provider_message_id ?: '—' }}
                            </td>
                            <td class="py-4 px-4 text-slate-400 whitespace-nowrap">
                                {{ $msg->created_at->format('M d, Y H:i') }}
                                <span class="block text-[10px] text-slate-500">{{ $msg->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                No WhatsApp messages recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($messages->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
