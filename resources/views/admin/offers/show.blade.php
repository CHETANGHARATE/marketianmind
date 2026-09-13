@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.offers.index') }}" class="text-xs text-amber-400 hover:underline">
                    &larr; Back to Offers
                </a>
                <span class="text-xs text-slate-500">/</span>
                <span class="text-xs text-slate-400">Offer Details</span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                    {{ $offer->name }}
                </h1>
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold border {{ $offer->status()->badgeClasses() }}">
                    {{ $offer->status()->label() }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                    {{ $offer->formattedDiscount() }}
                </span>
            </div>
            @if($offer->description)
                <p class="mt-1 text-sm text-slate-400 max-w-3xl">
                    {{ $offer->description }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.offers.edit', $offer) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition cursor-pointer">
                Edit Offer
            </a>
        </div>
    </div>

    <!-- Performance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Orders</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($offer->orders->count()) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Redemptions: {{ $offer->times_used }} {{ $offer->usage_limit ? "/ {$offer->usage_limit} limit" : '' }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Net Revenue</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">₹{{ number_format($totalRevenuePaise / 100, 2) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Collected from promo orders</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Customer Savings Provided</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">₹{{ number_format($totalDiscountPaise / 100, 2) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Total discount amount granted</div>
        </div>
    </div>

    <!-- Rules & Configuration Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Configuration Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-3">Offer Configuration</h3>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 block">Discount Type:</span>
                    <span class="font-semibold text-white">{{ $offer->discount_type->label() }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Discount Amount:</span>
                    <span class="font-semibold text-amber-400">{{ $offer->formattedDiscount() }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Priority Level:</span>
                    <span class="font-semibold text-white">{{ $offer->priority }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Coupon Stacking:</span>
                    <span class="font-semibold {{ $offer->allow_coupons ? 'text-emerald-400' : 'text-slate-400' }}">
                        {{ $offer->allow_coupons ? 'Allowed' : 'Prohibited' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block">Badge Tag:</span>
                    <span class="font-semibold text-white">{{ $offer->badge_text ?? 'None' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Status:</span>
                    <span class="font-semibold {{ $offer->is_active ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $offer->is_active ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Schedule Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-3">Campaign Schedule</h3>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-slate-800/60">
                    <span class="text-slate-500">Starts At:</span>
                    <span class="font-semibold text-white">{{ $offer->starts_at ? $offer->starts_at->format('M d, Y h:i A') : 'Immediate Launch' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-800/60">
                    <span class="text-slate-500">Ends At:</span>
                    <span class="font-semibold text-white">{{ $offer->ends_at ? $offer->ends_at->format('M d, Y h:i A') : 'No Expiry Date' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-800/60">
                    <span class="text-slate-500">Usage Limit:</span>
                    <span class="font-semibold text-white">{{ $offer->usage_limit ? "{$offer->usage_limit} redemptions" : 'Unlimited' }}</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500">Times Used:</span>
                    <span class="font-semibold text-white">{{ $offer->times_used }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Eligible Products Grid -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-5">
        <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-3">
            Eligible Products ({{ $offer->courses->count() + $offer->bundles->count() }})
        </h3>

        @if($offer->courses->count() > 0)
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Courses ({{ $offer->courses->count() }})</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($offer->courses as $course)
                        @php
                            $pricing = app(\App\Services\PricingService::class)->resolveForProduct($course);
                        @endphp
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4 space-y-2">
                            <div class="font-bold text-white text-xs truncate">{{ $course->title }}</div>
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-800/80">
                                <div>
                                    <span class="text-slate-500 line-through text-[11px]">{{ $course->formattedPrice() }}</span>
                                    <span class="text-emerald-400 font-bold ml-1.5">{{ $pricing['formatted_final_price'] }}</span>
                                </div>
                                <a href="{{ route('courses.show', $course) }}" target="_blank" class="text-amber-400 text-[11px] hover:underline">
                                    Preview &nearr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($offer->bundles->count() > 0)
            <div class="pt-4 border-t border-slate-800">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Course Bundles ({{ $offer->bundles->count() }})</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($offer->bundles as $bundle)
                        @php
                            $pricing = app(\App\Services\PricingService::class)->resolveForProduct($bundle);
                        @endphp
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4 space-y-2">
                            <div class="font-bold text-white text-xs truncate">{{ $bundle->title }}</div>
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-800/80">
                                <div>
                                    <span class="text-slate-500 line-through text-[11px]">{{ $bundle->formattedPrice() }}</span>
                                    <span class="text-emerald-400 font-bold ml-1.5">{{ $pricing['formatted_final_price'] }}</span>
                                </div>
                                <a href="{{ route('bundles.show', $bundle) }}" target="_blank" class="text-amber-400 text-[11px] hover:underline">
                                    Preview &nearr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Recent Orders Placed with this Offer -->
    @if($offer->orders->count() > 0)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Recent Orders Using This Offer</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-left text-xs">
                    <thead class="bg-slate-950/80 text-slate-400 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Order #</th>
                            <th class="px-6 py-3">Student</th>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Offer Discount</th>
                            <th class="px-6 py-3">Paid Amount</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($offer->orders as $order)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-6 py-3 font-mono font-bold text-white">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-amber-400">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-slate-300">{{ $order->user?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-slate-300">{{ $order->productTitle() }}</td>
                                <td class="px-6 py-3 text-amber-400 font-semibold">{{ $order->formattedOfferDiscountAmount() }}</td>
                                <td class="px-6 py-3 text-white font-bold">{{ $order->formattedAmount() }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $order->status->badgeClasses() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
