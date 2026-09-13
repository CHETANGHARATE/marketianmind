@extends('layouts.public')

@section('title', 'WhatsApp Communication Preferences — Marketian Mind')

@section('subcontent')
<div class="py-16 bg-slate-50 min-h-[70vh] flex items-center justify-center">
    <div class="max-w-md w-full mx-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        @if(session('status'))
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Preferences Updated</h1>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                {{ session('status') }}
            </p>
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 text-left mb-6 leading-relaxed">
                <strong>Please Note:</strong> Essential transactional messages (such as course enrollment receipts and payment confirmations) will still be delivered if requested.
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition shadow-sm">
                Return to Homepage
            </a>
        @else
            <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Opt Out of WhatsApp Marketing</h1>
            <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                Enter your WhatsApp phone number below to stop receiving marketing notifications, course offers, and educational updates.
            </p>

            <form action="{{ route('whatsapp.opt-out.submit') }}" method="POST" class="space-y-4 text-left">
                @csrf
                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">WhatsApp Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $phone) }}" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white"
                        placeholder="e.g. +91 98765 43210">
                    @error('phone')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-medium text-sm rounded-xl transition shadow-sm">
                        Opt Out of WhatsApp Messages
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-xs text-slate-400">
                Need help? Contact support at support@marketianmind.com
            </div>
        @endif
    </div>
</div>
@endsection
