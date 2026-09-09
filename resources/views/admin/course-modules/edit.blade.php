@extends('layouts.admin')

@section('subcontent')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.index', $course) }}" class="hover:text-white transition truncate max-w-xs">{{ $course->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Edit Module</span>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Edit Module
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Update module details inside <span class="text-slate-200 font-semibold">"{{ $course->title }}"</span>.
            </p>
        </div>
        <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
            View Lessons ({{ $module->lessons()->count() }}) &rarr;
        </a>
    </div>

    <!-- Form -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.courses.modules.update', [$course, $module]) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Module Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Module Title <span class="text-amber-400">*</span>
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title', $module->title) }}"
                       required
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
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description', $module->description) }}</textarea>
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
                       value="{{ old('sort_order', $module->sort_order) }}"
                       min="0"
                       class="mt-1.5 w-full sm:w-36 rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                <p class="mt-1 text-[11px] text-slate-500">Modules will be displayed in ascending order (1, 2, 3...).</p>
                @error('sort_order')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                <button type="button"
                        onclick="if(confirm('Are you sure you want to delete this module? All lessons inside will be deleted.')) { document.getElementById('delete-module-form').submit(); }"
                        class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-2.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition">
                    Delete Module
                </button>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.courses.modules.index', $course) }}"
                       class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                        Update Module
                    </button>
                </div>
            </div>
        </form>

        <form id="delete-module-form" method="POST" action="{{ route('admin.courses.modules.destroy', [$course, $module]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection
