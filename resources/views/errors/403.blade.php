@extends('layouts.base', ['title' => 'Access Denied - Marketian Mind'])

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-rose-500/10 text-rose-600 flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold uppercase tracking-widest text-rose-600">Error 403</span>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Access Restricted</h1>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                You do not have the required permissions to access this page or resource.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm transition shadow-xs">
                Back to Home
            </a>
            @auth
                <a href="{{ auth()->user()->dashboardUrl() }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                    Log In
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection