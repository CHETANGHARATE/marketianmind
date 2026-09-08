@extends('layouts.public')

@section('subcontent')
<div>
    <!-- About Hero -->
    <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 to-white border-b border-slate-200">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 mb-6">
                About Marketian Mind
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                Built to Empower Business Owners With Marketing Independence.
            </h1>
            <p class="mt-6 text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Marketian Mind is a practical online marketing education platform created specifically for small business owners, startup founders, and local entrepreneurs.
            </p>
        </div>
    </section>

    <!-- The Core Philosophy -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-8 sm:p-12 text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Our Core Philosophy</span>
                <blockquote class="mt-4 text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    &ldquo;Marketing is not just posting on social media.&rdquo;
                </blockquote>
                <p class="mt-4 text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Posting random reels or graphics without a clear strategy leads to founder burnout and zero revenue. True business marketing is the systemic engine of understanding, attracting, and serving customers profitably.
                </p>
            </div>

            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    What Effective Marketing Actually Involves
                </h2>
                <p class="mt-3 text-sm text-slate-600">
                    At Marketian Mind, we structure our entire curriculum around the five foundational pillars of sustainable business growth:
                </p>
            </div>

            <!-- The 5 Pillars -->
            <div class="space-y-6">
                <!-- Pillar 1 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col sm:flex-row gap-6 items-start shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base">
                        01
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">1. Understanding Customers</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Marketing begins with deep empathy for your buyer. Who are they? What urgent pains or desires drive them? When you truly understand your customer, selling becomes a natural conversation rather than a struggle.
                        </p>
                    </div>
                </div>

                <!-- Pillar 2 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col sm:flex-row gap-6 items-start shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base">
                        02
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">2. Creating the Right Communication</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Clear communication beats clever copywriting every single time. Learn how to explain what you offer, how it solves problems, and why customers should act now—clearly and honestly.
                        </p>
                    </div>
                </div>

                <!-- Pillar 3 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col sm:flex-row gap-6 items-start shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base">
                        03
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">3. Building Brand Perception</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Branding isn't just a color palette or logo. It is the reputation, credibility, and trust your business holds in the mind of the prospect before and after they purchase.
                        </p>
                    </div>
                </div>

                <!-- Pillar 4 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col sm:flex-row gap-6 items-start shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base">
                        04
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">4. Choosing the Right Marketing Channels</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            You don't need to be everywhere. You only need to be where your high-value customers spend time. We teach you how to select and master the specific 1 or 2 channels that produce 80% of your business results.
                        </p>
                    </div>
                </div>

                <!-- Pillar 5 -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col sm:flex-row gap-6 items-start shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base">
                        05
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">5. Growing Strategically</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Growth must be sustainable. Instead of sporadic bursts of promotion, build a repeatable marketing engine that compounds over weeks, months, and years.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Agency vs Self-Mastery Comparison -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">The Marketian Mind Advantage</span>
                <h2 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Why Founders Learn Marketing Themselves First
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="rounded-xl border border-rose-200 bg-white p-8 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <span class="text-rose-500 font-bold">&times;</span>
                        Relying Blindly on Agencies Too Early
                    </h3>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <span class="text-rose-500 font-bold mt-0.5">&bull;</span>
                            <span>High monthly retainers before product-market fit is validated.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-rose-500 font-bold mt-0.5">&bull;</span>
                            <span>Agencies rarely understand the nuances of your unique customer like you do.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-rose-500 font-bold mt-0.5">&bull;</span>
                            <span>Reports filled with vanity metrics (impressions, clicks) instead of actual revenue.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-rose-500 font-bold mt-0.5">&bull;</span>
                            <span>If the agency contract ends, you are left with zero internal marketing capability.</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-white p-8 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <span class="text-emerald-500 font-bold">&check;</span>
                        Learning Practical Marketing With Marketian Mind
                    </h3>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-500 font-bold mt-0.5">&bull;</span>
                            <span>Affordable, one-time education with permanent knowledge retention.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-500 font-bold mt-0.5">&bull;</span>
                            <span>Direct control over your business voice, positioning, and customer acquisition.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-500 font-bold mt-0.5">&bull;</span>
                            <span>Laser focus on real leads, inquiries, sales conversations, and profit margins.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-500 font-bold mt-0.5">&bull;</span>
                            <span>When you eventually hire an agency or team later, you can manage them intelligently.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="py-16 bg-white text-center">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Ready to take control of your business marketing?
            </h2>
            <p class="mt-3 text-sm text-slate-600 max-w-xl mx-auto">
                Explore our upcoming course curriculum and discover practical marketing knowledge tailored for operators.
            </p>
            <div class="mt-8 flex items-center justify-center gap-4">
                <a href="{{ route('courses') }}" class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Explore Courses
                </a>
                <a href="{{ route('contact') }}" class="rounded-lg border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Contact Us
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
