@extends('admin.reports.layout', ['title' => 'Renewal Analytics & Lifecycle'])

@section('report_content')
<div class="space-y-6">
    <!-- Top Summary & Export -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Renewal Revenue (Paid Orders)</span>
            <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $summary['formatted_revenue'] }}</p>
            <p class="text-xs text-slate-500 mt-1">
                Across <span class="font-bold text-slate-700">{{ $summary['paid_renewal_orders'] }}</span> paid renewal transactions &bull; Overall Renewal Rate: <span class="font-bold text-slate-700">{{ $summary['renewal_rate'] }}%</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.export.renewals', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export Renewals CSV</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Renewal Rate</span>
            <p class="text-xl font-extrabold text-amber-600 mt-1">{{ $summary['renewal_rate'] }}%</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">Finite Expiry Cohort</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Early Renewals</span>
            <p class="text-xl font-extrabold text-emerald-600 mt-1">{{ $summary['early_renewals'] }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">Prior to Expiry</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Post-Expiry</span>
            <p class="text-xl font-extrabold text-indigo-600 mt-1">{{ $summary['post_expiry_renewals'] }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">After Expiration</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Expiring Soon</span>
            <p class="text-xl font-extrabold text-amber-600 mt-1">{{ $summary['expiring_soon'] }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">Within 30 Days</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Expired Unrenewed</span>
            <p class="text-xl font-extrabold text-rose-600 mt-1">{{ $summary['expired_unrenewed'] }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">Access Elapsed</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 text-center">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Finite Students</span>
            <p class="text-xl font-extrabold text-slate-700 mt-1">{{ $summary['finite_students'] }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">{{ $summary['lifetime_students'] }} Legacy Lifetime</span>
        </div>
    </div>

    <!-- Renewal Funnel & Latency Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Renewal Conversion Funnel -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Renewal Conversion Funnel</h3>
                    <p class="text-xs text-slate-500">Student lifecycle conversion through the renewal cycle</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full">
                    Overall Conversion: {{ $funnel['overall_conversion_rate'] }}%
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400">1. Eligible</span>
                    <p class="text-lg font-black text-slate-800 mt-1">{{ $funnel['eligible'] }}</p>
                    <span class="text-[10px] text-slate-500">Expiring/Expired</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400">2. Reminders</span>
                    <p class="text-lg font-black text-slate-800 mt-1">{{ $funnel['notifications_sent'] }}</p>
                    <span class="text-[10px] text-slate-500">Notifications</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400">3. Checkout</span>
                    <p class="text-lg font-black text-slate-800 mt-1">{{ $funnel['checkout_started'] }}</p>
                    <span class="text-[10px] text-slate-500">Started</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400">4. Paid</span>
                    <p class="text-lg font-black text-emerald-600 mt-1">{{ $funnel['payment_success'] }}</p>
                    <span class="text-[10px] text-slate-500">Success</span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/50 border border-emerald-100">
                    <span class="text-[10px] uppercase font-bold text-emerald-700">5. Fulfilled</span>
                    <p class="text-lg font-black text-emerald-700 mt-1">{{ $funnel['fulfilled'] }}</p>
                    <span class="text-[10px] text-emerald-600">Access Granted</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Checkout-to-Payment Rate: <strong class="text-slate-800">{{ $funnel['checkout_conversion_rate'] }}%</strong></span>
                <span>Cohort Completion Rate: <strong class="text-slate-800">{{ $funnel['overall_conversion_rate'] }}%</strong></span>
            </div>
        </div>

        <!-- Latency & Timing Overview -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-slate-900">Renewal Latency &amp; Lead Time</h3>
                    <p class="text-xs text-slate-500">Timing behavior when students renew course access</p>
                </div>

                <div class="space-y-4">
                    <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-200/60">
                        <span class="text-xs font-semibold text-emerald-800">Early Renewal Lead Time</span>
                        <p class="text-2xl font-black text-emerald-700 mt-1">{{ $latency['avg_early_lead_days'] }} <span class="text-xs font-normal text-emerald-600">days before expiry</span></p>
                        <p class="text-[11px] text-emerald-700 mt-0.5">Based on {{ $latency['early_count'] }} early renewal transactions</p>
                    </div>

                    <div class="p-3 rounded-xl bg-indigo-50/60 border border-indigo-200/60">
                        <span class="text-xs font-semibold text-indigo-800">Post-Expiry Delay</span>
                        <p class="text-2xl font-black text-indigo-700 mt-1">{{ $latency['avg_post_expiry_delay_days'] }} <span class="text-xs font-normal text-indigo-600">days after expiry</span></p>
                        <p class="text-[11px] text-indigo-700 mt-0.5">Based on {{ $latency['post_expiry_count'] }} post-expiry re-activations</p>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 mt-4 italic">
                * Early renewals automatically extend from current expiration; post-expiry renewals reset access from fulfillment time.
            </p>
        </div>
    </div>

    <!-- Course-Level Renewal Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Course Catalog Renewal Performance</h3>
                <p class="text-xs text-slate-500">Granular renewal metrics, revenue, and rates across individual courses</p>
            </div>
        </div>

        @if($courseMetrics->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No courses in catalog.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Course</th>
                            <th class="px-4 py-3.5 text-center">Finite Students</th>
                            <th class="px-4 py-3.5 text-center">Reaching Expiry</th>
                            <th class="px-4 py-3.5 text-center">Early / Post-Expiry</th>
                            <th class="px-4 py-3.5 text-center">Renewals</th>
                            <th class="px-4 py-3.5 text-center">Renewal Rate</th>
                            <th class="px-6 py-3.5 text-right">Renewal Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($courseMetrics as $row)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-3.5 font-medium text-slate-900">
                                    <a href="{{ route('admin.analytics.courses.show', $row['id']) }}" class="hover:text-amber-600 transition">
                                        {{ $row['title'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs font-semibold text-slate-700">
                                    {{ $row['finite_enrollments'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs text-slate-500">
                                    {{ $row['expiring_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs">
                                    <span class="text-emerald-700 font-semibold">{{ $row['early_renewals'] }}</span>
                                    <span class="text-slate-300 mx-1">/</span>
                                    <span class="text-indigo-700 font-semibold">{{ $row['post_expiry_renewals'] }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs font-bold text-slate-900">
                                    {{ $row['renewals_count'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $row['renewal_rate'] >= 50 ? 'bg-emerald-50 text-emerald-700' : ($row['renewal_rate'] >= 20 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $row['renewal_rate'] }}%
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-bold text-emerald-600">
                                    {{ $row['formatted_revenue'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Expiry Cohort Analysis Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Expiry Cohort Analysis (Last 6 Months)</h3>
                <p class="text-xs text-slate-500">Track renewal retention grouped by student expiration month (legacy lifetime strictly excluded)</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Cohort (Expiry Month)</th>
                        <th class="px-6 py-3.5 text-center">Cohort Size (Expiring Access)</th>
                        <th class="px-6 py-3.5 text-center">Renewals Retained</th>
                        <th class="px-6 py-3.5 text-center">Cohort Renewal Rate</th>
                        <th class="px-6 py-3.5 text-right">Cohort Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($cohorts as $cohort)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-3.5 font-semibold text-slate-900">
                                {{ $cohort['month_label'] }}
                            </td>
                            <td class="px-6 py-3.5 text-center text-xs font-medium text-slate-700">
                                {{ $cohort['cohort_size'] }}
                            </td>
                            <td class="px-6 py-3.5 text-center text-xs font-bold text-slate-900">
                                {{ $cohort['renewals_count'] }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $cohort['renewal_rate'] >= 50 ? 'bg-emerald-50 text-emerald-700' : ($cohort['renewal_rate'] >= 20 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $cohort['renewal_rate'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-emerald-600">
                                {{ $cohort['formatted_revenue'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Renewal Activity Stream -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Recent Renewal Transactions</h3>
                <p class="text-xs text-slate-500">Live feed of student renewal fulfillments and access extensions</p>
            </div>
        </div>

        @if($recentRenewals->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No recent renewal transactions recorded yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Student</th>
                            <th class="px-6 py-3.5">Course</th>
                            <th class="px-4 py-3.5 text-center">Order #</th>
                            <th class="px-4 py-3.5 text-center">Type</th>
                            <th class="px-4 py-3.5 text-center">Extended Through</th>
                            <th class="px-6 py-3.5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentRenewals as $act)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $act['student_name'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $act['student_email'] }}</p>
                                </td>
                                <td class="px-6 py-3.5 font-medium text-slate-800">
                                    {{ $act['course_title'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-mono text-xs text-slate-500">
                                    {{ $act['order_number'] }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $act['is_early'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                        {{ $act['type_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs text-slate-600">
                                    {{ $act['expires_at'] }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-bold text-emerald-600">
                                    {{ $act['formatted_amount'] }}
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
