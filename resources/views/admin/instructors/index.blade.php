@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-0.5 text-xs font-bold text-indigo-400 border border-indigo-500/20">
                    Instructors
                </span>
                <span class="text-xs text-slate-400">Platform Faculty &amp; Practitioners</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
                Instructor Management
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                Manage instructor profiles, biographies, credentials, and course assignments.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.instructors.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Add Instructor
            </a>
        </div>
    </div>

    <!-- Stats 3-Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Total Instructors</span>
            <div class="text-2xl font-bold text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Active Faculty</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs font-semibold text-slate-400">Inactive</span>
            <div class="text-2xl font-bold text-slate-400 mt-1">{{ $stats['inactive'] }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.instructors.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by instructor name, title, or slug..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
            </div>

            <div class="w-full sm:w-44">
                <select name="status"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                    Filter
                </button>
                @if($search || $status !== null)
                    <a href="{{ route('admin.instructors.index') }}"
                       class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-medium text-slate-400 hover:text-white border border-slate-800 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Instructors Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
        @if($instructors->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Instructor</th>
                            <th scope="col" class="px-3 py-3.5">Headline / Title</th>
                            <th scope="col" class="px-3 py-3.5">Courses</th>
                            <th scope="col" class="px-3 py-3.5">Status</th>
                            <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($instructors as $instructor)
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Instructor Avatar & Name -->
                                <td class="py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $instructor->avatarUrl() }}"
                                             alt="{{ $instructor->name }}"
                                             class="h-10 w-10 rounded-xl object-cover bg-slate-800 border border-slate-700 shrink-0">
                                        <div>
                                            <div class="font-bold text-white">
                                                {{ $instructor->name }}
                                            </div>
                                            <div class="text-xs text-slate-500 font-mono">
                                                /instructors/{{ $instructor->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Title -->
                                <td class="px-3 py-4 text-xs text-slate-300">
                                    {{ $instructor->title ?: '—' }}
                                </td>

                                <!-- Courses Count -->
                                <td class="px-3 py-4 text-xs font-semibold text-slate-300">
                                    <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-0.5 text-indigo-400 border border-indigo-500/20">
                                        {{ $instructor->courses_count }} {{ \Illuminate\Support\Str::plural('Course', $instructor->courses_count) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-4 text-xs">
                                    <form method="POST" action="{{ route('admin.instructors.toggle', $instructor) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold border transition cursor-pointer {{ $instructor->is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-slate-700' }}"
                                                title="Click to toggle status">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $instructor->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                            {{ $instructor->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.instructors.edit', $instructor) }}"
                                           class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.instructors.destroy', $instructor) }}" onsubmit="return confirm('Are you sure you want to delete instructor &quot;{{ $instructor->name }}&quot;?');">
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
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($instructors->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    {{ $instructors->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center text-slate-400">
                <svg class="h-12 w-12 mx-auto text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h3 class="text-base font-bold text-white">No instructors found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Add your first course instructor or adjust your search filter.
                </p>
                <div class="mt-4">
                    <a href="{{ route('admin.instructors.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                        + Add First Instructor
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection