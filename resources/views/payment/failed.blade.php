@extends('layouts.public')

@section('subcontent')
<div class="min-h-screen bg-slate-50 py-16 flex items-center justify-center">
    <div class="mx-auto max-w-lg px-4 sm:px-6 w-full">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm text-center">
            <!-- Failure Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-8 ring-rose-50/50 mb-6">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>

            <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20 uppercase tracking-wider">
                Payment Incomplete
            </span>

            <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-900">
                Payment Could Not Be Completed
            </h1>

            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                {{ session('error') ?? 'Your transaction was cancelled or could not be verified by the banking network. No course access has been activated.' }}
            </p>

            <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4 text-left text-xs space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Order Number</span>
                    <span class="font-mono font-semibold text-slate-800">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Course</span>
                    <span class="font-semibold text-slate-800">{{ $order->course->title }}</span>
                </div>
            </div>

            <!-- Retry Buttons -->
            <div class="mt-8 space-y-3">
                <a href="{{ route('student.courses.checkout', $order) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Retry Payment &rarr;
                </a>
                <a href="{{ route('courses.show', $order->course) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Back to Course Details
                </a>
            </div>

            <p class="mt-6 text-xs text-slate-400">
                Need help? Email us at <a href="mailto:hello@marketianmind.com" class="text-indigo-600 underline">hello@marketianmind.com</a>
            </p>
        </div>
    </div>
</div>
@endsection