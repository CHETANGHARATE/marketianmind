@extends('layouts.admin')

@section('subcontent')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Top Breadcrumb & Back Link -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to All Orders
        </a>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700 text-[11px] text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Audit Mode &bull; Immutable Read-Only Ledger
        </div>
    </div>

    <!-- Main Order Overview Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-800 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Transaction Audit Record</span>
                <h1 class="text-2xl sm:text-3xl font-black text-white mt-1 font-mono">{{ $order->order_number }}</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Internal Order ID: #{{ $order->id }} &bull; Recorded on {{ $order->created_at->format('M d, Y \a\t h:i A') }}
                </p>
            </div>
            <div>
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
                <span class="inline-flex items-center rounded-xl px-4 py-1.5 text-xs font-bold uppercase tracking-wider border {{ $statusClasses }}">
                    {{ $order->status->label() }}
                </span>
            </div>
        </div>

        <!-- 3-Column Financial & Gateway Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 py-6 border-b border-slate-800 text-xs">
            <!-- Historical Amount -->
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 font-semibold block mb-1">Historical Transaction Amount</span>
                <p class="text-2xl font-black text-white font-mono">{{ $order->formattedAmount() }}</p>
                <p class="text-[11px] text-slate-400 mt-1">
                    {{ number_format($order->amount) }} paise ({{ $order->currency }})
                </p>
                <p class="text-[10px] text-slate-500 mt-2 italic">
                    Historical amount preserved from purchase time.
                </p>
            </div>

            <!-- Razorpay Gateway Identifiers -->
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 font-semibold block mb-1">Razorpay Gateway Order</span>
                <p class="font-mono text-amber-400 font-semibold text-xs break-all">
                    {{ $order->razorpay_order_id ?: 'Not Created' }}
                </p>
                <div class="mt-3 space-y-1 text-[11px] text-slate-400">
                    <div>Currency: <span class="font-mono text-white">{{ $order->currency }}</span></div>
                    <div>Gateway Attempts: <span class="font-semibold text-white">{{ $order->payments->count() }}</span></div>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 font-semibold block mb-1">Audit Timestamps</span>
                <div class="space-y-1 text-[11px] text-slate-300 mt-2">
                    <div>Created: <span class="text-white font-medium">{{ $order->created_at->format('M d, Y h:i A') }}</span></div>
                    <div>Paid: <span class="{{ $order->paid_at ? 'text-emerald-400 font-medium' : 'text-slate-500' }}">{{ $order->paid_at ? $order->paid_at->format('M d, Y h:i A') : 'Unpaid' }}</span></div>
                    @if($order->expires_at)
                        <div>Expires: <span class="text-slate-400">{{ $order->expires_at->format('M d, Y h:i A') }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2-Column Student & Course Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-800 text-xs">
            <!-- Student Information Card -->
            <div class="rounded-xl bg-slate-950/50 p-5 border border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Student Profile</span>
                    <a href="{{ route('admin.students.show', $order->user) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        View Profile &rarr;
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-sm font-bold text-amber-400 border border-slate-700">
                        {{ strtoupper(substr($order->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-white text-sm">{{ $order->user->name }}</p>
                        <p class="text-slate-400 text-xs">{{ $order->user->email }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>User ID: #{{ $order->user->id }}</span>
                    <span>Verified: {{ $order->user->email_verified_at ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            <!-- Course Information Card -->
            <div class="rounded-xl bg-slate-950/50 p-5 border border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Purchased Course</span>
                    <a href="{{ route('admin.courses.edit', $order->course) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        Edit Course &rarr;
                    </a>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">{{ $order->course->title }}</p>
                    <p class="text-slate-400 text-xs mt-0.5">Slug: {{ $order->course->slug }}</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Category: {{ $order->course->category?->name ?? 'Uncategorized' }}</span>
                    <span>Catalog Price: ₹{{ number_format($order->course->price, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Course Access & Enrollment State -->
        <div class="py-6 border-b border-slate-800 text-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                Course Enrollment Status
            </h3>
            @if($enrollment)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <span class="font-bold text-white text-sm">Enrollment Granted</span>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Status: <span class="capitalize text-emerald-400 font-semibold">{{ $enrollment->status->value }}</span> &bull; Enrolled {{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('M d, Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-amber-400 hover:text-amber-300 hover:bg-slate-700 transition">
                            Inspect Enrollment &rarr;
                        </a>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 text-slate-400">
                    <span class="font-semibold text-slate-300">No active enrollment record linked.</span>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Course access is automatically granted when an order payment is successfully verified.
                    </p>
                </div>
            @endif
        </div>

        <!-- Payments Transaction Log -->
        <div class="pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Payment Gateway Transactions ({{ $order->payments->count() }})
                </h3>
                <span class="text-[11px] text-slate-500">
                    Double-counting safe audit record
                </span>
            </div>

            @if($order->payments->count() > 0)
                <div class="divide-y divide-slate-800 border border-slate-800 rounded-2xl overflow-hidden text-xs">
                    @foreach($order->payments as $payment)
                        <div class="p-5 bg-slate-950/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-white font-bold text-sm">{{ $payment->razorpay_payment_id }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $payment->isCaptured() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : ($payment->isFailed() ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : 'bg-slate-800 text-slate-300 border border-slate-700') }}">
                                        {{ $payment->status->label() }}
                                    </span>
                                </div>
                                <div class="text-slate-400 text-xs mt-1 space-x-3">
                                    <span>Method: <strong class="text-white">{{ ucfirst($payment->method ?? 'Gateway') }}</strong></span>
                                    <span>Captured: <strong class="{{ $payment->captured ? 'text-emerald-400' : 'text-slate-400' }}">{{ $payment->captured ? 'Yes' : 'No' }}</strong></span>
                                    <span>Amount: <strong class="text-white font-mono">{{ $payment->formattedAmount() }}</strong></span>
                                </div>
                                @if($payment->failure_description || $payment->failure_code)
                                    <div class="mt-2.5 p-3 rounded-lg bg-rose-950/30 border border-rose-900/40 text-rose-300 text-[11px]">
                                        <strong>Failure Details:</strong> {{ $payment->failure_description }}
                                        @if($payment->failure_code)
                                            <span class="font-mono text-rose-400">({{ $payment->failure_code }})</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="sm:text-right shrink-0">
                                <div class="text-slate-400 text-xs">
                                    {{ $payment->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-slate-500 text-[11px] font-mono mt-0.5">
                                    {{ $payment->created_at->format('h:i:s A') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 rounded-2xl border border-dashed border-slate-800 bg-slate-950/30 text-center">
                    <p class="text-xs text-slate-400 italic">No gateway transaction attempts recorded for this order yet.</p>
                </div>
            @endif

            <!-- Security Policy Disclaimer -->
            <div class="mt-6 p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-500 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>
                    <strong>Security Policy:</strong> Razorpay API Key Secrets, webhook signing secrets, and raw payment authorization tokens are never exposed in administrative views.
                </span>
            </div>
        </div>
    </div>
</div>
@endsection