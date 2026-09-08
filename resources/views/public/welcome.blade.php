@extends('layouts.public')

@section('subcontent')
<div class="relative overflow-hidden">
    <!-- Hero Section -->
    <section class="relative px-4 pt-16 pb-20 sm:px-6 lg:px-8 lg:pt-24 lg:pb-28 bg-gradient-to-b from-indigo-50/50 to-white">
        <div class="mx-auto max-w-5xl text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-100/80 px-3.5 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-inset ring-indigo-200 mb-8">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Platform Foundation Ready
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl">
                Master Practical Marketing for
                <span class="text-indigo-600 block sm:inline">Small Businesses & Startups</span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                Marketian Mind provides actionable, real-world online marketing education designed to help entrepreneurs, small business owners, and startup founders attract customers and scale predictably.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="#foundation-status" class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Explore Platform Status
                </a>
                <a href="{{ route('student.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Student Portal Preview
                </a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Admin Portal Preview
                </a>
            </div>
        </div>
    </section>

    <!-- Foundation & Readiness Verification Panel -->
    <section id="foundation-status" class="py-16 bg-white border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600">System Architecture</h2>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Project Foundation Verification</p>
                <p class="mt-3 text-sm text-slate-500">
                    The core stack and directory hierarchy are established in accordance with Laravel standards and ready for Hostinger deployment.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1: Laravel -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-600 font-bold text-sm">
                            LV
                        </div>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            Active
                        </span>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Laravel Framework</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Running on PHP 8.3 with clean modular architecture and standard routing.
                    </p>
                </div>

                <!-- Card 2: Tailwind CSS & Vite -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 font-bold text-sm">
                            TW
                        </div>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            Compiled
                        </span>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Tailwind CSS & Vite</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Modern responsive utility styling with production bundle support.
                    </p>
                </div>

                <!-- Card 3: MySQL Readiness -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold text-sm">
                            SQL
                        </div>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            Configured
                        </span>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">MySQL Ready</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Prepared for MySQL integration with secure environment variables and standard migrations.
                    </p>
                </div>

                <!-- Card 4: Hostinger Deployment -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-700 font-bold text-sm">
                            HST
                        </div>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            Ready
                        </span>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Hostinger Ready</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Configured for shared hosting with standard public entry point and static asset builds.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Planned Platform Capabilities Overview -->
    <section id="features" class="py-16 bg-slate-50 border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600">Roadmap</h2>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Core Platform Features</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="rounded-lg bg-white p-6 shadow-sm border border-slate-200">
                    <div class="font-semibold text-slate-900 text-base mb-2">1. Explore Courses</div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Curated curriculum focused on customer acquisition, performance marketing, and branding.
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm border border-slate-200">
                    <div class="font-semibold text-slate-900 text-base mb-2">2. Watch Video Lessons</div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        High-quality video modules with actionable breakdowns, templates, and downloadable resources.
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm border border-slate-200">
                    <div class="font-semibold text-slate-900 text-base mb-2">3. Track Progress</div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Dedicated student dashboard to monitor lesson completions, course milestones, and certificates.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
