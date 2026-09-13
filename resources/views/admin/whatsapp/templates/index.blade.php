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
                    Templates
                </span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                WhatsApp Message Templates
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Pre-approved Meta templates for transactional receipts and opted-in marketing communications.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.whatsapp.templates.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Template
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-xs font-medium text-emerald-400 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
        <form method="GET" action="{{ route('admin.whatsapp.templates.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search templates..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <select name="category" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                            {{ ucfirst($cat->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ route('admin.whatsapp.templates.index') }}" class="rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Templates Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="border-b border-slate-800 bg-slate-800/30 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4">Template Details</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Language</th>
                        <th class="py-3 px-4">Variables</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($templates as $template)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4">
                                <span class="font-bold text-white text-sm block">{{ $template->name }}</span>
                                <span class="font-mono text-[11px] text-emerald-400">{{ $template->template_name }}</span>
                                <p class="mt-1 text-slate-400 line-clamp-2">{{ Str::limit($template->body, 120) }}</p>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium border {{ $template->category->badgeClasses() }}">
                                    {{ $template->category->label() }}
                                </span>
                            </td>
                            <td class="py-4 px-4 uppercase font-mono text-slate-400">
                                {{ $template->language }}
                            </td>
                            <td class="py-4 px-4">
                                @if(!empty($template->variables))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($template->variables as $v)
                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-300">
                                                &#123;&#123;&nbsp;{{ $v }}&nbsp;&#125;&#125;
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-500">None</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <form action="{{ route('admin.whatsapp.templates.toggle', $template) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium border {{ $template->is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-700/30 text-slate-500 border-slate-700/50' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-4 px-4 text-right space-x-2">
                                <a href="{{ route('admin.whatsapp.templates.edit', $template) }}"
                                   class="rounded-lg bg-slate-800 hover:bg-slate-700 px-2.5 py-1 text-xs font-medium text-slate-300 transition">
                                    Edit
                                </a>
                                <form action="{{ route('admin.whatsapp.templates.destroy', $template) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500/10 hover:bg-rose-500/20 px-2.5 py-1 text-xs font-medium text-rose-400 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                No WhatsApp templates found. Create your first approved template!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
