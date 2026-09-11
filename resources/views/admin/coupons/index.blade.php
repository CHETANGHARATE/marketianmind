@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Marketing & Growth
                </span>
                <span class="text-xs text-slate-500">Promotions & Discounts</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Coupons & Discounts
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Create and manage promotional discount codes, redemption limits, and course-specific promotions.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.coupons.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Coupon
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

    <!-- Filters & Search -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.coupons.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
            <!-- Search -->
            <div class="sm:col-span-5">
                <label for="search" class="sr-only">Search</label>
                <div class="relative">
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Search by coupon code or name..."
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-3">
                <select name="status"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Type Filter -->
            <div class="sm:col-span-2">
                <select name="type"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Types</option>
                    <option value="percentage" {{ request('type') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ request('type') === 'fixed' ? 'selected' : '' }}>Fixed (₹)</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-2.5 text-xs font-semibold transition text-center cursor-pointer">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'type']))
                    <a href="{{ route('admin.coupons.index') }}"
                       class="rounded-xl bg-slate-900 border border-slate-700 hover:bg-slate-800 text-slate-400 hover:text-white px-3 py-2.5 text-xs font-semibold transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Coupons Table Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-left text-xs">
                <thead class="bg-slate-950/80 text-slate-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Coupon & Name</th>
                        <th scope="col" class="px-4 py-3.5">Discount</th>
                        <th scope="col" class="px-4 py-3.5">Scope & Limits</th>
                        <th scope="col" class="px-4 py-3.5">Usage</th>
                        <th scope="col" class="px-4 py-3.5">Validity</th>
                        <th scope="col" class="px-4 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center rounded-lg bg-amber-500/10 px-2.5 py-1 text-xs font-mono font-bold text-amber-400 ring-1 ring-inset ring-amber-500/20 uppercase tracking-wide">
                                        {{ $coupon->code }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs font-semibold text-white">
                                    {{ $coupon->name }}
                                </div>
                                @if($coupon->course)
                                    <div class="mt-0.5 text-[11px] text-slate-400 flex items-center gap-1">
                                        <span class="text-indigo-400">Course:</span> {{ Str::limit($coupon->course->title, 28) }}
                                    </div>
                                @else
                                    <div class="mt-0.5 text-[11px] text-emerald-400/80">
                                        All Courses
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm font-black text-white">
                                    {{ $coupon->formattedDiscount() }}
                                </span>
                                @if($coupon->max_discount_amount)
                                    <div class="text-[11px] text-slate-400">
                                        Max Cap: {{ $coupon->formattedMaxDiscount() }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-[11px] text-slate-300">
                                    Min Order: <span class="font-medium text-white">{{ $coupon->formattedMinOrder() ?? 'None' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Per User: <span class="text-white">{{ $coupon->per_user_limit }}x</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-white">{{ $coupon->times_used }}</span>
                                    <span class="text-slate-500">/</span>
                                    <span class="text-slate-400">{{ $coupon->usage_limit ?? '∞' }}</span>
                                </div>
                                @if($coupon->hasReachedGlobalLimit())
                                    <span class="inline-block mt-0.5 text-[10px] font-semibold text-rose-400">Limit Reached</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-[11px]">
                                @if($coupon->expires_at)
                                    <div class="{{ $coupon->isExpired() ? 'text-rose-400 font-semibold' : 'text-slate-300' }}">
                                        Expires: {{ $coupon->expires_at->format('M d, Y') }}
                                    </div>
                                    @if($coupon->isExpired())
                                        <span class="inline-block text-[10px] text-rose-400">Expired</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">No Expiry</span>
                                @endif
                                @if($coupon->starts_at && $coupon->isUpcoming())
                                    <div class="text-amber-400 text-[10px] mt-0.5">Starts: {{ $coupon->starts_at->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            title="Click to toggle active status"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold cursor-pointer transition {{ $coupon->is_active ? 'bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $coupon->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                       class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to {{ $coupon->times_used > 0 ? 'deactivate' : 'delete' }} coupon {{ $coupon->code }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition cursor-pointer">
                                            {{ $coupon->times_used > 0 ? 'Deactivate' : 'Delete' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <svg class="mx-auto h-8 w-8 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <p class="text-sm font-semibold text-white">No promotional coupons found</p>
                                <p class="text-xs text-slate-500 mt-1">Get started by creating your first promotional coupon discount.</p>
                                <a href="{{ route('admin.coupons.create') }}"
                                   class="inline-flex items-center gap-1.5 mt-4 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                                    + Create First Coupon
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coupons->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>
</div>
@endsection