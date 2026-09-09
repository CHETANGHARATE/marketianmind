@extends('layouts.student')

@section('subcontent')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('student.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
            &larr; Back to Orders
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Receipt
        </button>
    </div>

    <!-- Printable Receipt Card -->
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded bg-indigo-600 text-white font-bold text-xs">M</span>
                    <span class="font-bold text-lg text-slate-900">Marketian<span class="text-indigo-600">Mind</span></span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Practical Marketing Education for Founders</p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
                <p class="text-xs font-mono text-slate-500 mt-1">Order #{{ $order->order_number }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 py-6 border-b border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 font-medium">Billed To</span>
                <p class="font-bold text-slate-900 mt-0.5">{{ $order->user->name }}</p>
                <p class="text-slate-500">{{ $order->user->email }}</p>
            </div>
            <div class="text-right">
                <span class="text-slate-400 font-medium">Order Date</span>
                <p class="font-bold text-slate-900 mt-0.5">{{ $order->created_at->format('F d, Y') }}</p>
                @if($order->paid_at)
                    <p class="text-emerald-600 font-medium">Paid at: {{ $order->paid_at->format('h:i A') }}</p>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="py-6 border-b border-slate-100">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100">
                        <th class="pb-3 font-semibold">Course Description</th>
                        <th class="pb-3 text-right font-semibold">Tuition</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-4">
                            <span class="font-bold text-slate-900 text-sm block">{{ $order->course->title }}</span>
                            <span class="text-slate-500 text-xs">Lifetime access, self-paced curriculum, verified certificate track</span>
                        </td>
                        <td class="py-4 text-right font-bold text-slate-900 text-sm">
                            {{ $order->formattedAmount() }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total -->
        <div class="py-6 flex flex-col items-end text-xs space-y-2">
            <div class="flex justify-between w-48 text-slate-500">
                <span>Subtotal</span>
                <span>{{ $order->formattedAmount() }}</span>
            </div>
            <div class="flex justify-between w-48 font-bold text-slate-900 text-sm pt-2 border-t border-slate-100">
                <span>Total Paid</span>
                <span>{{ $order->formattedAmount() }}</span>
            </div>
        </div>

        @if($order->payments->count() > 0)
            <div class="pt-6 border-t border-slate-100 text-[11px] text-slate-400">
                <span class="font-semibold text-slate-600 block mb-1">Payment Transactions</span>
                @foreach($order->payments as $payment)
                    <div class="flex justify-between py-1">
                        <span>Ref: {{ $payment->razorpay_payment_id ?: 'N/A' }} ({{ ucfirst($payment->method ?? 'Razorpay') }})</span>
                        <span class="{{ $payment->isCaptured() ? 'text-emerald-600' : 'text-slate-500' }}">{{ strtoupper($payment->status->value) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection