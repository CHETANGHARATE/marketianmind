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
                <span class="text-xs text-slate-500">Email Templates</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Marketing Email Templates
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Design reusable email templates with dynamic personalization tags for automated workflows.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.automations.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                &larr; Back to Automations
            </a>
            <a href="{{ route('admin.marketing-templates.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Template
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Search Form -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.marketing-templates.index') }}" class="flex gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search templates by name, slug, or subject..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl transition">
                Search
            </button>
            @if(request('search'))
                <a href="{{ route('admin.marketing-templates.index') }}" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-xs text-slate-400 hover:text-white rounded-xl transition flex items-center">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Templates Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Template Name &amp; Slug</th>
                        <th class="py-3 px-4 font-semibold">Subject Line</th>
                        <th class="py-3 px-4 font-semibold">Used By</th>
                        <th class="py-3 px-4 font-semibold">Created</th>
                        <th class="py-3 px-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($templates as $tpl)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white text-sm">{{ $tpl->name }}</div>
                                <div class="font-mono text-[11px] text-slate-500 mt-0.5">{{ $tpl->slug }}</div>
                                @if($tpl->description)
                                    <div class="text-[11px] text-slate-400 mt-1 max-w-md truncate">{{ $tpl->description }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-slate-200 font-medium">{{ $tpl->subject }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-800 text-indigo-400 border border-slate-700">
                                    {{ $tpl->automations_count }} {{ Str::plural('automation', $tpl->automations_count) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400">
                                {{ $tpl->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.marketing-templates.edit', $tpl) }}"
                                       class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 font-semibold text-xs transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.marketing-templates.destroy', $tpl) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this template?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 font-semibold text-xs border border-rose-500/20 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                <p class="text-sm">No marketing templates found.</p>
                                <a href="{{ route('admin.marketing-templates.create') }}" class="mt-3 inline-block text-xs font-semibold text-indigo-400 hover:underline">
                                    Create your first template &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
