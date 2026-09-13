@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                    WhatsApp Cloud API
                </span>
                <span class="text-xs text-slate-500">Official Meta Business Platform</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                WhatsApp Integration
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Official WhatsApp Business Platform overview, delivery performance, template management, and message logs.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.whatsapp.templates.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Manage Templates
            </a>
            <a href="{{ route('admin.whatsapp.messages.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Message Logs
            </a>
            <a href="{{ route('admin.whatsapp.settings') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Integration Settings
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

    <!-- Health / Provider Status Notice -->
    <div class="rounded-2xl border {{ $isConfigured ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-amber-500/20 bg-amber-500/5' }} p-4 sm:p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $isConfigured ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-white">
                        {{ $isConfigured ? 'WhatsApp Cloud API Connected' : 'WhatsApp API Pending Configuration' }}
                    </h2>
                    <p class="text-xs text-slate-400">
                        {{ $isConfigured ? 'Provider: Meta Business Platform (Cloud API v21.0). Ready for transactional and consented marketing delivery.' : 'Meta credentials (Phone Number ID or Access Token) are not fully configured in your .env environment.' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.whatsapp.settings') }}" class="text-xs font-medium text-emerald-400 hover:text-emerald-300">
                View Credentials &rarr;
            </a>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Total Dispatched</p>
            <p class="mt-2 text-2xl font-bold text-white">{{ number_format($stats['total_messages']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">All message records</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Sent</p>
            <p class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['sent_messages']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Accepted by Meta</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Delivered</p>
            <p class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($stats['delivered_messages']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Confirmed on handset</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Read</p>
            <p class="mt-2 text-2xl font-bold text-indigo-400">{{ number_format($stats['read_messages']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Blue ticks / opened</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Failed</p>
            <p class="mt-2 text-2xl font-bold text-rose-400">{{ number_format($stats['failed_messages']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Delivery errors</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <p class="text-xs font-medium text-slate-400">Opted-In Audience</p>
            <p class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($stats['opted_in_leads'] + $stats['opted_in_users']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">{{ $stats['opted_in_leads'] }} leads, {{ $stats['opted_in_users'] }} users</p>
        </div>
    </div>

    <!-- Recent Messages Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-white">Recent WhatsApp Messages</h2>
                <p class="text-xs text-slate-400">Latest 10 transactional and automation dispatches</p>
            </div>
            <a href="{{ route('admin.whatsapp.messages.index') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                View All Messages &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4">Recipient</th>
                        <th class="py-3 px-4">Phone Number</th>
                        <th class="py-3 px-4">Template</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Direction</th>
                        <th class="py-3 px-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentMessages as $msg)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4">
                                @if($msg->lead)
                                    <a href="{{ route('admin.leads.show', $msg->lead) }}" class="font-medium text-emerald-400 hover:underline">
                                        {{ $msg->lead->name }}
                                    </a>
                                    <span class="block text-[10px] text-slate-500">Lead #{{ $msg->lead->id }}</span>
                                @elseif($msg->user)
                                    <span class="font-medium text-white">{{ $msg->user->name }}</span>
                                    <span class="block text-[10px] text-slate-500">User #{{ $msg->user->id }}</span>
                                @else
                                    <span class="text-slate-500">Unassociated</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-300">
                                {{ $msg->phone_normalized ?: $msg->phone_number }}
                            </td>
                            <td class="py-3 px-4">
                                @if($msg->template)
                                    <span class="font-medium text-white">{{ $msg->template->name }}</span>
                                    <span class="block text-[10px] text-slate-500">{{ $msg->template->template_name }}</span>
                                @else
                                    <span class="text-slate-500">Custom / Direct</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
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
                            </td>
                            <td class="py-3 px-4 text-slate-400 capitalize">
                                {{ $msg->direction }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $msg->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                No WhatsApp messages dispatched yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
