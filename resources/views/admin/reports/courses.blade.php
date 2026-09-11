@extends('admin.reports.layout', ['title' => 'Course Performance Report'])

@section('report_content')
<div class="space-y-6">
    <!-- Header & Export -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
        <div>
            <h2 class="text-base font-bold text-slate-900">Course Catalog Financial &amp; Academic Performance</h2>
            <p class="text-xs text-slate-500 mt-0.5">Authoritative revenue attribution, student enrollments, and verified completion rates.</p>
        </div>

        <a href="{{ route('admin.reports.export.courses', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Export Courses CSV</span>
        </a>
    </div>

    <!-- Courses Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($coursesReport->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No courses available in catalog.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Course</th>
                            <th class="px-6 py-3.5">Price</th>
                            <th class="px-6 py-3.5">Paid Orders</th>
                            <th class="px-6 py-3.5">Net Revenue</th>
                            <th class="px-6 py-3.5">Discounts</th>
                            <th class="px-6 py-3.5">Enrollments</th>
                            <th class="px-6 py-3.5">Completion Rate</th>
                            <th class="px-6 py-3.5">Certificates</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($coursesReport as $c)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $c['title'] }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $c['slug'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    {{ $c['is_free'] ? 'Free' : '₹' . number_format($c['price'], 2) }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $c['paid_orders_count'] }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    {{ $c['formatted_revenue'] }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    ₹{{ number_format($c['discount'], 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900">{{ $c['total_enrollments'] }}</span>
                                    <span class="text-xs text-slate-400">({{ $c['active_enrollments'] }} act)</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, $c['completion_rate']) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-800">{{ $c['completion_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-teal-600">
                                    {{ $c['certificates_issued'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection