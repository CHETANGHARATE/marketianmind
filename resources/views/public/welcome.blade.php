@extends('layouts.public')

@section('subcontent')
<div>
    <!-- SECTION 1: HERO -->
    <section class="relative px-4 pt-16 pb-20 sm:px-6 lg:px-8 lg:pt-24 lg:pb-28 bg-gradient-to-b from-indigo-50/40 via-white to-white border-b border-slate-100">
        <div class="mx-auto max-w-4xl text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 mb-8">
                <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
                Practical Online Marketing Education
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl sm:leading-[1.15]">
                Marketing Knowledge for Business Owners.
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg sm:text-xl leading-relaxed text-slate-600">
                Learn how to grow your business online without depending on expensive marketing agencies.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('courses') }}" class="w-full sm:w-auto rounded-lg bg-indigo-600 px-7 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Explore Courses
                </a>
                <a href="{{ route('about') }}" class="w-full sm:w-auto rounded-lg border border-slate-300 bg-white px-7 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: PROBLEM -->
    <section class="py-20 bg-slate-50/60 border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">The Challenge</span>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Your Business Doesn't Need an Expensive Agency to Start Growing Online.
                </h2>
                <p class="mt-4 text-base text-slate-600 leading-relaxed">
                    Most business owners are experts at their products, craftsmanship, and daily operations. However, navigating the modern online world often feels overwhelming when you haven't mastered the core essentials:
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-5">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 mb-4 font-bold text-sm">
                        01
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Social Media Marketing</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Knowing what to post, which platform matters, and how to convert followers into paying customers.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 mb-4 font-bold text-sm">
                        02
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Content Creation</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Producing genuine, clear messages without spending hours or hiring dedicated video teams.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 mb-4 font-bold text-sm">
                        03
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Digital Marketing</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Demystifying search, websites, and funnels without confusing technical jargon.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 mb-4 font-bold text-sm">
                        04
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Online Customer Acquisition</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Consistently finding and attracting prospective buyers without burning marketing budget on vanity metrics.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 mb-4 font-bold text-sm">
                        05
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Brand Communication</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Clearly communicating your value so your customers understand why you are their best choice.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: MARKETIAN MIND SOLUTION -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">The Solution</span>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl leading-tight">
                        Learn the Marketing Skills Your Business Actually Needs.
                    </h2>
                    <p class="mt-5 text-base text-slate-600 leading-relaxed">
                        Marketian Mind provides practical and easy-to-understand marketing education designed specifically for business owners.
                    </p>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        We skip theoretical lectures and complex academic frameworks. Instead, we equip you with real-world execution knowledge so you can confidently make marketing decisions, generate leads, and grow your revenue independently.
                    </p>
                    <div class="mt-8 flex items-center gap-4">
                        <a href="{{ route('courses') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-600 transition">
                            Explore Curriculum &rarr;
                        </a>
                        <a href="{{ route('about') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition">
                            Read Our Philosophy
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 shadow-sm space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-white font-bold text-xs">
                                &check;
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Directly Applicable Frameworks</h4>
                                <p class="text-xs text-slate-500 mt-1">
                                    Every lesson is built to be implemented immediately on your actual business.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-white font-bold text-xs">
                                &check;
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Budget-Friendly Independence</h4>
                                <p class="text-xs text-slate-500 mt-1">
                                    Stop spending monthly retainers on agencies before you even understand your own customer journey.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-white font-bold text-xs">
                                &check;
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Simple, Clear Language</h4>
                                <p class="text-xs text-slate-500 mt-1">
                                    Zero agency buzzwords. Just honest, practical marketing principles that work.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: WHAT YOU WILL LEARN -->
    <section class="py-20 bg-slate-50/50 border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Core Curriculum</span>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    What You Will Learn
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    Essential marketing competencies broken down specifically for small business owners and startup founders.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Social Media Marketing</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Learn how to choose the right social platforms for your specific business, publish consistently, and build trust with your target market.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Understanding Customers</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Uncover who your ideal customers are, what problems keep them up at night, and what triggers them to buy from you instead of competitors.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Content Strategy</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Create high-impact content that answers customer questions, establishes your expertise, and drives organic inquiries on autopilot.
                    </p>
                </div>

                <!-- Card 4 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        4
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Digital Marketing Basics</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Master the foundational concepts of online visibility, landing pages, email follow-ups, and lead conversion without getting bogged down in tech.
                    </p>
                </div>

                <!-- Card 5 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        5
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Brand Communication</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Craft a clear, compelling value proposition that articulates exactly why your business matters and turns satisfied clients into brand advocates.
                    </p>
                </div>

                <!-- Card 6 -->
                <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-md transition">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-sm mb-5">
                        6
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Online Business Growth</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Develop a sustainable, repeatable marketing routine that fits into a busy founder's schedule and scales alongside your business.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: FEATURED COURSE -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Flagship Program</span>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Featured Course
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    Our comprehensive step-by-step masterclass built specifically for time-constrained business operators.
                </p>
            </div>

            <div class="max-w-3xl mx-auto">
                <x-course-card
                    title="Digital Marketing for Business Owners"
                    description="Learn how to grow your business online with limited time and budget. Gain full control over your customer acquisition without depending entirely on expensive agencies."
                    badge="Coming Soon"
                    modules="6 Core Modules"
                    audience="Small Business Owners & Startup Founders"
                    :url="route('course.details')"
                    :featured="true"
                />
            </div>
        </div>
    </section>

    <!-- SECTION 6: WHO IS THIS FOR? -->
    <section class="py-20 bg-slate-50/50 border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Target Audience</span>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Who Is Marketian Mind For?
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    Tailored specifically for operators who need pragmatic results, not marketing jargon.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.614A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.015a2.993 2.993 0 0 0 2.25 1.015c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 8.651h.008v.008H3.75v-.008Zm0-3h.008v.008H3.75v-.008Zm0-3h.008v.008H3.75v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Small Business Owners</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Looking to generate steady inquiries without high ongoing agency overhead.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Startup Founders</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Early-stage builders testing product-market fit and searching for initial traction.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Entrepreneurs</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Solo operators launching products or services who need direct marketing control.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Local Business Owners</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Stores, clinics, agencies, and consultants looking to capture their immediate local catchment area.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">First-Time Business Owners</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        New founders needing a clear, reliable compass for their very first marketing steps.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 7: WHY MARKETIAN MIND? -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">The Difference</span>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Why Marketian Mind?
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    We designed this platform specifically to address the frustrations business owners experience with typical marketing courses.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="p-6 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="font-black text-indigo-600 text-lg mb-2">01</div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Practical Learning</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Step-by-step guidance you can execute right away on your business today.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="font-black text-indigo-600 text-lg mb-2">02</div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Simple Language</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Clear, plain concepts without confusing agency buzzwords and acronyms.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="font-black text-indigo-600 text-lg mb-2">03</div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Business-Focused</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Focuses on revenue, leads, and paying clients, not vanity likes and views.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="font-black text-indigo-600 text-lg mb-2">04</div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Affordable Learning</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Fraction of the cost of one month of an agency retainer, with lifelong value.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-slate-200 bg-slate-50">
                    <div class="font-black text-indigo-600 text-lg mb-2">05</div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">No Theory Fluff</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        No 1970s textbook models. Only modern tactics that work in today's digital market.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 8: FINAL CTA -->
    <section class="py-20 bg-slate-900 text-white">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold tracking-tight sm:text-5xl">
                Stop Guessing Your Marketing. Start Understanding It.
            </h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Take control of your business growth with practical knowledge designed for small business owners and startup founders.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('courses') }}" class="w-full sm:w-auto rounded-lg bg-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Start Learning
                </a>
                <a href="{{ route('contact') }}" class="w-full sm:w-auto rounded-lg border border-slate-700 bg-slate-800 px-8 py-3.5 text-base font-semibold text-slate-200 hover:bg-slate-700 transition">
                    Have Questions? Contact Us
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
