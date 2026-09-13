@extends('layouts.public')

@section('title', 'Marketing Email Preferences — Marketian Mind')

@section('subcontent')
<div class="py-16 bg-slate-50 min-h-[70vh] flex items-center justify-center">
    <div class="max-w-md w-full mx-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        @if($success)
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Unsubscribed Successfully</h1>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                You have been removed from Marketian Mind promotional and marketing communications for <strong>{{ $email }}</strong>.
            </p>
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 text-left mb-6 leading-relaxed">
                <strong>Please Note:</strong> Essential transactional messages (such as password resets, purchase invoices, and course enrollment receipts) will still be delivered if you have an active account.
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition shadow-sm">
                Return to Homepage
            </a>
        @elseif($isAlreadyUnsubscribed)
            <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Already Unsubscribed</h1>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                The email address <strong>{{ $email }}</strong> is already on our opt-out list and will not receive marketing emails.
            </p>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition shadow-sm">
                Return to Homepage
            </a>
        @else
            <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Unsubscribe from Marketing</h1>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                Confirm below to opt out of promotional emails, course announcements, and educational marketing updates.
            </p>

            <form action="{{ route('marketing.unsubscribe.submit') }}" method="POST" class="space-y-4 text-left">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white"
                        placeholder="your@email.com">
                    @error('email')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Reason (Optional)</label>
                    <select id="reason" name="reason"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        <option value="No longer interested in online marketing education">No longer interested in marketing education</option>
                        <option value="Too many emails received">Too many emails received</option>
                        <option value="Never signed up / wrong email">Never signed up / wrong email</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-medium text-sm rounded-xl transition shadow-sm">
                        Confirm Unsubscribe
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
