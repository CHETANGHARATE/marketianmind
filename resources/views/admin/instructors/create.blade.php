@extends('layouts.admin')

@section('subcontent')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.instructors.index') }}" class="hover:text-white transition">Instructors</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">New Instructor</span>
    </div>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Add New Instructor
        </h1>
        <p class="mt-1 text-sm text-slate-400">
            Create an instructor profile that can be assigned to courses.
        </p>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.instructors.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                1. Personal &amp; Professional Info
            </h2>

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Full Name <span class="text-amber-400">*</span>
                </label>
                <input type="text"
                       name="name"
                       id="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Chetan Gharate"
                       required
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Slug <span class="text-slate-500 lowercase font-normal">(optional, auto-generated from name)</span>
                </label>
                <input type="text"
                       name="slug"
                       id="slug"
                       value="{{ old('slug') }}"
                       placeholder="e.g. chetan-gharate"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 font-mono">
                @error('slug')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title / Headline -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Professional Title / Headline <span class="text-slate-500 lowercase font-normal">(optional)</span>
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title') }}"
                       placeholder="e.g. Performance Marketer &amp; Growth Consultant"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Biography / Background <span class="text-slate-500 lowercase font-normal">(optional)</span>
                </label>
                <textarea name="bio"
                          id="bio"
                          rows="4"
                          placeholder="Brief biography outlining experience, areas of expertise, and teaching background..."
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('bio') }}</textarea>
                @error('bio')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Avatar -->
            <div>
                <label for="avatar" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Profile Photo / Avatar <span class="text-slate-500 lowercase font-normal">(optional)</span>
                </label>
                <input type="file"
                       name="avatar"
                       id="avatar"
                       accept="image/png,image/jpeg,image/jpg,image/webp"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer">
                <p class="mt-1 text-[11px] text-slate-500">Accepted: PNG, JPG, JPEG, WEBP (Max 2 MB).</p>
                @error('avatar')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                2. Links &amp; Status
            </h2>

            <!-- Website -->
            <div>
                <label for="website_url" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Website URL
                </label>
                <input type="url"
                       name="website_url"
                       id="website_url"
                       value="{{ old('website_url') }}"
                       placeholder="https://yourdomain.com"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('website_url')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- LinkedIn -->
            <div>
                <label for="linkedin_url" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    LinkedIn Profile URL
                </label>
                <input type="url"
                       name="linkedin_url"
                       id="linkedin_url"
                       value="{{ old('linkedin_url') }}"
                       placeholder="https://linkedin.com/in/username"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('linkedin_url')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Twitter / X -->
            <div>
                <label for="twitter_url" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Twitter / X Profile URL
                </label>
                <input type="url"
                       name="twitter_url"
                       id="twitter_url"
                       value="{{ old('twitter_url') }}"
                       placeholder="https://x.com/username"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('twitter_url')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Checkbox -->
            <div class="pt-2">
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm font-semibold text-slate-200">Active (Visible for course assignment)</span>
                </label>
            </div>
        </div>

        <!-- Submit & Cancel -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.instructors.index') }}"
               class="rounded-xl border border-slate-700 bg-slate-950 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                Save Instructor
            </button>
        </div>
    </form>
</div>
@endsection