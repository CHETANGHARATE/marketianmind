@extends('layouts.admin')

@section('subcontent')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.index', $course) }}" class="hover:text-white transition truncate max-w-xs">{{ $course->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">New Module</span>
    </div>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Add New Module
        </h1>
        <p class="mt-1 text-sm text-slate-400">
            Create a curriculum module inside <span class="text-slate-200 font-semibold">"{{ $course->title }}"</span>.
        </p>
    </div>

    <!-- Form -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.courses.modules.store', $course) }}" class="space-y-5">
            @csrf

            <!-- Module Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Module Title <span class="text-amber-400">*</span>
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title') }}"
                       required
                       placeholder="e.g. Module 1: Marketing Foundation & Value Proposition"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Description <span class="text-slate-500 lowercase font-normal">(optional)</span>
                </label>
                <textarea name="description"
                          id="description"
                          rows="3"
                          placeholder="What will students learn in this module..."
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sort Order -->
            <div>
                <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Sort Order / Sequence Number
                </label>
                <input type="number"
                       name="sort_order"
                       id="sort_order"
                       value="{{ old('sort_order', $nextSortOrder) }}"
                       min="0"
                       class="mt-1.5 w-full sm:w-36 rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                <p class="mt-1 text-[11px] text-slate-500">Modules will be sorted in ascending order (1, 2, 3...).</p>
                @error('sort_order')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('admin.courses.modules.index', $course) }}"
                   class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                    Save Module
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
