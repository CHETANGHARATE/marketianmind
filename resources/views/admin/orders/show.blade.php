@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Back to All Orders
        </a>
        <span class="text-xs text-slate-500">Audit Mode (Read-Only)</span>
    </div>

    <!-- Order Audit Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-800 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Order Audit Inspector</span>
                <h1 class="text-2xl font-black text-white mt-1">{{ $order->order_number }}</h1>
            </div>
            <div>
                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-bold {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-800 text-xs">
            <div>
                <span class="text-slate-400 font-semibold block mb-1">Student Details</span>
                <p class="font-bold text-white text-sm">{{ $order->user->name }}</p>
                <p class="text-slate-400">{{ $order->user->email }}</p>
                <p class="text-slate-500 mt-1">User ID: #{{ $order->user->id }}</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block mb-1">Course Details</span>
                <p class="font-bold text-white text-sm">{{ $order->course->title }}</p>
                <p class="text-slate-400">Slug: {{ $order->course->slug }}</p>
                <p class="text-slate-500 mt-1">Course ID: #{{ $order->course->id }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 py-6 border-b border-slate-800 text-xs">
            <div>
                <span class="text-slate-400 font-semibold block mb-1">Amount</span>
                <p class="font-bold text-white text-base">{{ $order->formattedAmount() }}</p>
                <p class="text-slate-500">{{ $order->amount }} paise ({{ $order->currency }})</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block mb-1">Razorpay Order ID</span>
                <p class="font-mono text-indigo-400 font-medium">{{ $order->razorpay_order_id ?: 'None' }}</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block mb-1">Timestamps</span>
                <p class="text-slate-300">Created: {{ $order->created_at->format('M d, Y h:i A') }}</p>
                <p class="text-slate-300">Paid: {{ $order->paid_at ? $order->paid_at->format('M d, Y h:i A') : 'Unpaid' }}</p>
            </div>
        </div>

        <!-- Payments Log -->
        <div class="pt-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">
                Payment Transactions Associated ({{ $order->payments->count() }})
            </h3>

            @if($order->payments->count() > 0)
                <div class="divide-y divide-slate-800 border border-slate-800 rounded-xl overflow-hidden text-xs">
                    @foreach($order->payments as $payment)
                        <div class="p-4 bg-slate-950/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="font-mono text-white font-semibold">{{ $payment->razorpay_payment_id }}</span>
                                <div class="text-slate-400 mt-0.5">
                                    Method: {{ ucfirst($payment->method ?? 'Gateway') }} &bull; Captured: {{ $payment->captured ? 'Yes' : 'No' }}
                                </div>
                                @if($payment->failure_description)
                                    <div class="text-rose-400 mt-1">
                                        Error: {{ $payment->failure_description }} ({{ $payment->failure_code }})
                                    </div>
                                @endif
                            </div>
                            <div class="sm:text-right">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold {{ $payment->status->badgeClasses() }}">
                                    {{ $payment->status->label() }}
                                </span>
                                <div class="text-slate-500 text-[11px] mt-0.5">
                                    {{ $payment->created_at->format('M d, Y h:i A') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-500 italic">No payment attempts have been captured for this order yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection