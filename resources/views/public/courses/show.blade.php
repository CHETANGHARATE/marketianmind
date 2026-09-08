@extends('layouts.public')

@section('subcontent')
<div>
    <!-- SECTION 1: COURSE HERO -->
    <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 via-white to-white border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
                <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">&larr; Back to Courses</a>
                <span>/</span>
                <span class="text-indigo-600">Digital Marketing for Business Owners</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap items-center gap-2.5 mb-4">
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15">
                            Comprehensive Program
                        </span>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                            Coming Soon
                        </span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                        Digital Marketing for Business Owners
                    </h1>

                    <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed">
                        Learn how to grow your business online with limited time and budget. Gain complete marketing independence and master customer acquisition without relying on expensive monthly agency retainers.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-6 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900">Format:</span>
                            <span>Self-Paced Video Lessons</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900">Structure:</span>
                            <span>6 Core Modules</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900">Level:</span>
                            <span>Designed for Business Owners</span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="text-center pb-6 border-b border-slate-100">
                            <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Program Status</span>
                            <div class="mt-2 text-2xl font-black text-indigo-600">Coming Soon</div>
                            <p class="mt-1 text-xs text-slate-500">Curriculum finalized & currently in production</p>
                        </div>

                        <div class="py-6 space-y-3 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">&check;</span>
                                <span>Actionable step-by-step video lessons</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">&check;</span>
                                <span>Fill-in marketing plan templates</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">&check;</span>
                                <span>No technical background required</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">&check;</span>
                                <span>Lifetime curriculum access</span>
                            </div>
                        </div>

                        <a href="#notify-section" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                            Register Interest &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: WHAT YOU WILL LEARN -->
    <section class="py-16 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Outcomes & Takeaways</span>
                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                    What You Will Learn
                </h2>
                <p class="mt-3 text-sm text-slate-600">
                    By completing this program, you will develop a complete, working digital marketing strategy for your business.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 flex gap-4 items-start">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-xs">
                        &check;
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">Diagnose Your Core Marketing Bottleneck</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Understand whether your business needs better positioning, more qualified traffic, or a clearer conversion message.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 flex gap-4 items-start">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-xs">
                        &check;
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">Create Customer-Centric Messages</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Craft communications that speak directly to customer frustrations so prospects instantly see the value of your solution.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 flex gap-4 items-start">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-xs">
                        &check;
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">Execute Organic Social Marketing</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Build an organic social routine requiring less than 4 hours a week that reliably brings inbound customer interest.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-6 flex gap-4 items-start">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-xs">
                        &check;
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">Evaluate Marketing Performance Accurately</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Understand key conversion metrics so you never get misled by agency reports or vanity follower counts again.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: COURSE MODULES PREVIEW -->
    <section class="py-16 bg-slate-50/70 border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Curriculum Breakdown</span>
                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                    Course Modules Preview
                </h2>
                <p class="mt-3 text-sm text-slate-600">
                    Six structured modules covering the end-to-end journey of growing a business online:
                </p>
            </div>

            <div class="space-y-4">
                <!-- Module 1 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 01</span>
                        <span class="text-xs text-slate-400">Foundational Framework</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Understanding Digital Marketing</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Demystifying online channels, defining digital marketing without jargon, and mapping how online channels directly connect to business revenue.
                    </p>
                </div>

                <!-- Module 2 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 02</span>
                        <span class="text-xs text-slate-400">Customer Psychology</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Customer Understanding</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Building realistic customer personas, identifying buyer triggers, knowing where they look for answers, and conducting low-effort customer interviews.
                    </p>
                </div>

                <!-- Module 3 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 03</span>
                        <span class="text-xs text-slate-400">Platform Execution</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Social Media Marketing</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Selecting the right 1-2 platforms for your niche, profile optimization for conversions, consistent publishing calendars, and community engagement.
                    </p>
                </div>

                <!-- Module 4 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 04</span>
                        <span class="text-xs text-slate-400">Content Engine</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Content Strategy</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Developing content pillars, planning educational and trust-building themes, and creating a sustainable weekly content cadence without creative burnout.
                    </p>
                </div>

                <!-- Module 5 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 05</span>
                        <span class="text-xs text-slate-400">Asset Production</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Creating Marketing Content</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Practical guides on writing compelling copy, creating clean graphics and simple videos using free/low-cost tools, and crafting clear calls to action.
                    </p>
                </div>

                <!-- Module 6 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 06</span>
                        <span class="text-xs text-slate-400">Compounding Systems</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Growing Your Business Online</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Setting up simple lead collection, basic email follow-ups, tracking ROI, and knowing exactly when (and how) to delegate or scale with hired help.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: WHO THIS COURSE IS FOR -->
    <section class="py-16 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Audience</span>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">
                        Who This Course Is For
                    </h2>
                    <ul class="mt-6 space-y-3.5 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold mt-0.5">&check;</span>
                            <span>Small business owners seeking consistent, predictable inbound customer leads.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold mt-0.5">&check;</span>
                            <span>Early-stage startup founders who need traction before hiring marketing personnel.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold mt-0.5">&check;</span>
                            <span>Solo entrepreneurs, consultants, and service providers managing marketing alone.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold mt-0.5">&check;</span>
                            <span>Local brick-and-mortar operators seeking a commanding digital footprint.</span>
                        </li>
                    </ul>
                </div>

                <!-- SECTION 5: COURSE BENEFITS -->
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Value</span>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">
                        Key Course Benefits
                    </h2>
                    <ul class="mt-6 space-y-3.5 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold mt-0.5">&check;</span>
                            <span><strong>Agency Independence:</strong> Save thousands every month by knowing what works.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold mt-0.5">&check;</span>
                            <span><strong>Time Efficiency:</strong> Focus strictly on high-leverage activities that generate revenue.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold mt-0.5">&check;</span>
                            <span><strong>Confidence & Clarity:</strong> End the guesswork and post with purpose.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold mt-0.5">&check;</span>
                            <span><strong>Lifelong Asset:</strong> Practical knowledge stays with you across any future venture.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: COMING SOON CTA -->
    <section id="notify-section" class="py-16 bg-slate-900 text-white">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20 mb-6">
                Course In Production
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                Digital Marketing for Business Owners
            </h2>
            <p class="mt-4 text-base text-slate-300 max-w-xl mx-auto leading-relaxed">
                This course is currently being prepared with concise, high-value modules. Register your interest or contact us to be among the first to access the program when it launches.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('contact') }}" class="w-full sm:w-auto rounded-lg bg-indigo-600 px-7 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Register Interest / Contact Us
                </a>
                <a href="{{ route('courses') }}" class="w-full sm:w-auto rounded-lg border border-slate-700 bg-slate-800 px-7 py-3 text-sm font-semibold text-slate-200 hover:bg-slate-700 transition">
                    Back to All Courses
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
