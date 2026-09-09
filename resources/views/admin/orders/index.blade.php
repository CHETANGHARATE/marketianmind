@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Orders & Transactions</h1>
            <p class="mt-1 text-xs text-slate-400">Review student course purchases, payment audit logs, and gateway statuses.</p>
        </div>
    </div>

    <!-- Filter Tabs & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div class="flex items-center gap-2 overflow-x-auto text-xs">
            <a href="{{ route('admin.orders.index') }}" class="px-3 py-1.5 rounded-lg font-semibold transition {{ empty($statusFilter) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'paid']) }}" class="px-3 py-1.5 rounded-lg font-semibold transition {{ $statusFilter === 'paid' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                Paid ({{ $counts['paid'] }})
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg font-semibold transition {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'failed']) }}" class="px-3 py-1.5 rounded-lg font-semibold transition {{ $statusFilter === 'failed' ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                Failed ({{ $counts['failed'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.orders.index') }}" method="GET" class="flex items-center gap-2">
            @if($statusFilter)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <input type="text" name="search" value="{{ $search }}" placeholder="Search Order # or Email" class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none">
            <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
                Search
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 shadow-sm overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-6 py-4">Order Number</th>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4 font-mono font-medium text-white">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-white block">{{ $order->user->name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $order->user->email }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    {{ $order->course->title }}
                                </td>
                                <td class="px-6 py-4 font-bold text-white">
                                    {{ $order->formattedAmount() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $order->status->badgeClasses() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="rounded bg-slate-800 px-2.5 py-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:bg-slate-700 transition">
                                        Audit &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-slate-800 p-4">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center text-xs text-slate-500">
                No orders match your active filter criteria.
            </div>
        @endif
    </div>
</div>
@endsection