@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Financial Auditing
                </span>
                <span class="text-xs text-slate-500">Commerce & Payment Gateway</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Orders & Transactions
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Review student course purchases, payment audit logs, transaction histories, and gateway statuses.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300">
                <span class="text-amber-400 font-bold mr-1.5">{{ number_format($metrics['total_orders']) }}</span> Total Orders
            </span>
        </div>
    </div>

    <!-- KPI Metrics Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Revenue Card -->
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Total Revenue</span>
                <div class="rounded-lg bg-emerald-500/20 p-2 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ $metrics['formatted_revenue'] }}</p>
                <p class="mt-1 text-[11px] text-emerald-400/80">From verified paid orders</p>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">All Orders</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['total_orders']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Lifetime purchases initiated</p>
            </div>
        </div>

        <!-- Paid Orders Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Paid Orders</span>
                <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['paid_orders']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Successfully completed</p>
            </div>
        </div>

        <!-- Pending Orders Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Pending Orders</span>
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['pending_orders']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Awaiting payment verification</p>
            </div>
        </div>

        <!-- Failed Orders Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-400">Failed Orders</span>
                <div class="rounded-lg bg-rose-500/10 p-2 text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['failed_orders']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Unsuccessful attempts</p>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Controls -->
    <div class="space-y-4">
        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800/80 pb-3">
            <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ empty($currentStatus) || $currentStatus === 'all' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                All ({{ number_format($metrics['total_orders']) }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'paid'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'paid' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Paid ({{ number_format($metrics['paid_orders']) }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'pending' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Pending ({{ number_format($metrics['pending_orders']) }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'failed'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'failed' ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                Failed ({{ number_format($metrics['failed_orders']) }})
            </a>
            @if($metrics['cancelled_orders'] > 0)
                <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'cancelled'])) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'cancelled' ? 'bg-slate-500/15 text-slate-300 border border-slate-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    Cancelled ({{ number_format($metrics['cancelled_orders']) }})
                </a>
            @endif
            @if($metrics['refunded_orders'] > 0)
                <a href="{{ route('admin.orders.index', array_merge(request()->except(['status', 'page']), ['status' => 'refunded'])) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'refunded' ? 'bg-purple-500/15 text-purple-400 border border-purple-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    Refunded ({{ number_format($metrics['refunded_orders']) }})
                </a>
            @endif
        </div>

        <!-- Search & Filter Bar -->
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            @if($currentStatus && $currentStatus !== 'all')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <!-- Search Input (4 cols) -->
            <div class="sm:col-span-4 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by Order #, Student, Course, or Razorpay ID..."
                       class="w-full rounded-xl border border-slate-800 bg-slate-900/90 pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @if($search !== '')
                    <a href="{{ route('admin.orders.index', request()->except(['search', 'page'])) }}"
                       class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white"
                       title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>

            <!-- Course Filter Select (3 cols) -->
            <div class="sm:col-span-3">
                <select name="course"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->slug }}" {{ $currentCourse === $c->slug ? 'selected' : '' }}>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Status Filter (2 cols) -->
            <div class="sm:col-span-2">
                <select name="payment_status"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Payment Types</option>
                    @foreach($paymentStatuses as $ps)
                        <option value="{{ $ps->value }}" {{ $currentPaymentStatus === $ps->value ? 'selected' : '' }}>
                            {{ $ps->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter (1 col) -->
            <div class="sm:col-span-1">
                <select name="date"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-2.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 truncate">
                    <option value="">All Dates</option>
                    <option value="today" {{ $currentDate === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ $currentDate === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $currentDate === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>
            </div>

            <!-- Sort Select (2 cols) -->
            <div class="sm:col-span-2">
                <select name="sort"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>Newest Orders</option>
                    <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Oldest Orders</option>
                    <option value="amount_high" {{ $currentSort === 'amount_high' ? 'selected' : '' }}>Highest Amount</option>
                    <option value="amount_low" {{ $currentSort === 'amount_low' ? 'selected' : '' }}>Lowest Amount</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Orders Table Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-6 py-4">Order Details</th>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Historical Amount</th>
                            <th class="px-6 py-4">Order Status</th>
                            <th class="px-6 py-4">Payment</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($orders as $order)
                            @php
                                $latestPayment = $order->payments->first();
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Order Number & Date -->
                                <td class="px-6 py-4">
                                    <div class="font-mono font-bold text-white text-sm">
                                        {{ $order->order_number }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $order->created_at->format('M d, Y') }} &bull; {{ $order->created_at->format('h:i A') }}
                                    </div>
                                    @if($order->razorpay_order_id)
                                        <div class="text-[10px] font-mono text-slate-500 mt-0.5 truncate max-w-[160px]" title="{{ $order->razorpay_order_id }}">
                                            {{ $order->razorpay_order_id }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Student -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-amber-400 border border-slate-700 shrink-0">
                                            {{ strtoupper(substr($order->user->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.students.show', $order->user) }}" class="font-semibold text-white hover:text-amber-400 transition truncate block">
                                                {{ $order->user->name }}
                                            </a>
                                            <span class="text-[11px] text-slate-400 truncate block">
                                                {{ $order->user->email }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Course -->
                                <td class="px-6 py-4 max-w-[220px]">
                                    <a href="{{ route('admin.courses.edit', $order->course) }}" class="font-medium text-slate-200 hover:text-amber-400 transition block truncate" title="{{ $order->course->title }}">
                                        {{ $order->course->title }}
                                    </a>
                                    @if($order->course->category)
                                        <span class="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 text-[10px] font-semibold text-slate-400 mt-1">
                                            {{ $order->course->category->name }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Historical Amount -->
                                <td class="px-6 py-4 font-mono font-bold text-white text-sm">
                                    {{ $order->formattedAmount() }}
                                    <span class="text-[10px] text-slate-500 font-sans font-normal block">{{ $order->currency }}</span>
                                </td>

                                <!-- Order Status -->
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = match($order->status->value) {
                                            'paid' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                            'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                            'failed' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                            'cancelled' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                                            'refunded' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                            default => 'bg-slate-800 text-slate-300 border-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border {{ $statusClasses }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>

                                <!-- Payment Status / Method -->
                                <td class="px-6 py-4">
                                    @if($latestPayment)
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $latestPayment->isCaptured() ? 'bg-emerald-500/10 text-emerald-400' : ($latestPayment->isFailed() ? 'bg-rose-500/10 text-rose-400' : 'bg-slate-800 text-slate-300') }}">
                                                {{ $latestPayment->status->label() }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Method: {{ ucfirst($latestPayment->method ?? 'Gateway') }}
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-500 italic">None</span>
                                    @endif
                                </td>

                                <!-- Action -->
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                        Audit &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($orders->hasPages())
                <div class="border-t border-slate-800 p-4">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-800/80 flex items-center justify-center text-slate-500 mb-4 border border-slate-700/50">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-white">No Orders Found</h3>
                <p class="mt-1 text-xs text-slate-400 max-w-sm mx-auto">
                    No orders match your active filter and search criteria.
                </p>
                <div class="mt-5">
                    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition border border-slate-700">
                        Reset All Filters
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection