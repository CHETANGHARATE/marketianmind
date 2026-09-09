@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Purchase History</h1>
            <p class="mt-1 text-sm text-slate-500">Review your past course enrollments, invoices, and transaction records.</p>
        </div>
        <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
            &larr; Back to My Courses
        </a>
    </div>

    <!-- Orders Table Card -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Order #</th>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-mono font-medium text-slate-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900 block">{{ $order->course->title }}</span>
                                    <span class="text-[11px] text-slate-400">Ref: {{ $order->razorpay_order_id ?: 'Pending Gateway' }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $order->formattedAmount() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $order->status->badgeClasses() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($order->isPaid())
                                            <a href="{{ route('student.courses.show', $order->course) }}" class="rounded bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 transition">
                                                Go to Course
                                            </a>
                                        @elseif($order->isPending())
                                            <a href="{{ route('student.courses.checkout', $order) }}" class="rounded bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition">
                                                Pay Now
                                            </a>
                                        @endif
                                        <a href="{{ route('student.orders.show', $order) }}" class="rounded border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                            Receipt
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-slate-200 p-4">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 mb-3">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">No Orders Found</h3>
                <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">You haven't purchased any paid courses yet. Browse the curriculum catalog to enroll in upcoming growth tracks.</p>
                <div class="mt-5">
                    <a href="{{ route('courses') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        Explore Paid Courses &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection