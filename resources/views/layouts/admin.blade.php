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

                    <!-- Instructors -->
                    <a href="{{ route('admin.instructors.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.instructors.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.instructors.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Instructors
                    </a>

                    <!-- Course Analytics -->
                    <a href="{{ route('admin.analytics.courses') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.analytics.courses*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.analytics.courses*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Course Analytics
                    </a>
                </div>
            </div>

            <!-- Orders & Commerce Section -->
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Commerce & Sales
                </p>
                <div class="space-y-1">
                    <a href="{{ route('admin.leads.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.leads.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.leads.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>Leads &amp; Inquiries</span>
                        </div>
                        @php
                            $pendingLeadsCount = \Illuminate\Support\Facades\Schema::hasTable('leads') ? \App\Models\Lead::where('status', 'new')->count() : 0;
                        @endphp
                        @if($pendingLeadsCount > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-slate-950">
                                {{ $pendingLeadsCount }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.orders.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.orders.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Orders & Transactions
                    </a>
                    <a href="{{ route('admin.coupons.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.coupons.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.coupons.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Coupons & Discounts
                    </a>
                    <a href="{{ route('admin.referrals.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.referrals.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.referrals.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Referrals
                    </a>
                    <a href="{{ route('admin.reports.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.reports.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.reports.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Business Reports
                    </a>
                </div>
            </div>

            <!-- Students & Enrollments Section -->
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Students & Enrollments
                </p>
                <div class="space-y-1">
                    <a href="{{ route('admin.students.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.students.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.students.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Students
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.enrollments.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.enrollments.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Enrollments
                    </a>
                    <a href="{{ route('admin.certificates.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.certificates.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.certificates.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        Certificates
                    </a>
                    <a href="{{ route('admin.reviews.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.reviews.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.reviews.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        Course Reviews
                    </a>
                    <a href="{{ route('admin.announcements.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.announcements.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.announcements.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        Announcements
                    </a>
                </div>
            </div>

            <!-- Governance & Security Section -->
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Governance & Security
                </p>
                <div class="space-y-1">
                    <a href="{{ route('admin.audit_logs.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.audit_logs.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.audit_logs.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Audit Logs
                    </a>
                    <a href="{{ route('admin.mail.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.mail.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.mail.*') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Mail &amp; SMTP
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
                    <a href="{{ route('admin.instructors.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.instructors.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Instructors
                    </a>
                    <a href="{{ route('admin.analytics.courses') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.analytics.courses*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Course Analytics
                    </a>
                    <a href="{{ route('admin.leads.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.leads.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Leads &amp; Inquiries
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.orders.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Orders & Transactions
                    </a>
                    <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.coupons.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Coupons & Discounts
                    </a>
                    <a href="{{ route('admin.referrals.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.referrals.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Referrals
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.reports.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Business Reports
                    </a>
                    <a href="{{ route('admin.students.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.students.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Students
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.enrollments.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Enrollments
                    </a>
                    <a href="{{ route('admin.certificates.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.certificates.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Certificates
                    </a>
                    <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.reviews.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Course Reviews
                    </a>
                    <a href="{{ route('admin.announcements.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.announcements.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Announcements
                    </a>
                    <a href="{{ route('admin.audit_logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.audit_logs.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Audit Logs
                    </a>
                    <a href="{{ route('admin.mail.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.mail.*') ? 'bg-amber-500/20 text-amber-400' : 'text-slate-300' }}">
                        Mail &amp; SMTP
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
                        @if(request()->routeIs('admin.leads.*'))
                            Leads &amp; Inquiries
                        @elseif(request()->routeIs('admin.audit_logs.*'))
                            Audit Logs
                        @elseif(request()->routeIs('admin.analytics.*'))
                            Course Analytics
                        @elseif(request()->routeIs('admin.courses.*'))
                            Course Management
                        @elseif(request()->routeIs('admin.categories.*'))
                            Category Management
                        @elseif(request()->routeIs('admin.instructors.*'))
                            Instructors
                        @elseif(request()->routeIs('admin.orders.*'))
                            Commerce & Orders
                        @elseif(request()->routeIs('admin.students.*'))
                            Student Management
                        @elseif(request()->routeIs('admin.enrollments.*'))
                            Enrollment Management
                        @elseif(request()->routeIs('admin.certificates.*'))
                            Certificate Management
                        @elseif(request()->routeIs('admin.announcements.*'))
                            Announcements
                        @elseif(request()->routeIs('admin.mail.*'))
                            Mail Configuration
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
