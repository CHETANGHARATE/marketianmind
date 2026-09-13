@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-400 ring-1 ring-inset ring-indigo-500/20">
                    Marketing Templates
                </span>
                <span class="text-xs text-slate-500">New Template</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Create Email Template
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Author a new email template with dynamic interpolation tags for automated delivery.
            </p>
        </div>

        <div>
            <a href="{{ route('admin.marketing-templates.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                &larr; Cancel &amp; Return
            </a>
        </div>
    </div>

    <!-- Form & Legend Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.marketing-templates.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4 shadow-xs">
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                            Template Name <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                               placeholder="e.g. Inbound Lead Welcome & Free Guide">
                        @error('name')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="slug" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                Unique Slug <span class="text-slate-500 font-normal">(Optional, auto-generated)</span>
                            </label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                                   placeholder="inbound-lead-welcome">
                            @error('slug')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                                Internal Purpose
                            </label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                                   placeholder="Sent to new inbound leads 5m after capture">
                            @error('description')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                            Email Subject Line <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                               placeholder="Welcome to Marketian Mind, @{{ lead.name }}!">
                        <p class="text-[11px] text-slate-500 mt-1">Variables like <code class="text-indigo-400 font-mono">@{{ lead.name }}</code> or <code class="text-indigo-400 font-mono">@{{ user.name }}</code> are supported in the subject line.</p>
                        @error('subject')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="body_html" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                            Email Body HTML / Content <span class="text-rose-400">*</span>
                        </label>
                        <textarea id="body_html" name="body_html" rows="12" required
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500 leading-relaxed"
                                  placeholder="<h2>Hi @{{ lead.name }},</h2>&#10;<p>Thank you for reaching out to Marketian Mind.</p>&#10;<p><a href='@{{ course.url }}' class='btn'>Explore Our Courses</a></p>">{{ old('body_html') }}</textarea>
                        @error('body_html')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.marketing-templates.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-sm transition">
                            Save Template
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Variable Documentation Card -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4 shadow-xs">
                <div class="border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Personalization Variables
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-1">Insert these exact tags into the subject or body to personalize emails.</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="font-semibold text-slate-300 mb-1">Inbound Leads:</div>
                        <ul class="space-y-1 font-mono text-[11px] text-indigo-300">
                            <li>@{{ lead.name }}</li>
                            <li>@{{ lead.email }}</li>
                            <li>@{{ lead.phone }}</li>
                            <li>@{{ lead.company_name }}</li>
                            <li>@{{ lead.status }}</li>
                        </ul>
                    </div>

                    <div class="border-t border-slate-800/60 pt-3">
                        <div class="font-semibold text-slate-300 mb-1">Registered Students:</div>
                        <ul class="space-y-1 font-mono text-[11px] text-indigo-300">
                            <li>@{{ user.name }}</li>
                            <li>@{{ user.email }}</li>
                        </ul>
                    </div>

                    <div class="border-t border-slate-800/60 pt-3">
                        <div class="font-semibold text-slate-300 mb-1">Course &amp; Bundle Context:</div>
                        <ul class="space-y-1 font-mono text-[11px] text-indigo-300">
                            <li>@{{ course.title }}</li>
                            <li>@{{ course.url }}</li>
                            <li>@{{ bundle.title }}</li>
                            <li>@{{ bundle.url }}</li>
                        </ul>
                    </div>

                    <div class="border-t border-slate-800/60 pt-3">
                        <div class="font-semibold text-slate-300 mb-1">Compliance:</div>
                        <ul class="space-y-1 font-mono text-[11px] text-indigo-300">
                            <li>@{{ unsubscribe_url }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
