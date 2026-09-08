@extends('layouts.base')

@section('content')
<div class="min-h-screen flex flex-col bg-slate-100">
    <!-- Student Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-slate-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded bg-indigo-600 text-white font-bold text-sm">
                        M
                    </span>
                    <span>Marketian Mind</span>
                </a>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                    Student Portal
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    &larr; Back to Website
                </a>
            </div>
        </div>
    </header>

    <!-- Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('subcontent')
    </main>
</div>
@endsection
