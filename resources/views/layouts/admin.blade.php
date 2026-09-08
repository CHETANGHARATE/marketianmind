@extends('layouts.base')

@section('content')
<div class="min-h-screen flex flex-col bg-slate-900 text-slate-100">
    <!-- Admin Header -->
    <header class="bg-slate-950 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded bg-amber-500 text-slate-950 font-black text-sm">
                        MM
                    </span>
                    <span>Marketian Mind</span>
                </a>
                <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-400 border border-amber-500/20">
                    Admin Management
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="text-sm font-medium text-slate-400 hover:text-white">
                    &larr; Back to Website
                </a>
            </div>
        </div>
    </header>

    <!-- Admin Main Slot -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('subcontent')
    </main>
</div>
@endsection
