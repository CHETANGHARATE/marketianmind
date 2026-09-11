@extends('layouts.public')

@section('subcontent')
<div>
    <!-- Contact Page Header -->
    <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 to-white border-b border-slate-200">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 mb-6">
                Get In Touch
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                Contact Marketian Mind
            </h1>
            <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Have questions about our practical marketing curriculum or want to register early interest for our upcoming courses? We'd love to hear from you.
            </p>
        </div>
    </section>

    <!-- Main Contact Section -->
    <section class="py-16 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Left: Contact Form UI -->
                <div class="lg:col-span-7">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900 mb-2">Send Us a Message</h2>
                        <p class="text-xs text-slate-500 mb-8">
                            Fill out the form below and our team will get back to you within 1-2 business days.
                        </p>

                        @if(session('success'))
                            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3">
                                <svg class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-emerald-900">Message Received</h4>
                                    <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                            @csrf

                            <!-- Honeypot Anti-Spam Field -->
                            <div style="display:none !important;" aria-hidden="true">
                                <label for="website">Website</label>
                                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                        Your Name <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        placeholder="e.g. Alex Sharma"
                                        required
                                        class="w-full rounded-lg border @error('name') border-rose-400 @else border-slate-300 @enderror px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                    >
                                    @error('name')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                        Email Address <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="alex@yourbusiness.com"
                                        required
                                        class="w-full rounded-lg border @error('email') border-rose-400 @else border-slate-300 @enderror px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                    >
                                    @error('email')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                        Phone / WhatsApp <span class="text-slate-400 font-normal lowercase">(optional)</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="phone"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        placeholder="+91 98765 43210"
                                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                    >
                                    @error('phone')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="course_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                        Related Course <span class="text-slate-400 font-normal lowercase">(optional)</span>
                                    </label>
                                    <select
                                        id="course_id"
                                        name="course_id"
                                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                    >
                                        <option value="">-- General Platform Inquiry --</option>
                                        @if(isset($courses))
                                            @foreach($courses as $course)
                                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                                    {{ $course->title }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('course_id')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="subject" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Subject <span class="text-slate-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input
                                    type="text"
                                    id="subject"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    placeholder="e.g. Question about curriculum for local businesses"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                >
                                @error('subject')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="message" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                    Message <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="5"
                                    placeholder="Tell us about your business, what challenges you are facing, or any questions you have..."
                                    required
                                    class="w-full rounded-lg border @error('message') border-rose-400 @else border-slate-300 @enderror px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600"
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-7 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer"
                                >
                                    <span>Send Inquiry</span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Information & Community Placeholders -->
                <div class="lg:col-span-5 space-y-8">
                    <!-- Direct Contact Card -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Direct Support</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-2 mb-4">Email Inquiries</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            For course inquiries, feedback, or partnerships, feel free to reach out to our team directly:
                        </p>
                        <a href="mailto:hello@marketianmind.com" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <span>hello@marketianmind.com</span>
                        </a>
                    </div>

                    <!-- Social Media Placeholder Card -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Connect With Us</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-2 mb-4">Social Media & Community</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-6">
                            Follow our bite-sized marketing tips, case studies, and updates for founders across social channels:
                        </p>

                        <div class="space-y-3 text-xs font-medium text-slate-700">
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-slate-200">
                                <span>LinkedIn</span>
                                <span class="text-[11px] text-slate-400">@marketianmind</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-slate-200">
                                <span>YouTube</span>
                                <span class="text-[11px] text-slate-400">@MarketianMind</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-white border border-slate-200">
                                <span>X (Twitter)</span>
                                <span class="text-[11px] text-slate-400">@marketianmind</span>
                            </div>
                        </div>
                    </div>

                    <!-- Philosophy reminder -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white">
                        <p class="text-xs text-slate-500 italic leading-relaxed">
                            &ldquo;Our mission is to help small business owners build real marketing competence without paying exorbitant agency fees.&rdquo;
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
