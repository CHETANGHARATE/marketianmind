@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition mb-2">
                &larr; Back to Course List
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Edit Course: {{ $course->title }}
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Update course information, pricing, thumbnail, and publication status.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('admin.courses.modules.index', $course) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500/15 border border-amber-500/30 px-4 py-2.5 text-sm font-bold text-amber-400 hover:bg-amber-500/25 transition">
                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Manage Curriculum / Modules ({{ $course->modules()->count() }})
            </a>
        </div>
    </div>

    <!-- Course Edit Form -->
    <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. Basic Information Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                1. Basic Information
            </h2>

            <!-- Course Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Course Title <span class="text-amber-400">*</span>
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title', $course->title) }}"
                       required
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Course Slug -->
            <div>
                <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Custom Slug <span class="text-slate-500 lowercase font-normal">(must be unique)</span>
                </label>
                <div class="mt-1.5 flex rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                    <span class="inline-flex items-center px-3 text-xs text-slate-500 bg-slate-900 border-r border-slate-800 select-none">
                        /courses/
                    </span>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug', $course->slug) }}"
                           class="w-full bg-transparent px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-hidden font-mono">
                </div>
                @error('slug')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Short Description -->
            <div>
                <label for="short_description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Short Description / Excerpt <span class="text-amber-400">*</span>
                </label>
                <textarea name="short_description"
                          id="short_description"
                          rows="2"
                          required
                          maxlength="500"
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('short_description', $course->short_description) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-500">Maximum 500 characters.</p>
                @error('short_description')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Full Description -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Full Description
                </label>
                <textarea name="description"
                          id="description"
                          rows="5"
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 2. Organization & Instructor Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                2. Category & Instructor
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Course Category -->
                <div>
                    <label for="course_category_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Category
                    </label>
                    <select name="course_category_id"
                            id="course_category_id"
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        <option value="">-- No Category (Uncategorized) --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) old('course_category_id', $course->course_category_id) === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_category_id')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Instructor Name -->
                <div>
                    <label for="instructor_name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Instructor Name
                    </label>
                    <input type="text"
                           name="instructor_name"
                           id="instructor_name"
                           value="{{ old('instructor_name', $course->instructor_name) }}"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    @error('instructor_name')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 3. Pricing & Monetization Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                3. Course Pricing
            </h2>

            <!-- Free vs Paid Toggle -->
            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio"
                           name="is_free"
                           id="pricing_paid"
                           value="0"
                           {{ old('is_free', $course->is_free ? '1' : '0') === '0' ? 'checked' : '' }}
                           class="h-4 w-4 text-amber-500 bg-slate-950 border-slate-700 focus:ring-amber-500">
                    <span class="text-sm font-semibold text-white">Paid Course</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio"
                           name="is_free"
                           id="pricing_free"
                           value="1"
                           {{ old('is_free', $course->is_free ? '1' : '0') === '1' ? 'checked' : '' }}
                           class="h-4 w-4 text-amber-500 bg-slate-950 border-slate-700 focus:ring-amber-500">
                    <span class="text-sm font-semibold text-white">Free Course</span>
                </label>
            </div>

            <!-- Pricing Fields Container -->
            <div id="pricing_fields" class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 {{ old('is_free', $course->is_free ? '1' : '0') === '1' ? 'hidden' : '' }}">
                <!-- Regular Price -->
                <div>
                    <label for="price" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Regular Price (₹ INR) <span class="text-amber-400">*</span>
                    </label>
                    <div class="mt-1.5 relative rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                            ₹
                        </span>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="price"
                               id="price"
                               value="{{ old('price', $course->price) }}"
                               class="w-full bg-transparent pl-8 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-hidden">
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Discount Price -->
                <div>
                    <label for="discount_price" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Discount / Offer Price (₹ INR) <span class="text-slate-500 lowercase font-normal">(optional)</span>
                    </label>
                    <div class="mt-1.5 relative rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                            ₹
                        </span>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="discount_price"
                               id="discount_price"
                               value="{{ old('discount_price', $course->discount_price) }}"
                               class="w-full bg-transparent pl-8 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-hidden">
                    </div>
                    @error('discount_price')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 4. Publishing & Meta Settings -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                4. Status, Duration & Visibility
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Publication Status <span class="text-amber-400">*</span>
                    </label>
                    <select name="status"
                            id="status"
                            required
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        <option value="draft" {{ old('status', $course->status->value) === 'draft' ? 'selected' : '' }}>Draft (Hidden from public)</option>
                        <option value="published" {{ old('status', $course->status->value) === 'published' ? 'selected' : '' }}>Published (Live on website)</option>
                        <option value="archived" {{ old('status', $course->status->value) === 'archived' ? 'selected' : '' }}>Archived (Deprecated)</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estimated Duration -->
                <div>
                    <label for="estimated_duration" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Estimated Duration
                    </label>
                    <input type="text"
                           name="estimated_duration"
                           id="estimated_duration"
                           value="{{ old('estimated_duration', $course->estimated_duration) }}"
                           placeholder="e.g. 6 hours"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    @error('estimated_duration')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Featured Course Toggle -->
            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="featured"
                           id="featured"
                           value="1"
                           {{ old('featured', $course->featured) ? 'checked' : '' }}
                           class="h-4 w-4 rounded-sm border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-white">Mark as Featured Course</span>
                        <p class="text-xs text-slate-400">Featured courses appear with priority badges on the home page and browse catalog.</p>
                    </div>
                </label>
                @error('featured')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 5. Thumbnail Upload Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                5. Course Thumbnail
            </h2>

            <div>
                <label for="thumbnail" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                    Replace Image <span class="text-slate-500 lowercase font-normal">(leave blank to keep current thumbnail)</span>
                </label>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div id="preview-container" class="h-24 w-36 rounded-lg border border-slate-800 bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                        @if($course->thumbnailUrl())
                            <img id="image-preview" src="{{ $course->thumbnailUrl() }}" alt="Current Thumbnail" class="h-full w-full object-cover">
                        @else
                            <span id="preview-placeholder" class="text-xs text-slate-600 font-semibold">No Image</span>
                            <img id="image-preview" src="#" alt="Thumbnail Preview" class="h-full w-full object-cover hidden">
                        @endif
                    </div>

                    <div class="flex-1 w-full">
                        <input type="file"
                               name="thumbnail"
                               id="thumbnail"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 file:cursor-pointer cursor-pointer">
                        <p class="mt-1 text-[11px] text-slate-500">Supported formats: JPG, PNG, WebP up to 3MB.</p>
                    </div>
                </div>
                @error('thumbnail')
                    <p class="mt-2 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="flex items-center justify-between pt-4">
            <button type="button"
                    onclick="if(confirm('Are you sure you want to delete this course? All associated modules and lessons will also be deleted.')) { document.getElementById('delete-course-form').submit(); }"
                    class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-2.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition">
                Delete Course
            </button>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.courses.index') }}"
                   class="rounded-xl border border-slate-700 bg-slate-950 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 focus:outline-hidden focus:ring-2 focus:ring-amber-500/50 transition">
                    Update Course
                </button>
            </div>
        </div>
    </form>

    <!-- Hidden Delete Form -->
    <form id="delete-course-form" method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paidRadio = document.getElementById('pricing_paid');
        const freeRadio = document.getElementById('pricing_free');
        const pricingFields = document.getElementById('pricing_fields');

        function updatePricingVisibility() {
            if (freeRadio && freeRadio.checked) {
                pricingFields.classList.add('hidden');
            } else if (pricingFields) {
                pricingFields.classList.remove('hidden');
            }
        }

        if (paidRadio) paidRadio.addEventListener('change', updatePricingVisibility);
        if (freeRadio) freeRadio.addEventListener('change', updatePricingVisibility);

        const thumbnailInput = document.getElementById('thumbnail');
        const previewImg = document.getElementById('image-preview');
        const placeholder = document.getElementById('preview-placeholder');

        if (thumbnailInput) {
            thumbnailInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        previewImg.src = event.target.result;
                        previewImg.classList.remove('hidden');
                        if (placeholder) placeholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
@endsection
