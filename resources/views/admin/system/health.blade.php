@extends('layouts.admin')

@section('subcontent')
<div class="space-y-8">
    <!-- Header & Quick Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                    Hostinger Operational Health
                </span>
                <span class="text-xs text-slate-500">System Diagnostics &amp; Recovery</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                System Health &amp; Backups
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Live performance diagnostics, runtime constraints, database integrity, and automated disaster recovery backups.
            </p>
        </div>

        <!-- On-Demand Backup Actions -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <form method="POST" action="{{ route('admin.system.backups.run') }}" class="inline">
                @csrf
                <input type="hidden" name="type" value="db">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-8 4v6m-3-3h6" />
                    </svg>
                    Backup Database Now
                </button>
            </form>

            <form method="POST" action="{{ route('admin.system.backups.run') }}" class="inline">
                @csrf
                <input type="hidden" name="type" value="files">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    Backup Files Now
                </button>
            </form>

            <a href="{{ route('health') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Public /health API
            </a>
        </div>
    </div>

    <!-- Feedback Notifications -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Database Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Database Health</span>
                @if($dbDiagnostic['status'] === 'ok')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Connected
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/15 px-2 py-0.5 text-xs font-semibold text-rose-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                        Degraded
                    </span>
                @endif
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white uppercase">{{ $dbDiagnostic['driver'] }}</span>
                @if($dbDiagnostic['latency_ms'])
                    <span class="text-xs font-medium text-slate-400">({{ $dbDiagnostic['latency_ms'] }} ms)</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-400">
                {{ $dbDiagnostic['tables_count'] }} tables &bull; {{ $dbDiagnostic['database'] ?: 'active schema' }}
            </p>
        </div>

        <!-- Storage Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Storage Disk</span>
                @if($storageDiagnostic['writable'])
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Writable
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/15 px-2 py-0.5 text-xs font-semibold text-rose-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                        Read-Only
                    </span>
                @endif
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white">{{ $storageDiagnostic['free_formatted'] }}</span>
                <span class="text-xs font-medium text-slate-400">free</span>
            </div>
            <p class="mt-1 text-xs text-slate-400">
                Total: {{ $storageDiagnostic['total_formatted'] }}
                @if($storageDiagnostic['used_percentage'])
                    ({{ $storageDiagnostic['used_percentage'] }}% used)
                @endif
            </p>
        </div>

        <!-- Cache Subsystem Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Cache Subsystem</span>
                @if($cacheDiagnostic['operational'])
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Healthy
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        Check
                    </span>
                @endif
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white uppercase">{{ $cacheDiagnostic['store'] }}</span>
                <span class="text-xs font-medium text-slate-400">store</span>
            </div>
            <p class="mt-1 text-xs text-slate-400">
                Shared-hosting optimized &bull; Read/write verified
            </p>
        </div>

        <!-- Disaster Recovery Backups Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Backup Engine</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-400">
                    Retain: 7d / 4w
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white">{{ count($backups) }}</span>
                <span class="text-xs font-medium text-slate-400">archives stored</span>
            </div>
            <p class="mt-1 text-xs text-slate-400">
                Total size: {{ $totalBackupStorageFormatted }} &bull; Private storage
            </p>
        </div>
    </div>

    <!-- Runtime & Environment Grid -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
        <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Hostinger Shared Hosting Runtime Limits &amp; Safeguards
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">PHP Version</span>
                <span class="mt-1 text-sm font-bold text-slate-200">{{ $environment['php_version'] }}</span>
            </div>
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">Laravel Version</span>
                <span class="mt-1 text-sm font-bold text-slate-200">{{ $environment['laravel_version'] }}</span>
            </div>
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">Memory Limit</span>
                <span class="mt-1 text-sm font-bold text-slate-200">{{ $environment['memory_limit'] }}</span>
            </div>
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">Execution Limit</span>
                <span class="mt-1 text-sm font-bold text-slate-200">{{ $environment['max_execution_time'] }}</span>
            </div>
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">Upload Limit</span>
                <span class="mt-1 text-sm font-bold text-slate-200">{{ $environment['upload_max_filesize'] }}</span>
            </div>
            <div class="rounded-xl bg-slate-950/60 p-3.5 border border-slate-800/80">
                <span class="block text-[11px] font-semibold text-slate-400 uppercase">Environment</span>
                <div class="mt-1 flex items-center gap-1.5">
                    <span class="text-sm font-bold {{ $environment['app_env'] === 'production' ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ ucfirst($environment['app_env']) }}
                    </span>
                    @if($environment['app_debug'])
                        <span class="rounded bg-rose-500/20 px-1 text-[10px] font-semibold text-rose-400">DEBUG ON</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stored Backups Management Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-white">Stored Backups Archive</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Located in private non-public directory <code class="text-amber-400 text-[11px]">storage/app/backups/</code> with automated retention pruning.
                </p>
            </div>
            <span class="text-xs text-slate-500">
                {{ count($backups) }} total file(s)
            </span>
        </div>

        @if(empty($backups))
            <div class="p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-slate-800/80 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-300">No backup archives generated yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Trigger an on-demand database or files backup above, or wait for the automated cron scheduler.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Archive Filename</th>
                            <th class="px-6 py-3 font-semibold">Type</th>
                            <th class="px-6 py-3 font-semibold">Size</th>
                            <th class="px-6 py-3 font-semibold">Created / Modified</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-200">
                                    <div class="flex items-center gap-2.5">
                                        @if($backup['type'] === 'db')
                                            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        @endif
                                        <span>{{ $backup['filename'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-xs">
                                    @if($backup['type'] === 'db')
                                        <span class="inline-flex items-center rounded-md bg-blue-500/10 px-2 py-0.5 text-xs font-medium text-blue-400 ring-1 ring-inset ring-blue-500/20">
                                            Database SQL
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                            Media &amp; Files
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-xs font-semibold text-slate-300">
                                    {{ $backup['size_formatted'] }}
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-400">
                                    {{ $backup['modified_at'] }}
                                </td>
                                <td class="px-6 py-3.5 text-xs text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.system.backups.download', ['type' => $backup['type'], 'filename' => $backup['filename']]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download
                                        </a>

                                        <form method="POST" action="{{ route('admin.system.backups.delete', ['type' => $backup['type'], 'filename' => $backup['filename']]) }}"
                                              onsubmit="return confirm('Are you sure you want to permanently delete this backup?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Operational Cron & Disaster Recovery Runbook Quick Reference -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Hostinger Shared Hosting Deployment &amp; Cron Configuration
        </h3>
        <p class="text-xs text-slate-400 leading-relaxed">
            Marketian Mind relies on a single cron job configured in Hostinger hPanel to process scheduled automations, student engagement reminders, and automated database backups without needing persistent daemon workers:
        </p>
        <div class="rounded-xl bg-slate-950 p-3.5 border border-slate-800 font-mono text-xs text-amber-300 overflow-x-auto">
            * * * * * cd /home/uXXXXX/public_html &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 text-xs text-slate-400">
            <div class="rounded-lg bg-slate-950/40 p-3 border border-slate-800/80">
                <span class="font-semibold text-slate-200">Recovery Time Objective (RTO):</span>
                <span class="text-amber-400 font-bold ml-1">&lt; 1 Hour</span>
                <p class="mt-1 text-slate-500">Database restoration from gzip dump takes under 5 minutes via phpMyAdmin or SSH CLI.</p>
            </div>
            <div class="rounded-lg bg-slate-950/40 p-3 border border-slate-800/80">
                <span class="font-semibold text-slate-200">Recovery Point Objective (RPO):</span>
                <span class="text-amber-400 font-bold ml-1">&lt; 24 Hours</span>
                <p class="mt-1 text-slate-500">Automated daily snapshot at 02:00 UTC plus Razorpay transaction logs for payment recovery.</p>
            </div>
        </div>
    </div>
</div>
@endsection
