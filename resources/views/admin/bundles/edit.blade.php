@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Edit Course Bundle</h1>
            <p class="mt-1 text-sm text-slate-400">Update bundle details, pricing, and included curriculum.</p>
        </div>
        <div class="flex items-center gap-3">
            @if($bundle->isPublished())
                <a href="{{ route('bundles.show', $bundle) }}" target="_blank" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition">
                    View on Site &nearr;
                </a>
                <span class="text-slate-600">&bull;</span>
            @endif
            <a href="{{ route('admin.bundles.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white transition">
                &larr; Back to Bundles
            </a>
        </div>
    </div>

    <!-- Error Summary -->
    @if($errors->any())
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.bundles.update', $bundle) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Info Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Bundle Overview</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Bundle Title <span class="text-rose-400">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           id="title"
                           value="{{ old('title', $bundle->title) }}"
                           required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="slug" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        URL Slug <span class="text-slate-500 font-normal lowercase">(unique identifier)</span>
                    </label>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug', $bundle->slug) }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="short_description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Short Summary
                    </label>
                    <textarea name="short_description"
                              id="short_description"
                              rows="2"
                              class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('short_description', $bundle->short_description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Full Description
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="5"
                              class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description', $bundle->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Pricing, Status & Thumbnail Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">Pricing &amp; Display</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Bundle Price (₹ INR) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="price"
                           id="price"
                           value="{{ old('price', $bundle->price) }}"
                           required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Status <span class="text-rose-400">*</span>
                    </label>
                    <select name="status"
                            id="status"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        @foreach(\App\Enums\BundleStatus::cases() as $statusCase)
                            <option value="{{ $statusCase->value }}" {{ old('status', $bundle->status->value) === $statusCase->value ? 'selected' : '' }}>
                                {{ $statusCase->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="thumbnail" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Bundle Thumbnail Image
                    </label>
                    @if($bundle->thumbnail_url)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->title }}" class="h-16 w-28 rounded-lg object-cover bg-slate-800 border border-slate-700">
                            <span class="text-xs text-slate-400">Current thumbnail. Upload a new image to replace.</span>
                        </div>
                    @endif
                    <input type="file"
                           name="thumbnail"
                           id="thumbnail"
                           accept="image/*"
                           class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-slate-400 file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400">
                </div>

                <div class="sm:col-span-2 pt-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="featured"
                               value="1"
                               {{ old('featured', $bundle->featured) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                        <span class="text-sm font-semibold text-white">Feature this bundle on listings and highlights</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Courses Assignment Card -->
        @php
            $selectedCourseIds = old('courses', $bundle->courses->pluck('id')->toArray());
        @endphp
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-base font-bold text-white">Included Courses <span class="text-rose-400">*</span></h2>
                    <p class="text-xs text-slate-400 mt-0.5">Select all courses that are included in this bundle.</p>
                </div>
                <span class="text-xs font-semibold text-slate-400">Select at least 1 course</span>
            </div>

            <div class="grid grid-cols-1 gap-2.5 max-h-96 overflow-y-auto pr-1">
                @foreach($courses as $course)
                    <label class="flex items-center justify-between p-3 rounded-lg border border-slate-800 bg-slate-800/40 hover:bg-slate-800/80 transition cursor-pointer">
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   name="courses[]"
                                   value="{{ $course->id }}"
                                   {{ in_array($course->id, $selectedCourseIds) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-semibold text-white">{{ $course->title }}</span>
                                <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                    <span>{{ $course->category?->name ?? 'General' }}</span>
                                    <span>&bull;</span>
                                    <span class="{{ $course->isPublished() ? 'text-emerald-400' : 'text-slate-400' }}">{{ ucfirst($course->status->value) }}</span>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-amber-400 whitespace-nowrap">
                            {{ $course->formattedPrice() }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.bundles.index') }}" class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-700 transition">
                Cancel
            </a>
            <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition cursor-pointer">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
