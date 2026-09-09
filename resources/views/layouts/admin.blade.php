@extends('layouts.base')

@section('content')
<div class="min-h-screen flex bg-slate-950 text-slate-100">
    <!-- Desktop Admin Sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-slate-900 border-r border-slate-800 z-30">
        <!-- Brand Header -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-slate-800/80 bg-slate-950">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 font-bold text-base text-white tracking-tight">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-sm shadow-xs">
                    MM
                </span>
                <span>Marketian <span class="text-amber-400">Admin</span></span>
            </a>
            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400 border border-amber-500/20">
                Staff
            </span>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-3.5 py-6 space-y-6 overflow-y-auto">
            <!-- Core Section -->
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Overview
                </p>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>

            <!-- Course Management Section -->
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Course Management
                </p>
                <div class="space-y-1">
                    <!-- Courses List -->
                    <a href="{{ route('admin.courses.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.courses.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.courses.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Courses
                    </a>

                    <!-- Course Categories -->
                    <a href="{{ route('admin.categories.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.categories.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Categories
                    </a>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/60 space-y-2">
            <a href="{{ route('home') }}" class="flex items-center justify-center gap-2 w-full px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 rounded-lg hover:border-slate-700 transition">
                &larr; Back to Website
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center justify-center gap-2 w-full px-3 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 bg-rose-500/10 border border-rose-500/20 rounded-lg hover:bg-rose-500/20 transition">
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile Drawer -->
    <div id="admin-mobile-drawer" class="fixed inset-0 z-50 lg:hidden hidden" role="dialog" aria-modal="true">
        <div id="admin-drawer-backdrop" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs"></div>
        <div class="fixed inset-y-0 left-0 w-64 bg-slate-900 shadow-2xl flex flex-col z-10 border-r border-slate-800">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-800 bg-slate-950">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-bold text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-sm">
                        MM
                    </span>
                    <span>Marketian Admin</span>
                </a>
                <button id="admin-drawer-close" type="button" class="p-2 text-slate-400 hover:text-white rounded-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-4 overflow-y-auto">
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.courses.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.courses.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Courses
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.categories.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Categories
                    </a>
                </div>
            </nav>

            <div class="p-4 border-t border-slate-800 bg-slate-950 space-y-2">
                <a href="{{ route('home') }}" class="block text-center px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 rounded-lg">
                    &larr; Back to Website
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-center px-3 py-2 text-xs font-semibold text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-lg">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
        <!-- Top Navigation Header -->
        <header class="sticky top-0 z-20 h-16 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <button id="admin-drawer-open" type="button" class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="font-semibold text-slate-300">Admin Control</span>
                    <span>/</span>
                    <span class="text-amber-400 font-medium">
                        @if(request()->routeIs('admin.courses.*'))
                            Course Management
                        @elseif(request()->routeIs('admin.categories.*'))
                            Category Management
                        @else
                            Dashboard
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-block text-xs font-semibold text-slate-300">
                        {{ auth()->user()->name }}
                    </span>
                    <div class="h-8 w-8 rounded-full bg-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages & Alerts Container -->
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            @if(session('success'))
                <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-emerald-300">Success</p>
                        <p class="text-xs text-emerald-400/90 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-rose-300">Error</p>
                        <p class="text-xs text-rose-400/90 mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 mb-6">
                    <div class="flex items-center gap-2 text-sm font-semibold text-rose-300 mb-1">
                        <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Please correct the following errors:
                    </div>
                    <ul class="list-disc list-inside text-xs text-rose-400 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Page Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            @yield('subcontent')
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const drawer = document.getElementById('admin-mobile-drawer');
        const openBtn = document.getElementById('admin-drawer-open');
        const closeBtn = document.getElementById('admin-drawer-close');
        const backdrop = document.getElementById('admin-drawer-backdrop');

        function openDrawer() {
            if (drawer) drawer.classList.remove('hidden');
        }

        function closeDrawer() {
            if (drawer) drawer.classList.add('hidden');
        }

        if (openBtn) openBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
    });
</script>
@endsection
