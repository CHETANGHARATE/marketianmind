@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Learner Directory
                </span>
                <span class="text-xs text-slate-500">Student Account Management</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Students
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Manage, search, and review registered student learners across Marketian Mind.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($counts['all']) }}</span> Total Students
            </span>
        </div>
    </div>

    <!-- Filter Tabs & Controls -->
    <div class="space-y-4">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800/80 pb-3">
            <a href="{{ route('admin.students.index', array_merge(request()->except(['filter', 'page']), ['filter' => 'all'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $filter === 'all' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                All Students ({{ number_format($counts['all']) }})
            </a>
            <a href="{{ route('admin.students.index', array_merge(request()->except(['filter', 'page']), ['filter' => 'with_enrollments'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $filter === 'with_enrollments' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                With Enrollments ({{ number_format($counts['with_enrollments']) }})
            </a>
            <a href="{{ route('admin.students.index', array_merge(request()->except(['filter', 'page']), ['filter' => 'without_enrollments'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $filter === 'without_enrollments' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Without Enrollments ({{ number_format($counts['without_enrollments']) }})
            </a>
            <a href="{{ route('admin.students.index', array_merge(request()->except(['filter', 'page']), ['filter' => 'recent'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $filter === 'recent' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                New (Last 30 Days) ({{ number_format($counts['recent']) }})
            </a>
        </div>

        <!-- Search & Sort Controls -->
        <form method="GET" action="{{ route('admin.students.index') }}" class="flex flex-col sm:flex-row gap-3">
            @if($filter !== 'all')
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif

            <!-- Search Input -->
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search students by name or email..."
                       class="w-full rounded-xl border border-slate-800 bg-slate-900/90 pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @if($search !== '')
                    <a href="{{ route('admin.students.index', request()->except(['search', 'page'])) }}"
                       class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white"
                       title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>

            <!-- Sort Select -->
            <div class="sm:w-56">
                <select name="sort"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest Registered</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest Registered</option>
                    <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Name (A &rarr; Z)</option>
                    <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Name (Z &rarr; A)</option>
                    <option value="enrollments_desc" {{ $sort === 'enrollments_desc' ? 'selected' : '' }}>Most Enrollments</option>
                </select>
            </div>

            <button type="submit"
                    class="rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                Search
            </button>
        </form>
    </div>

    <!-- Students Table Container -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm overflow-hidden">
        @if($students->isEmpty())
            <div class="py-16 text-center px-4">
                <div class="mx-auto w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">No students found</h3>
                <p class="mt-1 text-xs text-slate-400">
                    @if($search !== '' || $filter !== 'all')
                        No students matched your search criteria. Try adjusting your filters.
                    @else
                        No registered students are present in the platform directory.
                    @endif
                </p>
                @if($search !== '' || $filter !== 'all')
                    <div class="mt-4">
                        <a href="{{ route('admin.students.index') }}"
                           class="inline-flex items-center rounded-lg bg-amber-500/10 border border-amber-500/30 px-3 py-1.5 text-xs font-semibold text-amber-400 hover:bg-amber-500/20 transition">
                            Reset Filters
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                            <th class="py-3.5 pl-6 pr-4">Student</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-center">Enrollments</th>
                            <th class="py-3.5 px-4 text-center">Paid Orders</th>
                            <th class="py-3.5 px-4">Joined Date</th>
                            <th class="py-3.5 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($students as $student)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-4 pl-6 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-amber-500/20 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/30">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.students.show', $student) }}"
                                               class="font-bold text-white hover:text-amber-400 transition block truncate">
                                                {{ $student->name }}
                                            </a>
                                            <p class="text-[11px] text-slate-400 truncate">
                                                {{ $student->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    @if($student->email_verified_at)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/30">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($student->enrollments_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            {{ $student->enrollments_count }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-xs font-medium">0</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($student->paid_orders_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            {{ $student->paid_orders_count }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-xs font-medium">0</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <div>{{ $student->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $student->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="py-4 pr-6 text-right">
                                    <a href="{{ route('admin.students.show', $student) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-amber-500/50 hover:bg-slate-700 hover:text-white transition">
                                        <span>Inspect</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($students->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    {{ $students->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection