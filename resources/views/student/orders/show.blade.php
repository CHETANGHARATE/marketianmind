@extends('layouts.student')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Top Bar Navigation (Hidden on Print) -->
    <div class="flex items-center justify-between print:hidden">
        <a href="{{ route('student.orders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
            &larr; Back to Purchase History
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Receipt
            </button>
        </div>
    </div>

    <!-- Status Notice Banner (Hidden on Print) -->
    <div class="print:hidden">
        @if($order->isPaid())
            @if($isEnrolled)
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shrink-0 shadow-xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-emerald-900">Payment Verified &amp; Course Access Active</h3>
                            <p class="mt-0.5 text-xs text-emerald-700">Your tuition is paid in full. You have lifetime access to all course lessons and curriculum materials.</p>
                        </div>
                    </div>
                    <a href="{{ route('student.courses.show', $order->course) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-500 transition shrink-0">
                        Continue Learning &rarr;
                    </a>
                </div>
            @else
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5 sm:p-6 shadow-xs flex items-start gap-3.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-600 text-white shrink-0 shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900">Payment Confirmed — Enrollment Provisioning</h3>
                        <p class="mt-0.5 text-xs text-amber-700">Your payment of {{ $order->formattedAmount() }} was successfully verified. If your course player does not unlock within a few moments, please reach out to our team at support@marketianmind.com.</p>
                    </div>
                </div>
            @endif
        @elseif($order->isPending())
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white shrink-0 shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900">Payment Pending</h3>
                        <p class="mt-0.5 text-xs text-amber-700">This order was created but has not yet completed payment. Complete your payment to unlock instant course access.</p>
                    </div>
                </div>
                <a href="{{ route('student.courses.checkout', $order) }}" class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-amber-500 transition shrink-0">
                    Complete Payment &rarr;
                </a>
            </div>
        @elseif($order->isFailed())
            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-600 text-white shrink-0 shadow-xs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Payment Unsuccessful</h3>
                        <p class="mt-0.5 text-xs text-rose-700">The recent transaction attempt for this order was not completed. No funds were debited, or your bank may reverse any temporary hold.</p>
                    </div>
                </div>
                <a href="{{ route('courses.show', $order->course) }}" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-rose-500 transition shrink-0">
                    Retry Purchase &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- Course Information Summary Card (Hidden on Print) -->
    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm print:hidden">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            @if($order->course->thumbnailUrl())
                <img src="{{ $order->course->thumbnailUrl() }}" alt="{{ $order->course->title }}" class="h-20 w-32 rounded-xl object-cover border border-slate-100 bg-slate-100 shrink-0">
            @else
                <div class="h-20 w-32 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-lg shrink-0">
                    MM
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    @if($order->course->category)
                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 border border-indigo-100">
                            {{ $order->course->category->name }}
                        </span>
                    @endif
                    <span class="text-xs text-slate-400">Instructor: {{ $order->course->instructor_name ?? 'Marketian Mind Faculty' }}</span>
                </div>
                <h2 class="text-lg font-bold text-slate-900 truncate">{{ $order->course->title }}</h2>
                <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ $order->course->short_description }}</p>
            </div>
            <div class="sm:text-right shrink-0">
                <span class="text-xs text-slate-400 block font-medium">Course Tuition</span>
                <span class="text-xl font-bold text-slate-900">{{ $order->formattedAmount() }}</span>
            </div>
        </div>
    </div>

    <!-- Formal Printable Receipt Card -->
    <div class="rounded-2xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm print:border-none print:shadow-none print:p-0">
        <!-- Receipt Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-sm shadow-xs">M</span>
                    <span class="font-bold text-xl text-slate-900 tracking-tight">Marketian<span class="text-indigo-600">Mind</span></span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Practical Online Marketing Education for Business Owners &amp; Founders</p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
                <p class="text-xs font-mono font-bold text-slate-900 mt-1.5">Order #{{ $order->order_number }}</p>
            </div>
        </div>

        <!-- Meta Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] block">Customer Details</span>
                <p class="font-bold text-slate-900 text-sm mt-1">{{ $order->user->name }}</p>
                <p class="text-slate-600 mt-0.5">{{ $order->user->email }}</p>
                <p class="text-slate-400 text-[11px] mt-0.5">Role: {{ ucfirst($order->user->role->value ?? 'Student') }}</p>
            </div>
            <div class="sm:text-right">
                <span class="text-slate-400 font-semibold uppercase tracking-wider text-[10px] block">Invoice Information</span>
                <p class="font-bold text-slate-900 mt-1">Date: {{ $order->created_at->format('F d, Y') }}</p>
                @if($order->paid_at)
                    <p class="text-emerald-700 font-semibold mt-0.5">Payment Verified: {{ $order->paid_at->format('M d, Y - h:i A') }}</p>
                @endif
                <p class="text-slate-500 font-mono text-[11px] mt-0.5">Currency: {{ strtoupper($order->currency) }}</p>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="py-6 border-b border-slate-100">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider">
                        <th class="pb-3">Description</th>
                        <th class="pb-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-4">
                            <span class="font-bold text-slate-900 text-sm block">{{ $order->course->title }}</span>
                            <span class="text-slate-500 text-xs">Full course tuition, self-paced curriculum, complete digital marketing modules &amp; certificate eligibility.</span>
                        </td>
                        <td class="py-4 text-right font-bold text-slate-900 text-sm whitespace-nowrap">
                            {{ $order->formattedAmount() }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary Totals -->
        <div class="py-6 flex flex-col items-end text-xs space-y-2">
            <div class="flex justify-between w-56 text-slate-600">
                <span>Subtotal</span>
                <span class="font-medium">{{ $order->formattedAmount() }}</span>
            </div>
            <div class="flex justify-between w-56 text-slate-600">
                <span>Taxes &amp; Fees</span>
                <span class="font-medium">₹0.00</span>
            </div>
            <div class="flex justify-between w-56 font-bold text-slate-900 text-base pt-3 border-t border-slate-200">
                <span>Total Amount</span>
                <span>{{ $order->formattedAmount() }}</span>
            </div>
        </div>

        <!-- Payment Records (Safe Details Only) -->
        @if($order->payments->count() > 0)
            <div class="pt-6 border-t border-slate-100 text-xs">
                <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px] block mb-2">Payment Transaction Records</span>
                <div class="space-y-2">
                    @foreach($order->payments as $payment)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px]">
                            <div class="space-y-0.5">
                                <p class="font-mono text-slate-800">
                                    <span class="font-semibold text-slate-500">Gateway Ref:</span> {{ $payment->razorpay_payment_id ?: 'Pending' }}
                                </p>
                                <p class="text-slate-500">
                                    Method: <span class="font-semibold text-slate-700">{{ ucfirst($payment->method ?? 'Razorpay Gateway') }}</span>
                                    @if($payment->paid_at)
                                        &bull; Processed: {{ $payment->paid_at->format('M d, Y h:i A') }}
                                    @endif
                                </p>
                            </div>
                            <div class="mt-2 sm:mt-0 sm:text-right">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $payment->isCaptured() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $payment->status->value }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Footer / Print Notice -->
        <div class="mt-8 pt-6 border-t border-slate-100 text-center text-[11px] text-slate-400">
            <p>Marketian Mind &bull; Online Marketing Education Platform &bull; support@marketianmind.com</p>
            <p class="mt-1">This document serves as an electronic confirmation of your course purchase.</p>
        </div>
    </div>
</div>
@endsection