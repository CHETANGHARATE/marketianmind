@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                    Marketing &amp; Growth
                </span>
                <span class="text-xs text-slate-400">Visitor-to-Student Pipeline</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
                Leads &amp; Inquiries
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                Manage prospective student inquiries, contact messages, and conversion opportunities.
            </p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Total Inquiries</span>
            <div class="text-2xl font-bold text-white mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">New / Uncontacted</span>
            <div class="text-2xl font-bold text-amber-400 mt-1">{{ number_format($stats['new']) }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Contacted</span>
            <div class="text-2xl font-bold text-sky-400 mt-1">{{ number_format($stats['contacted']) }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Converted to Students</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ number_format($stats['converted']) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.leads.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by name, email, phone, or subject..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
            </div>

            <div class="w-full sm:w-44">
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

            <div class="w-full sm:w-56">
                <select name="course_id"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ Str::limit($c->title, 28) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'course_id']))
                    <a href="{{ route('admin.leads.index') }}"
                       class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Leads Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Lead</th>
                        <th scope="col" class="px-3 py-3.5">Course / Source</th>
                        <th scope="col" class="px-3 py-3.5">Status</th>
                        <th scope="col" class="px-3 py-3.5">Received</th>
                        <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-4 pl-4 pr-3 sm:pl-6">
                                <div class="font-bold text-white">
                                    {{ $lead->name }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    <a href="mailto:{{ $lead->email }}" class="hover:text-amber-400 underline">{{ $lead->email }}</a>
                                    @if($lead->phone)
                                        &bull; <span class="text-slate-500 font-mono">{{ $lead->phone }}</span>
                                    @endif
                                </div>
                                @if($lead->subject)
                                    <div class="text-xs text-slate-500 mt-0.5 truncate max-w-xs">
                                        {{ $lead->subject }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-4 text-xs">
                                @if($lead->course)
                                    <span class="font-semibold text-slate-200 block">
                                        {{ Str::limit($lead->course->title, 32) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">General Inquiry</span>
                                @endif
                                <span class="text-[11px] text-slate-500 uppercase tracking-wider block mt-0.5">
                                    Source: {{ str_replace('_', ' ', $lead->source) }}
                                </span>
                            </td>

                            <td class="px-3 py-4 text-xs">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold border {{ $lead->status->badgeClasses() }}">
                                    {{ $lead->status->label() }}
                                </span>
                            </td>

                            <td class="px-3 py-4 text-xs text-slate-400 whitespace-nowrap">
                                {{ $lead->created_at->diffForHumans() }}
                            </td>

                            <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.leads.show', $lead) }}"
                                       class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
                                        View &amp; Notes
                                    </a>

                                    <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Are you sure you want to delete inquiry from &quot;{{ $lead->name }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg bg-rose-500/10 px-2.5 py-1.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-xs text-slate-500">
                                No lead inquiries found matching the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leads->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection