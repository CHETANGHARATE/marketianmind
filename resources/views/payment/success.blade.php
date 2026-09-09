@extends('layouts.public')

@section('subcontent')
<div class="min-h-screen bg-slate-50 py-16 flex items-center justify-center">
    <div class="mx-auto max-w-xl px-4 sm:px-6 w-full">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50 mb-6">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 uppercase tracking-wider">
                Payment Successful
            </span>

            <h1 class="mt-4 text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                Welcome to the Course!
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Your payment was verified successfully and full course access is now active on your account.
            </p>

            <!-- Receipt Box -->
            <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50/80 p-5 text-left text-xs space-y-3">
                <div class="flex justify-between items-center pb-3 border-b border-slate-200/60">
                    <span class="text-slate-500">Course</span>
                    <span class="font-bold text-slate-900 text-right">{{ $order->course->title }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Order Reference</span>
                    <span class="font-mono font-semibold text-slate-800">{{ $order->order_number }}</span>
                </div>
                @if($payment)
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Payment ID</span>
                        <span class="font-mono text-slate-700">{{ $payment->razorpay_payment_id }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Amount Paid</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $order->formattedAmount() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Date</span>
                    <span class="text-slate-700">{{ $order->paid_at ? $order->paid_at->format('M d, Y h:i A') : now()->format('M d, Y') }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 space-y-3">
                <a href="{{ route('student.courses.show', $order->course) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Start Learning Now &rarr;
                </a>
                <a href="{{ route('student.orders.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                    View Purchase History
                </a>
            </div>
        </div>
    </div>
</div>
@endsection