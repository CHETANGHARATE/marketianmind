@extends('admin.reports.layout', ['title' => 'Coupon Performance Report'])

@section('report_content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
        <h2 class="text-base font-bold text-slate-900">Promotional Coupon Performance</h2>
        <p class="text-xs text-slate-500 mt-0.5">Track discount concessions, redemptions, and revenue stimulated through promotional codes.</p>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($couponsReport->isEmpty())
            <div class="p-8 text-center text-xs text-slate-500">No coupons recorded in the database.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Code</th>
                            <th class="px-6 py-3.5">Type &amp; Value</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Redemptions</th>
                            <th class="px-6 py-3.5">Total Discount Given</th>
                            <th class="px-6 py-3.5">Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($couponsReport as $c)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600 text-xs">
                                    {{ $c['code'] }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ ucfirst($c['type'] instanceof \App\Enums\CouponDiscountType ? $c['type']->value : $c['type']) }}: {{ $c['value'] }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $c['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $c['is_active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $c['orders_count'] }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                    {{ $c['formatted_discount'] }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    {{ $c['formatted_revenue'] }}
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