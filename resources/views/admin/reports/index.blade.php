@extends('admin.reports.layout', ['title' => 'Executive Overview'])

@section('report_content')
<div class="space-y-6">
    <!-- Quick Export Actions Strip -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Export Authoritative Data:</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.export.sales', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                <span>Sales CSV</span>
            </a>
            <a href="{{ route('admin.reports.export.courses', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                <span>Courses CSV</span>
            </a>
            <a href="{{ route('admin.reports.export.enrollments', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                <span>Enrollments CSV</span>
            </a>
        </div>
    </div>

    <!-- Primary KPIs Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <!-- Net Revenue -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Collected Revenue</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $summary['formatted_revenue'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Gross: {{ $summary['formatted_gross_sales'] }} &bull; Disc: {{ $summary['formatted_discount'] }}</p>
        </div>

        <!-- Paid Orders -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Paid Course Purchases</span>
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-indigo-600">{{ $summary['paid_orders_count'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Average Order Value: {{ $summary['formatted_aov'] }}</p>
        </div>

        <!-- Completion Rate -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Platform Completion Rate</span>
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-amber-600">{{ $summary['completion_rate'] }}%</p>
            <p class="mt-1 text-xs text-slate-500">{{ $summary['completed_enrollments'] }} completed of {{ $summary['total_enrollments'] }} enrolled</p>
        </div>

        <!-- Total Enrollments -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Enrollments</span>
                <span class="p-2 rounded-lg bg-sky-50 text-sky-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $summary['total_enrollments'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $summary['active_enrollments'] }} currently active students</p>
        </div>

        <!-- Student Registrations -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Student Growth</span>
                <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $summary['new_students'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Total registered: {{ $summary['total_students'] }} ({{ $summary['student_conversion_rate'] }}% converted to buyer)</p>
        </div>

        <!-- Certificates Issued -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Certificates Awarded</span>
                <span class="p-2 rounded-lg bg-teal-50 text-teal-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-teal-600">{{ $summary['certificates_issued'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Verified course credentials generated</p>
        </div>
    </div>

    <!-- Side-by-Side: Top Revenue Courses & Recent Paid Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Revenue Courses -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Top Revenue Courses</h3>
                <a href="{{ route('admin.reports.courses', request()->query()) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">
                    View All &rarr;
                </a>
            </div>
            @if($topCourses->isEmpty())
                <div class="p-8 text-center text-xs text-slate-500">No course transactions recorded in this period.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($topCourses as $c)
                        <div class="p-4 hover:bg-slate-50/60 transition flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $c['title'] }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $c['paid_orders_count'] }} purchases &bull; {{ $c['total_enrollments'] }} students ({{ $c['completion_rate'] }}% completed)</p>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-emerald-600">{{ $c['formatted_revenue'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Paid Orders -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Recent Paid Transactions</h3>
                <a href="{{ route('admin.reports.sales', request()->query()) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">
                    View All &rarr;
                </a>
            </div>
            @if($recentOrders->isEmpty())
                <div class="p-8 text-center text-xs text-slate-500">No paid orders recorded in this period.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentOrders as $order)
                        <div class="p-4 hover:bg-slate-50/60 transition flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $order->user?->name ?? 'Student' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $order->course?->title ?? 'Course' }} &bull; {{ $order->paid_at ? $order->paid_at->format('M d, Y') : 'Paid' }}</p>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-slate-900">{{ $order->formattedAmount() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection