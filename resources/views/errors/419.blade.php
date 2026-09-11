@extends('layouts.base', ['title' => 'Page Expired - Marketian Mind'])

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold uppercase tracking-widest text-amber-600">Error 419</span>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Session Expired</h1>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                Your session or security token has expired due to inactivity. Please refresh the page and try again.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm transition shadow-xs">
                Refresh Page
            </button>
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                Return to Login
            </a>
        </div>
    </div>
</div>
@endsection