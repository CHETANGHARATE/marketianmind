@extends('layouts.public')

@section('subcontent')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <!-- Back link -->
        <div class="mb-8">
            <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                &larr; Back to Course Overview
            </a>
        </div>

        <!-- Session Status & Alerts -->
        @if(session('status'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Order Details -->
            <div class="lg:col-span-7 space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-600 mb-2">
                        <span>Secure Checkout</span>
                        <span>&bull;</span>
                        <span class="text-slate-400">Order #{{ $order->order_number }}</span>
                    </div>

                    <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        {{ $course->title }}
                    </h1>

                    @if($course->short_description)
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                            {{ $course->short_description }}
                        </p>
                    @endif

                    <div class="mt-6 pt-6 border-t border-slate-100 flex items-center gap-4 text-xs text-slate-500">
                        @if($course->level)
                            <span class="inline-flex items-center gap-1">
                                <span class="font-semibold text-slate-700">Level:</span> {{ $course->level }}
                            </span>
                        @endif
                        @if($course->duration)
                            <span class="inline-flex items-center gap-1">
                                <span class="font-semibold text-slate-700">Duration:</span> {{ $course->duration }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1">
                            <span class="font-semibold text-slate-700">Access:</span> Lifetime
                        </span>
                    </div>
                </div>

                <!-- Trust Guarantees -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Marketian Mind Student Guarantee</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-600">
                        <div class="flex items-start gap-2.5">
                            <span class="font-bold text-emerald-600 text-sm">&check;</span>
                            <div>
                                <span class="font-semibold text-slate-800">Instant Access</span>
                                <p class="text-slate-500 mt-0.5">Start watching lessons immediately upon enrollment.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="font-bold text-emerald-600 text-sm">&check;</span>
                            <div>
                                <span class="font-semibold text-slate-800">Bank-Grade Encryption</span>
                                <p class="text-slate-500 mt-0.5">Processed securely via Razorpay (PCI-DSS compliant).</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="font-bold text-emerald-600 text-sm">&check;</span>
                            <div>
                                <span class="font-semibold text-slate-800">Multiple Payment Modes</span>
                                <p class="text-slate-500 mt-0.5">UPI (GPay, PhonePe, Paytm), Cards & Netbanking.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="font-bold text-emerald-600 text-sm">&check;</span>
                            <div>
                                <span class="font-semibold text-slate-800">Practical Marketing Content</span>
                                <p class="text-slate-500 mt-0.5">Actionable growth tactics built for busy founders.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Summary Card -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Promo Code Box -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Promotional Coupon</span>
                    </h3>

                    @if($order->hasCoupon())
                        <div class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-sm text-emerald-800 tracking-wider uppercase">{{ $order->coupon_code }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Applied</span>
                                </div>
                                <p class="text-xs text-emerald-600 mt-0.5">Saved {{ $order->formattedDiscountAmount() }} on this order</p>
                            </div>
                            <form action="{{ route('student.courses.checkout.remove-coupon', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline">
                                    Remove
                                </button>
                            </form>
                        </div>
                    @else
                        <form action="{{ route('student.courses.checkout.apply-coupon', $order) }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="text"
                                   name="coupon_code"
                                   id="coupon_code"
                                   value="{{ old('coupon_code') }}"
                                   placeholder="Enter coupon code"
                                   required
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs font-medium uppercase tracking-wider placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <button type="submit"
                                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition whitespace-nowrap">
                                Apply
                            </button>
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm sticky top-6">
                    @if($course->thumbnailUrl())
                        <div class="aspect-video rounded-xl overflow-hidden mb-6 bg-slate-100">
                            <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                        </div>
                    @endif

                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-4">
                        Investment Summary
                    </h2>

                    <div class="py-4 space-y-3 text-sm border-b border-slate-100">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Standard Tuition</span>
                            <span>₹{{ number_format($course->price, 2) }}</span>
                        </div>

                        @if($course->hasDiscount())
                            <div class="flex items-center justify-between text-emerald-600 font-medium">
                                <span>Founder Discount</span>
                                <span>- ₹{{ number_format($course->price - $course->discount_price, 2) }}</span>
                            </div>
                        @endif

                        @if($order->hasCoupon() && $order->discount_amount > 0)
                            <div class="flex items-center justify-between text-indigo-600 font-medium">
                                <span>Coupon ({{ $order->coupon_code }})</span>
                                <span>- {{ $order->formattedDiscountAmount() }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Platform Fee</span>
                            <span class="text-emerald-600 font-semibold">Free</span>
                        </div>
                    </div>

                    <div class="py-4 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">Total Due Today</span>
                        <span class="text-2xl font-black text-slate-900">{{ $order->formattedAmount() }}</span>
                    </div>

                    <!-- Payment / Enrollment Actions -->
                    <div class="mt-4 space-y-3">
                        @if($order->amount > 0)
                            <button type="button" id="rzp-button" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                                Pay {{ $order->formattedAmount() }} Now &rarr;
                            </button>
                        @else
                            <form action="{{ route('student.courses.checkout.complete-free', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 transition cursor-pointer">
                                    Complete Free Enrollment &rarr;
                                </button>
                            </form>
                        @endif

                        <div class="text-center text-[11px] text-slate-400">
                            By enrolling, you accept lifetime platform access terms.
                        </div>
                    </div>

                    @if($order->amount > 0)
                        <!-- Hidden Verification Form -->
                        <form id="rzp-verify-form" action="{{ route('payments.razorpay.verify') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="razorpay_order_id" id="form_razorpay_order_id">
                            <input type="hidden" name="razorpay_payment_id" id="form_razorpay_payment_id">
                            <input type="hidden" name="razorpay_signature" id="form_razorpay_signature">
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($order->amount > 0)
<!-- Razorpay Standard Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    document.getElementById('rzp-button')?.addEventListener('click', function(e) {
        e.preventDefault();

        const options = {
            key: "{{ $keyId }}",
            amount: "{{ $order->amount }}",
            currency: "{{ $order->currency }}",
            name: "Marketian Mind",
            description: "{{ addslashes($course->title) }}",
            order_id: "{{ $order->razorpay_order_id }}",
            prefill: {
                name: "{{ addslashes(auth()->user()->name) }}",
                email: "{{ addslashes(auth()->user()->email) }}"
            },
            theme: {
                color: "#4f46e5"
            },
            handler: function (response) {
                document.getElementById('form_razorpay_order_id').value = response.razorpay_order_id;
                document.getElementById('form_razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('form_razorpay_signature').value = response.razorpay_signature;
                document.getElementById('rzp-verify-form').submit();
            },
            modal: {
                ondismiss: function() {
                    console.log('Razorpay modal closed by user');
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            window.location.href = "{{ route('payment.failed', $order) }}";
        });
        rzp.open();
    });
</script>
@endif
@endsection