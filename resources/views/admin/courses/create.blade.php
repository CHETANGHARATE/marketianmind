@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition mb-2">
                &larr; Back to Course List
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Create New Course
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Add a new practical marketing course to the Marketian Mind curriculum.
            </p>
        </div>
    </div>

    <!-- Course Form -->
    <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

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
                       value="{{ old('title') }}"
                       required
                       placeholder="e.g. Digital Marketing for Small Businesses"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Course Slug -->
            <div>
                <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Custom Slug <span class="text-slate-500 lowercase font-normal">(optional, auto-generated from title if blank)</span>
                </label>
                <div class="mt-1.5 flex rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                    <span class="inline-flex items-center px-3 text-xs text-slate-500 bg-slate-900 border-r border-slate-800 select-none">
                        /courses/
                    </span>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug') }}"
                           placeholder="digital-marketing-for-small-businesses"
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
                          placeholder="Brief 1-2 sentence summary displayed on course cards and catalog..."
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('short_description') }}</textarea>
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
                          placeholder="Comprehensive course overview, target audience takeaways, and learning objectives..."
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
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
                            <option value="{{ $category->id }}" {{ old('course_category_id') == $category->id ? 'selected' : '' }}>
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
                        Instructor Name <span class="text-slate-500 lowercase font-normal">(optional)</span>
                    </label>
                    <input type="text"
                           name="instructor_name"
                           id="instructor_name"
                           value="{{ old('instructor_name', 'Marketian Mind Team') }}"
                           placeholder="e.g. Chetan Gharate"
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
                           {{ old('is_free', '0') == '0' ? 'checked' : '' }}
                           class="h-4 w-4 text-amber-500 bg-slate-950 border-slate-700 focus:ring-amber-500">
                    <span class="text-sm font-semibold text-white">Paid Course</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio"
                           name="is_free"
                           id="pricing_free"
                           value="1"
                           {{ old('is_free') == '1' ? 'checked' : '' }}
                           class="h-4 w-4 text-amber-500 bg-slate-950 border-slate-700 focus:ring-amber-500">
                    <span class="text-sm font-semibold text-white">Free Course</span>
                </label>
            </div>

            <!-- Pricing Fields Container -->
            <div id="pricing_fields" class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 {{ old('is_free') == '1' ? 'hidden' : '' }}">
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
                               value="{{ old('price', '1999.00') }}"
                               placeholder="1999.00"
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
                               value="{{ old('discount_price', '999.00') }}"
                               placeholder="999.00"
                               class="w-full bg-transparent pl-8 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-hidden">
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">Must be lower than regular price.</p>
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
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft (Hidden from public)</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published (Live on website)</option>
                        <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived (Deprecated)</option>
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
                           value="{{ old('estimated_duration', '6 hours') }}"
                           placeholder="e.g. 6 hours or 4.5 hours"
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
                           {{ old('featured') ? 'checked' : '' }}
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
                    Upload Image <span class="text-slate-500 lowercase font-normal">(JPG, PNG, WebP up to 3MB)</span>
                </label>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div id="preview-container" class="h-24 w-36 rounded-lg border border-slate-800 bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                        <span id="preview-placeholder" class="text-xs text-slate-600 font-semibold">Preview</span>
                        <img id="image-preview" src="#" alt="Thumbnail Preview" class="h-full w-full object-cover hidden">
                    </div>

                    <div class="flex-1 w-full">
                        <input type="file"
                               name="thumbnail"
                               id="thumbnail"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 file:cursor-pointer cursor-pointer">
                        <p class="mt-1 text-[11px] text-slate-500">Recommended dimension: 1280x720 (16:9 ratio).</p>
                    </div>
                </div>
                @error('thumbnail')
                    <p class="mt-2 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.courses.index') }}"
               class="rounded-xl border border-slate-700 bg-slate-950 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 focus:outline-hidden focus:ring-2 focus:ring-amber-500/50 transition">
                Create Course
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle pricing fields on Free vs Paid
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

        // Thumbnail live preview
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
