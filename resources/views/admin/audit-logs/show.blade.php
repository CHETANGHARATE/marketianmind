@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.audit_logs.index') }}" class="text-slate-400 hover:text-white transition flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Audit Logs
            </a>
            <span class="text-slate-600">/</span>
            <span class="text-amber-400">Event #{{ $auditLog->id }}</span>
        </div>

        <div>
            <a href="{{ route('admin.audit_logs.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                &larr; Back to Audit Trail
            </a>
        </div>
    </div>

    <!-- Main Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $auditLog->action_badge_classes }}">
                        {{ ucfirst($auditLog->action) }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-300 border border-slate-700">
                        {{ $auditLog->resource_type_label }}
                    </span>
                    <span class="text-xs text-slate-400">
                        Event ID: <strong class="text-white">#{{ $auditLog->id }}</strong>
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                    {{ $auditLog->description }}
                </h1>
                <p class="text-xs text-slate-400">
                    Recorded on {{ $auditLog->created_at->format('F d, Y \a\t h:i:s A') }} ({{ $auditLog->created_at->diffForHumans() }})
                </p>
            </div>
        </div>
    </div>

    <!-- 2 Column Details: Audit Information & Request Context -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Audit Information -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
            <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Audit Information
                </h3>
                <span class="text-[11px] text-slate-500 font-mono">ID: {{ $auditLog->id }}</span>
            </div>

            <dl class="divide-y divide-slate-800/60 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Action Verb</dt>
                    <dd class="font-bold text-white uppercase">{{ $auditLog->action }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Target Resource Type</dt>
                    <dd class="font-medium text-slate-200">{{ $auditLog->resource_type_label }} ({{ $auditLog->auditable_type }})</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Target Resource ID</dt>
                    <dd class="font-mono text-slate-300">{{ $auditLog->auditable_id ?? 'N/A' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Resource Label / Title</dt>
                    <dd class="font-bold text-amber-400">{{ $auditLog->resource_label ?? 'N/A' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Admin Actor Name</dt>
                    <dd class="font-bold text-white">{{ $auditLog->user->name ?? $auditLog->admin_name ?? 'System' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Admin Email</dt>
                    <dd class="text-slate-300 font-mono">{{ $auditLog->user->email ?? 'Account deleted / System' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Request Context -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
            <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    Request Context
                </h3>
                <span class="text-[11px] text-slate-500 font-mono">Immutable</span>
            </div>

            <dl class="divide-y divide-slate-800/60 text-xs">
                <div class="py-2.5 flex justify-between items-center">
                    <dt class="text-slate-400">IP Address</dt>
                    <dd class="font-mono text-slate-200 bg-slate-950 px-2 py-0.5 rounded-md border border-slate-800">
                        {{ $auditLog->ip_address ?? 'Not Recorded / Console' }}
                    </dd>
                </div>
                <div class="py-2.5">
                    <dt class="text-slate-400 mb-1">User Agent</dt>
                    <dd class="font-mono text-[11px] text-slate-400 bg-slate-950 p-2 rounded-lg border border-slate-800 break-all leading-relaxed">
                        {{ $auditLog->user_agent ?? 'Not Recorded / Console' }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-slate-400">Timestamp</dt>
                    <dd class="text-slate-300 font-mono">{{ $auditLog->created_at->toIso8601String() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- State & Values Changes Inspector -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4">
        <div class="border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                State Changes & Value Diff
            </h3>
            <p class="text-xs text-slate-400 mt-1">
                Field-level inspection of captured data. Sensitive fields (passwords, tokens, credentials, secrets) are stripped prior to persistence.
            </p>
        </div>

        @php
            $old = $auditLog->old_values ?? [];
            $new = $auditLog->new_values ?? [];
            $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
            sort($allKeys);
        @endphp

        @if(!empty($allKeys))
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-900/80 text-[10px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="py-3 pl-4 pr-3 w-1/4">Field / Attribute</th>
                            <th class="py-3 px-3 w-3/8 text-rose-400">Previous Value</th>
                            <th class="py-3 pl-3 pr-4 w-3/8 text-emerald-400">New Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @foreach($allKeys as $key)
                            @php
                                $valOld = $old[$key] ?? null;
                                $valNew = $new[$key] ?? null;
                                $changed = ($valOld !== $valNew);
                            @endphp
                            <tr class="{{ $changed ? 'bg-slate-900/30' : '' }}">
                                <td class="py-2.5 pl-4 pr-3 font-semibold text-slate-300">
                                    {{ $key }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-400 break-all">
                                    @if(is_null($valOld))
                                        <span class="text-slate-600 italic">null</span>
                                    @elseif(is_bool($valOld))
                                        <span class="text-amber-400 font-bold">{{ $valOld ? 'true' : 'false' }}</span>
                                    @elseif(is_array($valOld))
                                        <pre class="text-[11px] text-slate-300 whitespace-pre-wrap">{{ json_encode($valOld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <span class="{{ $changed ? 'text-rose-400' : 'text-slate-400' }}">{{ (string)$valOld }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pl-3 pr-4 text-slate-300 break-all">
                                    @if(is_null($valNew))
                                        <span class="text-slate-600 italic">null</span>
                                    @elseif(is_bool($valNew))
                                        <span class="text-amber-400 font-bold">{{ $valNew ? 'true' : 'false' }}</span>
                                    @elseif(is_array($valNew))
                                        <pre class="text-[11px] text-slate-300 whitespace-pre-wrap">{{ json_encode($valNew, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <span class="{{ $changed ? 'text-emerald-400 font-semibold' : 'text-slate-400' }}">{{ (string)$valNew }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-6 text-center text-slate-500 italic rounded-xl border border-slate-800 bg-slate-950">
                No differential attribute state was recorded for this event (action performed: {{ ucfirst($auditLog->action) }}).
            </div>
        @endif
    </div>
</div>
@endsection