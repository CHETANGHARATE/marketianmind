@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                    Mini CRM
                </span>
                <span class="text-xs text-slate-400">Visitor-to-Student Pipeline</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
                Leads &amp; Inquiries
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                Track, nurture, and convert inquiries, prospect business owners, and inbound leads into enrolled students.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.leads.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Lead
            </a>
        </div>
    </div>

    <!-- Pipeline Stages Horizontal Bar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-3">
            Pipeline Stages &amp; Volume
        </span>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
            @php
                $stages = [
                    'all' => ['label' => 'Total Inquiries', 'count' => $pipelineCounts['total'] ?? 0, 'param' => ''],
                    'new' => ['label' => 'New / Inbound', 'count' => $pipelineCounts['new'] ?? 0, 'param' => 'new'],
                    'contacted' => ['label' => 'Contacted', 'count' => $pipelineCounts['contacted'] ?? 0, 'param' => 'contacted'],
                    'qualified' => ['label' => 'Qualified', 'count' => $pipelineCounts['qualified'] ?? 0, 'param' => 'qualified'],
                    'interested' => ['label' => 'Interested', 'count' => $pipelineCounts['interested'] ?? 0, 'param' => 'interested'],
                    'follow_up' => ['label' => 'Follow-Up', 'count' => $pipelineCounts['follow_up'] ?? 0, 'param' => 'follow_up'],
                    'converted' => ['label' => 'Converted', 'count' => $pipelineCounts['converted'] ?? 0, 'param' => 'converted'],
                    'lost' => ['label' => 'Lost / Closed', 'count' => ($pipelineCounts['lost'] ?? 0) + ($pipelineCounts['not_interested'] ?? 0), 'param' => 'lost'],
                ];
            @endphp
            @foreach($stages as $key => $stage)
                @php
                    $isActive = ($key === 'all' && !request('status')) || (request('status') === $stage['param']);
                @endphp
                <a href="{{ route('admin.leads.index', array_merge(request()->except(['status', 'page']), $stage['param'] ? ['status' => $stage['param']] : [])) }}"
                   class="group flex flex-col justify-between rounded-lg p-3 transition border {{ $isActive ? 'bg-amber-500/10 border-amber-500/40' : 'bg-slate-950/40 border-slate-800/80 hover:bg-slate-800/40 hover:border-slate-700' }}">
                    <span class="text-[10px] font-semibold {{ $isActive ? 'text-amber-400' : 'text-slate-400 group-hover:text-slate-300' }} truncate">
                        {{ $stage['label'] }}
                    </span>
                    <span class="text-lg font-black {{ $isActive ? 'text-white' : 'text-slate-200' }} mt-1">
                        {{ number_format($stage['count']) }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Quick Follow-up Shortcuts -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Due Today -->
        <a href="{{ route('admin.leads.index', array_merge(request()->except(['follow_up', 'page']), ['follow_up' => 'today'])) }}"
           class="flex items-center justify-between rounded-xl border p-4 transition {{ request('follow_up') === 'today' ? 'bg-amber-500/10 border-amber-500/40' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700' }}">
            <div>
                <span class="text-xs font-semibold text-slate-400">Follow-Ups Due Today</span>
                <div class="text-2xl font-bold text-amber-400 mt-0.5">
                    {{ number_format($followUpCounts['today'] ?? 0) }}
                </div>
            </div>
            <span class="rounded-lg bg-amber-500/20 p-2.5 text-amber-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
        </a>

        <!-- Overdue -->
        <a href="{{ route('admin.leads.index', array_merge(request()->except(['follow_up', 'page']), ['follow_up' => 'overdue'])) }}"
           class="flex items-center justify-between rounded-xl border p-4 transition {{ request('follow_up') === 'overdue' ? 'bg-rose-500/10 border-rose-500/40' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700' }}">
            <div>
                <span class="text-xs font-semibold text-slate-400">Overdue Follow-Ups</span>
                <div class="text-2xl font-bold text-rose-400 mt-0.5">
                    {{ number_format($followUpCounts['overdue'] ?? 0) }}
                </div>
            </div>
            <span class="rounded-lg bg-rose-500/20 p-2.5 text-rose-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
        </a>

        <!-- Upcoming -->
        <a href="{{ route('admin.leads.index', array_merge(request()->except(['follow_up', 'page']), ['follow_up' => 'upcoming'])) }}"
           class="flex items-center justify-between rounded-xl border p-4 transition {{ request('follow_up') === 'upcoming' ? 'bg-sky-500/10 border-sky-500/40' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700' }}">
            <div>
                <span class="text-xs font-semibold text-slate-400">Upcoming Follow-Ups</span>
                <div class="text-2xl font-bold text-sky-400 mt-0.5">
                    {{ number_format($followUpCounts['upcoming'] ?? 0) }}
                </div>
            </div>
            <span class="rounded-lg bg-sky-500/20 p-2.5 text-sky-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </span>
        </a>
    </div>

    <!-- Multi-Filter & Search Toolbar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.leads.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Query -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search Leads</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Name, email, phone, company..."
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                    <select name="status"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Statuses</option>
                        @foreach(\App\Enums\LeadStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Priority</label>
                    <select name="priority"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Priorities</option>
                        @foreach(\App\Enums\LeadPriority::cases() as $pr)
                            <option value="{{ $pr->value }}" {{ request('priority') === $pr->value ? 'selected' : '' }}>
                                {{ $pr->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned Staff Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Assigned To</label>
                    <select name="assigned_to"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Staff &amp; Unassigned</option>
                        <option value="me" {{ request('assigned_to') === 'me' ? 'selected' : '' }}>Assigned to Me</option>
                        <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                        @foreach($assignees as $staff)
                            <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-800/60">
                <!-- Course Interest -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Course Interest</label>
                    <select name="course_id"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                                {{ Str::limit($c->title, 26) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Bundle Interest -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Bundle Interest</label>
                    <select name="bundle_id"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Bundles</option>
                        @foreach($bundles as $b)
                            <option value="{{ $b->id }}" {{ request('bundle_id') == $b->id ? 'selected' : '' }}>
                                {{ Str::limit($b->title, 26) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Lead Source</label>
                    <select name="source"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">All Sources</option>
                        @foreach($sources as $src)
                            <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $src)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sort By</label>
                    <select name="sort"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest Inquiries</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest Inquiries</option>
                        <option value="priority" {{ request('sort') === 'priority' ? 'selected' : '' }}>Priority (Urgent First)</option>
                        <option value="follow_up_asc" {{ request('sort') === 'follow_up_asc' ? 'selected' : '' }}>Follow-up (Soonest First)</option>
                        <option value="recently_updated" {{ request('sort') === 'recently_updated' ? 'selected' : '' }}>Recently Updated</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Lead Name (A-Z)</option>
                    </select>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800/60">
                <button type="submit"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-xs">
                    Apply Filters
                </button>
                @if(request()->hasAny(['search', 'status', 'priority', 'assigned_to', 'course_id', 'bundle_id', 'source', 'follow_up', 'sort']))
                    <a href="{{ route('admin.leads.index') }}"
                       class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Leads Data Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Lead &amp; Company</th>
                        <th scope="col" class="px-3 py-3.5">Interest &amp; Source</th>
                        <th scope="col" class="px-3 py-3.5">Priority &amp; Status</th>
                        <th scope="col" class="px-3 py-3.5">Follow-Up</th>
                        <th scope="col" class="px-3 py-3.5">Assigned To</th>
                        <th scope="col" class="px-3 py-3.5">Created</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- Lead & Company -->
                            <td class="py-4 pl-4 pr-3 sm:pl-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-800 font-bold text-xs text-amber-400 border border-slate-700">
                                        {{ strtoupper(substr($lead->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="font-bold text-white hover:text-amber-400 transition text-sm">
                                            {{ $lead->name }}
                                        </a>
                                        <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                            <a href="mailto:{{ $lead->email }}" class="hover:text-slate-300">{{ $lead->email }}</a>
                                            @if($lead->phone)
                                                <span>&bull;</span>
                                                <a href="tel:{{ $lead->phone }}" class="font-mono text-slate-400 hover:text-slate-300">{{ $lead->phone }}</a>
                                            @endif
                                        </div>
                                        @if($lead->company_name || $lead->job_title)
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $lead->job_title ? $lead->job_title . ' at ' : '' }}{{ $lead->company_name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Interest & Source -->
                            <td class="px-3 py-4 text-xs">
                                @if($lead->course)
                                    <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-0.5 text-[11px] font-semibold text-indigo-400 border border-indigo-500/20 max-w-[200px] truncate" title="{{ $lead->course->title }}">
                                        Course: {{ Str::limit($lead->course->title, 20) }}
                                    </span>
                                @elseif($lead->bundle)
                                    <span class="inline-flex items-center rounded-md bg-purple-500/10 px-2 py-0.5 text-[11px] font-semibold text-purple-400 border border-purple-500/20 max-w-[200px] truncate" title="{{ $lead->bundle->title }}">
                                        Bundle: {{ Str::limit($lead->bundle->title, 20) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">General Inquiry</span>
                                @endif
                                <div class="text-[10px] text-slate-400 mt-1 capitalize">
                                    Src: {{ str_replace('_', ' ', $lead->source) }}
                                </div>
                            </td>

                            <!-- Priority & Status -->
                            <td class="px-3 py-4 text-xs space-y-1">
                                <div>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold border {{ $lead->priority->badgeClasses() }}">
                                        {{ $lead->priority->label() }}
                                    </span>
                                </div>
                                <div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border {{ $lead->status->badgeClasses() }}">
                                        {{ $lead->status->label() }}
                                    </span>
                                </div>
                            </td>

                            <!-- Follow-Up -->
                            <td class="px-3 py-4 text-xs">
                                @if($lead->next_follow_up_at)
                                    @if($lead->isOverdue())
                                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-500/10 px-2 py-0.5 text-[11px] font-bold text-rose-400 border border-rose-500/20">
                                            Overdue: {{ $lead->next_follow_up_at->format('M d') }}
                                        </span>
                                    @elseif($lead->isDueToday())
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-[11px] font-bold text-amber-400 border border-amber-500/20">
                                            Due Today: {{ $lead->next_follow_up_at->format('h:i A') }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 text-[11px]">
                                            {{ $lead->next_follow_up_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>

                            <!-- Assigned To -->
                            <td class="px-3 py-4 text-xs">
                                @if($lead->assignedUser)
                                    <span class="font-semibold text-slate-200">
                                        {{ $lead->assignedUser->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Unassigned</span>
                                @endif
                            </td>

                            <!-- Created -->
                            <td class="px-3 py-4 text-xs text-slate-400">
                                <div>{{ $lead->created_at->format('M d, Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $lead->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 pl-3 pr-4 sm:pr-6 text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.leads.show', $lead) }}"
                                       class="rounded-lg bg-slate-800 px-2.5 py-1.5 font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                        Manage
                                    </a>
                                    <a href="{{ route('admin.leads.edit', $lead) }}"
                                       class="rounded-lg border border-slate-700 px-2 py-1.5 font-semibold text-slate-400 hover:text-white transition">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="mt-3 text-sm font-semibold text-slate-300">No leads found</p>
                                <p class="text-xs text-slate-500 mt-1">Try adjusting your filters or search keywords.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leads->hasPages())
            <div class="border-t border-slate-800 px-4 py-3 bg-slate-950/40">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
