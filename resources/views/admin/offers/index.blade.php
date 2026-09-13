@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Commercial &amp; Pricing
                </span>
                <span class="text-xs text-slate-500">Advanced Pricing &amp; Offers</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Promotional Offers
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Create and manage automated time-bound discounts, bundle promotions, priority rules, and product-specific offer pricing.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.offers.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Offer
            </a>
        </div>
    </div>

    <!-- Session Feedback -->
    @if(session('status'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-xs font-medium text-emerald-400 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Total Offers</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ number_format($totalOffers) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Configured in system</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Active Offers</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($activeOffers) }}</div>
            <div class="mt-1 text-[11px] text-emerald-500/80">Live &amp; affecting pricing</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Scheduled Offers</div>
            <div class="mt-2 text-2xl font-bold text-blue-400">{{ number_format($scheduledOffers) }}</div>
            <div class="mt-1 text-[11px] text-blue-500/80">Upcoming launches</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="text-xs font-medium text-slate-400">Orders Under Offer</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($totalOrdersCount) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Orders placed with promo</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.offers.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
            <!-- Search -->
            <div class="sm:col-span-6">
                <label for="search" class="sr-only">Search</label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Search by offer name, badge, or description..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-4">
                <select name="status"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses (Active, Scheduled, Expired, Disabled)</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Live)</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled (Future)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired / Limit Reached</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled (Inactive)</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-2.5 text-xs font-semibold transition text-center cursor-pointer">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.offers.index') }}"
                       class="rounded-xl bg-slate-900 border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-white px-3 py-2.5 text-xs font-semibold transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Offers Table Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-left text-xs">
                <thead class="bg-slate-950/80 text-slate-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Offer &amp; Badge</th>
                        <th class="px-6 py-3.5">Discount</th>
                        <th class="px-6 py-3.5">Target Products</th>
                        <th class="px-6 py-3.5">Schedule</th>
                        <th class="px-6 py-3.5">Priority</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($offers as $offer)
                        @php
                            $status = $offer->status();
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">
                                    <a href="{{ route('admin.offers.show', $offer) }}" class="hover:text-amber-400 transition">
                                        {{ $offer->name }}
                                    </a>
                                </div>
                                <div class="mt-0.5 flex items-center gap-2">
                                    <span class="text-[11px] text-slate-500 font-mono">{{ $offer->slug }}</span>
                                    @if($offer->badge_text)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            {{ $offer->badge_text }}
                                        </span>
                                    @endif
                                    @if($offer->allow_coupons)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            + Coupons Allowed
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-white">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                    {{ $offer->formattedDiscount() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-white">{{ $offer->courses_count + $offer->bundles_count }}</span>
                                    <span class="text-slate-500">
                                        ({{ $offer->courses_count }} {{ Str::plural('Course', $offer->courses_count) }}, {{ $offer->bundles_count }} {{ Str::plural('Bundle', $offer->bundles_count) }})
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 whitespace-nowrap text-[11px]">
                                @if($offer->starts_at || $offer->ends_at)
                                    <div>{{ $offer->starts_at ? $offer->starts_at->format('M d, Y H:i') : 'Immediate' }}</div>
                                    <div class="text-slate-500">&rarr; {{ $offer->ends_at ? $offer->ends_at->format('M d, Y H:i') : 'No Expiration' }}</div>
                                @else
                                    <span class="text-slate-500">Always Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-300 font-mono">
                                <span class="font-bold text-white">{{ $offer->priority }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold border {{ $status->badgeClasses() }}">
                                    {{ $status->label() }}
                                </span>
                                @if($offer->usage_limit)
                                    <div class="text-[10px] text-slate-500 mt-1">
                                        {{ $offer->times_used }} / {{ $offer->usage_limit }} used
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.offers.show', $offer) }}"
                                       class="rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                        View
                                    </a>
                                    <a href="{{ route('admin.offers.edit', $offer) }}"
                                       class="rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.offers.toggle', $offer) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-xs font-semibold {{ $offer->is_active ? 'text-amber-400 hover:bg-amber-400/10' : 'text-emerald-400 hover:bg-emerald-400/10' }} transition cursor-pointer">
                                            {{ $offer->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this promotional offer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg border border-red-500/20 bg-red-500/10 px-2.5 py-1.5 text-xs font-semibold text-red-400 hover:bg-red-500/20 transition cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-800/50 text-slate-400 mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white">No promotional offers found</h3>
                                <p class="mt-1 text-xs text-slate-400">Get started by creating your first automated promotional offer.</p>
                                <div class="mt-4">
                                    <a href="{{ route('admin.offers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                                        Create First Offer &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($offers->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $offers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
