@extends('layouts.base')

@section('content')
<div class="min-h-screen flex bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false }">
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:w-72 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200/90 z-30">
        <!-- Brand Header -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-slate-100">
            <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2.5 font-bold text-lg text-slate-900 tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base shadow-sm">
                    M
                </span>
                <span>Marketian<span class="text-indigo-600">Mind</span></span>
            </a>
            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 border border-indigo-100">
                Student
            </span>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <!-- 1. Dashboard -->
            <a href="{{ route('student.dashboard') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.dashboard') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <!-- 2. My Learning -->
            <a href="{{ route('student.my-learning') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.my-learning') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.my-learning') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                My Learning
            </a>

            <!-- 3. Browse Courses -->
            <a href="{{ route('student.courses') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.courses') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.courses') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Browse Courses
            </a>

            <!-- 4. Wishlist -->
            <a href="{{ route('student.wishlist.index') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.wishlist.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.wishlist.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                Wishlist
            </a>

            <!-- 4. Learning Progress -->
            <a href="{{ route('student.progress') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.progress') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.progress') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Learning Progress
            </a>

            <!-- 5. Purchase History -->
            <a href="{{ route('student.orders.index') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.orders.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.orders.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Purchase History
            </a>

            <!-- 6. Referrals -->
            <a href="{{ route('student.referrals.index') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.referrals.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.referrals.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Refer Friends
            </a>

            <!-- 7. Notifications -->
            <a href="{{ route('student.notifications.index') }}"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.notifications.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 {{ request()->routeIs('student.notifications.*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>Notifications</span>
                </div>
                @if(auth()->check() && auth()->user()->unreadNotifications()->count() > 0)
                    <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">
                        {{ auth()->user()->unreadNotifications()->count() }}
                    </span>
                @endif
            </a>

            <!-- 7. Profile & Account -->
            <a href="{{ route('student.profile') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.profile*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('student.profile*') ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Profile &amp; Account
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <a href="{{ route('home') }}" class="flex items-center justify-center gap-2 w-full px-3 py-2 text-xs font-semibold text-slate-600 hover:text-indigo-600 bg-white border border-slate-200 rounded-lg shadow-xs hover:border-indigo-200 transition">
                &larr; Back to Main Website
            </a>
        </div>
    </aside>

    <!-- Mobile Sidebar Drawer (JavaScript controlled) -->
    <div id="mobile-sidebar-drawer" class="fixed inset-0 z-50 lg:hidden hidden" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"></div>

        <!-- Drawer Panel -->
        <div class="fixed inset-y-0 left-0 w-72 bg-white shadow-xl flex flex-col z-10">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-100">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 font-bold text-lg text-slate-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">
                        M
                    </span>
                    <span>Marketian Mind</span>
                </a>
                <button id="mobile-sidebar-close" type="button" class="p-2 text-slate-500 hover:text-slate-800 rounded-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Dashboard
                </a>
                <a href="{{ route('student.my-learning') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.my-learning') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    My Learning
                </a>
                <a href="{{ route('student.courses') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.courses') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Browse Courses
                </a>
                <a href="{{ route('student.wishlist.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.wishlist.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Wishlist
                </a>
                <a href="{{ route('student.progress') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.progress') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Learning Progress
                </a>
                <a href="{{ route('student.orders.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.orders.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Purchase History
                </a>
                <a href="{{ route('student.referrals.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.referrals.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Refer Friends
                </a>
                <a href="{{ route('student.notifications.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.notifications.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    <span>Notifications</span>
                    @if(auth()->check() && auth()->user()->unreadNotifications()->count() > 0)
                        <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">
                            {{ auth()->user()->unreadNotifications()->count() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('student.profile') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('student.profile*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600' }}">
                    Profile &amp; Account
                </a>
            </nav>

            <div class="p-4 border-t border-slate-100">
                <a href="{{ route('home') }}" class="block text-center w-full px-3 py-2 text-xs font-semibold text-slate-600 hover:text-indigo-600 bg-slate-50 border border-slate-200 rounded-lg">
                    &larr; Back to Main Website
                </a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="flex-1 lg:pl-72 flex flex-col min-w-0">
        <!-- Top Navigation Header -->
        <header class="sticky top-0 z-20 h-16 bg-white/95 backdrop-blur border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <!-- Mobile Menu Button -->
                <button id="mobile-sidebar-toggle" type="button" class="p-2 -ml-2 text-slate-600 hover:text-slate-900 rounded-lg lg:hidden hover:bg-slate-100">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Breadcrumb or Title -->
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <span class="text-slate-400 hidden sm:inline">Student Portal</span>
                    <span class="text-slate-300 hidden sm:inline">&rsaquo;</span>
                    <span class="font-bold text-slate-900">{{ $headerTitle ?? 'Dashboard' }}</span>
                </div>
            </div>

            <!-- Header Right Section: Student Dropdown -->
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="hidden sm:inline-flex items-center text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
                    Visit Website &rarr;
                </a>

                <!-- Notification Bell -->
                <a href="{{ route('student.notifications.index') }}" class="relative p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-xl transition" title="Notifications">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(auth()->check() && auth()->user()->unreadNotifications()->count() > 0)
                        <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </span>
                    @endif
                </a>

                <!-- Profile Dropdown Container -->
                <div class="relative" id="profile-dropdown-container">
                    <button id="profile-dropdown-toggle" type="button" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition text-left cursor-pointer" aria-expanded="false" aria-haspopup="true">
                        <!-- Avatar placeholder -->
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-sm shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left pr-1">
                            <p class="text-xs font-bold text-slate-900 leading-tight">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-[10px] font-medium text-slate-500 leading-tight">
                                Student Account
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-slate-900/5 border border-slate-100 z-50">
                        <div class="px-3 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('student.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile &amp; Account
                            </a>
                            <a href="{{ route('student.profile') }}#password-settings" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                Change Password
                            </a>
                        </div>

                        <div class="pt-1 border-t border-slate-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer text-left">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('subcontent')
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mobile Sidebar Drawer
        const toggleBtn = document.getElementById('mobile-sidebar-toggle');
        const drawer = document.getElementById('mobile-sidebar-drawer');
        const backdrop = document.getElementById('mobile-sidebar-backdrop');
        const closeBtn = document.getElementById('mobile-sidebar-close');

        if (toggleBtn && drawer) {
            toggleBtn.addEventListener('click', function () {
                drawer.classList.remove('hidden');
            });
        }

        if (closeBtn && drawer) {
            closeBtn.addEventListener('click', function () {
                drawer.classList.add('hidden');
            });
        }

        if (backdrop && drawer) {
            backdrop.addEventListener('click', function () {
                drawer.classList.add('hidden');
            });
        }

        // Profile Dropdown
        const profileToggle = document.getElementById('profile-dropdown-toggle');
        const profileMenu = document.getElementById('profile-dropdown-menu');

        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                profileMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', function (e) {
                if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                    profileMenu.classList.add('hidden');
                }
            });
        }
    });
</script>
@endsection