@extends('admin.reports.layout', ['title' => 'Enrollments & Completion Report'])

@section('report_content')
<div class="space-y-6">
    <!-- Header & Export -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Enrollments</span>
            <p class="text-3xl font-extrabold text-slate-900 mt-1">{{ $summary['total_enrollments'] }}</p>
            <p class="text-xs text-slate-500 mt-1">
                {{ $summary['active_enrollments'] }} active learners &bull; {{ $summary['completed_enrollments'] }} completed ({{ $summary['completion_rate'] }}% completion rate)
            </p>
        </div>

        <a href="{{ route('admin.reports.export.enrollments', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Export Enrollments CSV</span>
        </a>
    </div>

    <!-- Periodic Enrollment Trend Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Periodic Enrollment Intake</h3>
        </div>
        @if($dailyTrend->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No enrollments recorded in this period.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5">Total Intake</th>
                            <th class="px-6 py-3.5">Active Learners</th>
                            <th class="px-6 py-3.5">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($dailyTrend as $day)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $day['formatted_date'] }}</td>
                                <td class="px-6 py-4 font-bold text-indigo-600">{{ $day['total'] }}</td>
                                <td class="px-6 py-4">{{ $day['active'] }}</td>
                                <td class="px-6 py-4 text-emerald-600 font-semibold">{{ $day['completed'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Course Completions -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Recent Student Course Completions</h3>
        </div>
        @if($recentCompletions->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No course completions recorded yet.</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($recentCompletions as $enr)
                    <div class="p-4 hover:bg-slate-50/60 transition flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $enr->user?->name ?? 'Student' }}</p>
                            <p class="text-xs text-slate-500">{{ $enr->course?->title ?? 'Course' }} &bull; Completed {{ $enr->completed_at ? $enr->completed_at->format('M d, Y') : 'Recently' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Completed
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection