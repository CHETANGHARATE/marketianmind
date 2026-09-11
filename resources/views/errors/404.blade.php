@extends('layouts.base', ['title' => 'Page Not Found - Marketian Mind'])

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <span class="text-xs font-bold uppercase tracking-widest text-amber-600">Error 404</span>
            <h1 class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">Page Not Found</h1>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm transition shadow-xs">
                Back to Home
            </a>
            <a href="{{ route('courses') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                Browse Courses
            </a>
        </div>
    </div>
</div>
@endsection