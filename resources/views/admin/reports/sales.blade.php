@extends('admin.reports.layout', ['title' => 'Sales & Revenue Report'])

@section('report_content')
<div class="space-y-6">
    <!-- Top Summary & Export -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Sales Revenue</span>
            <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $summary['formatted_revenue'] }}</p>
            <p class="text-xs text-slate-500 mt-1">
                Across <span class="font-bold text-slate-700">{{ $summary['paid_orders_count'] }}</span> completed orders &bull; Average: <span class="font-bold text-slate-700">{{ $summary['formatted_aov'] }}</span>
            </p>
        </div>

        <a href="{{ route('admin.reports.export.sales', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Export Sales CSV</span>
        </a>
    </div>

    <!-- Order Status Distribution -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Paid Orders</span>
            <p class="text-xl font-extrabold text-emerald-600 mt-1">{{ $statusBreakdown['paid'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Pending</span>
            <p class="text-xl font-extrabold text-amber-600 mt-1">{{ $statusBreakdown['pending'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Failed</span>
            <p class="text-xl font-extrabold text-rose-600 mt-1">{{ $statusBreakdown['failed'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Cancelled</span>
            <p class="text-xl font-extrabold text-slate-600 mt-1">{{ $statusBreakdown['cancelled'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Refunded</span>
            <p class="text-xl font-extrabold text-purple-600 mt-1">{{ $statusBreakdown['refunded'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Daily Revenue Time-Series Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Periodic Revenue Breakdown</h3>
        </div>
        @if($dailyTrend->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No sales transactions in this date range.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5">Paid Orders</th>
                            <th class="px-6 py-3.5">Discounts Given</th>
                            <th class="px-6 py-3.5">Net Revenue Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($dailyTrend as $day)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $day['formatted_date'] }}</td>
                                <td class="px-6 py-4">{{ $day['orders_count'] }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-500">₹{{ number_format($day['discount'], 2) }}</td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">₹{{ number_format($day['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection