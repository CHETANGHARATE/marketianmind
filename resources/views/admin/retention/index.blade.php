@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Phase 11.10
                </span>
                <span class="text-xs text-slate-500">Learner Progression &amp; Retention Hub</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Retention &amp; Renewal Optimization
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Identify students needing learning support, track 365-day access validity milestones, and facilitate manual course renewals.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.reports.renewals') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Renewals Report</span>
            </a>

            <a href="{{ route('admin.retention.export', request()->query()) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export Cohort CSV</span>
            </a>
        </div>
    </div>

    <!-- Notifications / Alerts -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-400">
            {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-semibold text-rose-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Top Retention KPI Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Active Learners -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Active (14d)</span>
            <p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ number_format($summary['active_learners_14d']) }}</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">{{ $summary['retention_rate'] }}% 14d retention</span>
        </div>

        <!-- 2. Purchased Not Started -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Not Started (3d+)</span>
            <p class="text-2xl font-extrabold text-purple-400 mt-1">{{ number_format($summary['not_started_count']) }}</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">Needs Kick-start</span>
        </div>

        <!-- 3. Started Inactive -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Inactive (14d+)</span>
            <p class="text-2xl font-extrabold text-orange-400 mt-1">{{ number_format($summary['inactive_learners_count']) }}</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">Needs Re-engagement</span>
        </div>

        <!-- 4. Approaching Completion -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Nearing 100%</span>
            <p class="text-2xl font-extrabold text-teal-400 mt-1">{{ number_format($summary['approaching_completion_count']) }}</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">&ge;80% complete</span>
        </div>

        <!-- 5. Expiring Soon -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Expiring Soon</span>
            <p class="text-2xl font-extrabold text-amber-400 mt-1">{{ number_format($summary['expiring_soon_count']) }}</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">&le;30 days validity</span>
        </div>

        <!-- 6. Renewal Rate -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Renewal Rate</span>
            <p class="text-2xl font-extrabold text-indigo-400 mt-1">{{ $summary['renewal_rate'] }}%</p>
            <span class="text-[10px] text-slate-500 block mt-0.5">{{ number_format($summary['renewed_count']) }} renewed</span>
        </div>
    </div>

    <!-- Cohort Filter Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
        @php
            $cohortTabs = [
                'all' => 'All Enrollments (' . number_format($summary['total_enrollments']) . ')',
                'not_started' => 'Not Started 3d+ (' . number_format($summary['not_started_count']) . ')',
                'inactive' => 'Inactive 14d+ (' . number_format($summary['inactive_learners_count']) . ')',
                'approaching_completion' => 'Nearing 100% (' . number_format($summary['approaching_completion_count']) . ')',
                'expiring_soon' => 'Expiring Soon (' . number_format($summary['expiring_soon_count']) . ')',
                'expired' => 'Expired Access (' . number_format($summary['expired_count']) . ')',
                'completed' => 'Completed (' . number_format($summary['completed_count']) . ')',
                'renewed' => 'Renewed (' . number_format($summary['renewed_count']) . ')',
            ];
        @endphp

        @foreach($cohortTabs as $key => $label)
            <a href="{{ route('admin.retention.index', array_merge(request()->query(), ['cohort' => $key, 'page' => 1])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $cohort === $key ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Search & Filter Controls -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.retention.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="hidden" name="cohort" value="{{ $cohort }}">

            <!-- Student Search -->
            <div>
                <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Search Student</label>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Student name or email..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none">
            </div>

            <!-- Course Filter -->
            <div>
                <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Filter by Course</label>
                <select name="course_id"
                        class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ $courseId == $c->id ? 'selected' : '' }}>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-xs font-bold text-slate-950 transition cursor-pointer">
                    Apply Filter
                </button>
                @if($search || $courseId || $cohort !== 'all')
                    <a href="{{ route('admin.retention.index') }}"
                       class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Student Cohort Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-800 bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="py-3 px-4">Student</th>
                        <th class="py-3 px-4">Course</th>
                        <th class="py-3 px-4">Progress</th>
                        <th class="py-3 px-4">Access Validity</th>
                        <th class="py-3 px-4">Retention Signal</th>
                        <th class="py-3 px-4 text-right">Support Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    @forelse($students as $item)
                        @php
                            $prog = $item->calculated_progress ?? ['percentage' => 0, 'completed' => 0, 'total' => 0];
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Student Info -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">
                                    <a href="{{ route('admin.students.show', $item->user) }}" class="hover:text-amber-400 transition">
                                        {{ $item->user?->name ?? 'Unknown Student' }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-400">{{ $item->user?->email }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">Enrolled {{ $item->enrolled_at?->format('M d, Y') ?? 'N/A' }}</div>
                            </td>

                            <!-- Course Info -->
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-200">{{ $item->course?->title }}</div>
                                @if($item->course?->category)
                                    <span class="text-[10px] text-amber-400 font-semibold uppercase">{{ $item->course->category->name }}</span>
                                @endif
                            </td>

                            <!-- Progress Bar -->
                            <td class="py-3.5 px-4">
                                <div class="w-32 space-y-1">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-200">{{ $prog['percentage'] }}%</span>
                                        <span class="text-slate-500 text-[10px]">{{ $prog['completed'] }}/{{ $prog['total'] }}</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $prog['percentage'] >= 100 ? 'bg-emerald-500' : ($prog['percentage'] >= 80 ? 'bg-teal-500' : ($prog['percentage'] > 0 ? 'bg-amber-500' : 'bg-slate-700')) }}" style="width: {{ $prog['percentage'] }}%"></div>
                                    </div>
                                    @if($item->last_activity_at)
                                        <span class="text-[10px] text-slate-500 block">
                                            Active {{ \Carbon\Carbon::parse($item->last_activity_at)->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-500 block">No lessons completed</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Access Validity -->
                            <td class="py-3.5 px-4">
                                @if($item->getAccessState() === 'lifetime')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Lifetime Access
                                    </span>
                                @elseif($item->getAccessState() === 'expired')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Expired ({{ $item->getFormattedExpiryDate() }})
                                    </span>
                                @elseif($item->getAccessState() === 'expiring')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        {{ $item->getRemainingDaysText() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300">
                                        {{ $item->getRemainingDaysText() }}
                                    </span>
                                @endif
                                @if($item->has_renewed)
                                    <span class="inline-flex items-center gap-1 text-[10px] text-indigo-400 font-semibold block mt-1">
                                        ✓ Renewed Access
                                    </span>
                                @endif
                            </td>

                            <!-- Retention Signal Badge -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold
                                    @if($item->retention_signal_color === 'purple') bg-purple-500/15 text-purple-300 border border-purple-500/30
                                    @elseif($item->retention_signal_color === 'orange') bg-orange-500/15 text-orange-300 border border-orange-500/30
                                    @elseif($item->retention_signal_color === 'teal') bg-teal-500/15 text-teal-300 border border-teal-500/30
                                    @elseif($item->retention_signal_color === 'amber') bg-amber-500/15 text-amber-300 border border-amber-500/30
                                    @elseif($item->retention_signal_color === 'rose') bg-rose-500/15 text-rose-300 border border-rose-500/30
                                    @elseif($item->retention_signal_color === 'indigo') bg-indigo-500/15 text-indigo-300 border border-indigo-500/30
                                    @else bg-emerald-500/15 text-emerald-300 border border-emerald-500/30
                                    @endif">
                                    {{ $item->retention_signal }}
                                </span>
                            </td>

                            <!-- Action Button / Form -->
                            <td class="py-3.5 px-4 text-right">
                                @if($item->suggested_action)
                                    <form action="{{ route('admin.retention.support', ['student' => $item->user_id, 'course' => $item->course_id]) }}" method="POST" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="support_type" value="{{ $item->suggested_action }}">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer
                                                @if($item->suggested_action === 'kickstart') bg-purple-600 hover:bg-purple-500 text-white
                                                @elseif($item->suggested_action === 'reengagement') bg-orange-600 hover:bg-orange-500 text-white
                                                @elseif($item->suggested_action === 'completion_push') bg-teal-600 hover:bg-teal-500 text-white
                                                @else bg-amber-600 hover:bg-amber-500 text-white
                                                @endif">
                                            <span>
                                                @if($item->suggested_action === 'kickstart') Send Kick-start
                                                @elseif($item->suggested_action === 'reengagement') Re-engage
                                                @elseif($item->suggested_action === 'completion_push') Push to Finish
                                                @else Renewal Note
                                                @endif
                                            </span>
                                            &rarr;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-500 italic">No action required</span>
                                @endif
                                @if($item->recent_support)
                                    <span class="text-[10px] text-slate-500 block mt-1">
                                        Last sent {{ $item->recent_support->sent_at?->diffForHumans() ?? 'recently' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-400">
                                No student enrollments found matching the current filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
