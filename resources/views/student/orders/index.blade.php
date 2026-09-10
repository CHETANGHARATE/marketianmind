@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Purchase History</h1>
            <p class="mt-1 text-sm text-slate-500">Review your past course enrollments, invoices, and transaction records.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:border-slate-300 transition">
                &larr; Back to My Courses
            </a>
            <a href="{{ route('courses') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                Browse Catalog &rarr;
            </a>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('student.orders.index') }}"
           class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 {{ is_null($currentStatus) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
            All Orders
        </a>
        @foreach($statuses as $status)
            <a href="{{ route('student.orders.index', ['status' => $status->value]) }}"
               class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 {{ $currentStatus === $status->value ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                {{ $status->label() }}
            </a>
        @endforeach
    </div>

    <!-- Orders Table Card -->
    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Order #</th>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Payment Status</th>
                            <th class="px-6 py-4">Order Date</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($order->course->thumbnailUrl())
                                            <img src="{{ $order->course->thumbnailUrl() }}" alt="{{ $order->course->title }}" class="h-9 w-12 rounded-lg object-cover shrink-0 border border-slate-100 bg-slate-100">
                                        @else
                                            <div class="h-9 w-12 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-xs shrink-0">
                                                MM
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('student.orders.show', $order) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition block line-clamp-1">
                                                {{ $order->course->title }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                @if($order->course->category)
                                                    <span class="inline-flex items-center text-[10px] font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">
                                                        {{ $order->course->category->name }}
                                                    </span>
                                                @endif
                                                <span class="text-[10px] text-slate-400 font-mono">
                                                    {{ $order->razorpay_order_id ? 'Gateway Ref: ' . $order->razorpay_order_id : 'Gateway: Pending' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 text-sm">
                                    {{ $order->formattedAmount() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $order->status->badgeClasses() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($order->isPaid())
                                            <a href="{{ route('student.courses.show', $order->course) }}" class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 hover:text-indigo-700 transition">
                                                Go to Course
                                            </a>
                                        @elseif($order->isPending())
                                            <a href="{{ route('student.courses.checkout', $order) }}" class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition">
                                                Pay Now
                                            </a>
                                        @endif
                                        <a href="{{ route('student.orders.show', $order) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition">
                                            Details &amp; Receipt
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-slate-100 p-4">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                @if($currentStatus)
                    <h3 class="text-base font-bold text-slate-900">No {{ ucfirst($currentStatus) }} Orders Found</h3>
                    <p class="mt-1.5 text-xs text-slate-500 max-w-sm mx-auto">You do not have any orders matching the "{{ ucfirst($currentStatus) }}" status filter.</p>
                    <div class="mt-5">
                        <a href="{{ route('student.orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                            Clear Filter
                        </a>
                    </div>
                @else
                    <h3 class="text-base font-bold text-slate-900">No purchases yet</h3>
                    <p class="mt-1.5 text-xs text-slate-500 max-w-sm mx-auto">You haven't purchased any paid courses yet. Explore our course catalog to find practical growth programs for your business.</p>
                    <div class="mt-5">
                        <a href="{{ route('courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                            Explore Courses &rarr;
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection