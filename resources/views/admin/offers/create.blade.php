@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.offers.index') }}" class="text-xs text-amber-400 hover:underline">
                    &larr; Back to Offers
                </a>
                <span class="text-xs text-slate-500">/</span>
                <span class="text-xs text-slate-400">New Promotion</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">
                Create Promotional Offer
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Configure automated pricing discounts for individual courses or course bundles with scheduling and priority controls.
            </p>
        </div>
    </div>

    <!-- Error Summary -->
    @if($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-xs font-medium text-rose-400 space-y-1">
            <div class="font-bold flex items-center gap-2">
                <svg class="h-4 w-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Please correct the following errors:
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('admin.offers.store') }}" class="space-y-6">
        @csrf

        <!-- Basic Information Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                Offer Identity &amp; Discount
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-semibold text-slate-300">
                        Offer Name <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="e.g., Diwali Founder Sprint Deal"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="slug" class="block text-xs font-semibold text-slate-300">
                        URL Slug <span class="text-slate-500">(Optional, auto-generated)</span>
                    </label>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug') }}"
                           placeholder="e.g., diwali-founder-sprint-deal"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="badge_text" class="block text-xs font-semibold text-slate-300">
                        Badge Text <span class="text-slate-500">(Optional display tag)</span>
                    </label>
                    <input type="text"
                           name="badge_text"
                           id="badge_text"
                           value="{{ old('badge_text') }}"
                           placeholder="e.g., Limited Time Deal, Diwali Special"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="discount_type" class="block text-xs font-semibold text-slate-300">
                        Discount Type <span class="text-amber-400">*</span>
                    </label>
                    <select name="discount_type"
                            id="discount_type"
                            required
                            class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage (%) Off</option>
                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹) Off</option>
                    </select>
                </div>

                <div>
                    <label for="discount_value" class="block text-xs font-semibold text-slate-300">
                        Discount Value <span class="text-amber-400">*</span>
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0.01"
                           name="discount_value"
                           id="discount_value"
                           value="{{ old('discount_value') }}"
                           required
                           placeholder="e.g., 20 for 20%, or 500 for ₹500"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-300">
                        Description / Campaign Notes <span class="text-slate-500">(Optional)</span>
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Internal or customer notes about this pricing offer..."
                              class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 p-3.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Scheduling, Priority & Controls Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                Schedule &amp; Priority Rules
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="starts_at" class="block text-xs font-semibold text-slate-300">
                        Start Date &amp; Time <span class="text-slate-500">(Optional, immediate if empty)</span>
                    </label>
                    <input type="datetime-local"
                           name="starts_at"
                           id="starts_at"
                           value="{{ old('starts_at') }}"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="ends_at" class="block text-xs font-semibold text-slate-300">
                        End Date &amp; Time <span class="text-slate-500">(Optional, no expiry if empty)</span>
                    </label>
                    <input type="datetime-local"
                           name="ends_at"
                           id="ends_at"
                           value="{{ old('ends_at') }}"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="priority" class="block text-xs font-semibold text-slate-300">
                        Priority Level <span class="text-slate-500">(Higher number = Higher precedence)</span>
                    </label>
                    <input type="number"
                           name="priority"
                           id="priority"
                           value="{{ old('priority', 10) }}"
                           min="0"
                           max="9999"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">
                        When multiple offers match the same product, the offer with the highest priority is selected automatically.
                    </p>
                </div>

                <div>
                    <label for="usage_limit" class="block text-xs font-semibold text-slate-300">
                        Total Usage / Redemption Cap <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="number"
                           name="usage_limit"
                           id="usage_limit"
                           value="{{ old('usage_limit') }}"
                           min="1"
                           placeholder="e.g., 100 successful purchases"
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <!-- Toggles -->
            <div class="pt-4 border-t border-slate-800/80 space-y-3">
                <label class="relative flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400 mt-0.5">
                    <div>
                        <span class="text-xs font-bold text-white">Offer Status: Active</span>
                        <p class="text-[11px] text-slate-400">When enabled and within schedule, this offer will automatically reduce product checkout prices.</p>
                    </div>
                </label>

                <label class="relative flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="allow_coupons" value="0">
                    <input type="checkbox"
                           name="allow_coupons"
                           value="1"
                           {{ old('allow_coupons', '0') == '1' ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400 mt-0.5">
                    <div>
                        <span class="text-xs font-bold text-white">Allow Coupon Stacking</span>
                        <p class="text-[11px] text-slate-400">If checked, students can enter a coupon code on top of this offer price. If unchecked (default), coupon entry will be blocked on this offer.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Eligible Products Target Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xs space-y-5">
            <div>
                <h2 class="text-base font-bold text-white">
                    Target Products <span class="text-amber-400">*</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Select the courses and/or bundles eligible for this promotional discount.
                </p>
            </div>

            <!-- Courses Section -->
            @if($courses->count() > 0)
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Individual Courses ({{ $courses->count() }})</span>
                        <button type="button" onclick="document.querySelectorAll('.course-checkbox').forEach(c => c.checked = true)" class="text-[11px] text-amber-400 hover:underline">Select All</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-60 overflow-y-auto pr-1">
                        @foreach($courses as $course)
                            <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-800 bg-slate-950/40 hover:border-slate-700 transition cursor-pointer">
                                <input type="checkbox"
                                       name="courses[]"
                                       value="{{ $course->id }}"
                                       class="course-checkbox h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400 mt-0.5"
                                       {{ in_array($course->id, old('courses', [])) ? 'checked' : '' }}>
                                <div class="text-xs">
                                    <span class="font-semibold text-white block">{{ $course->title }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $course->formattedPrice() }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Bundles Section -->
            @if($bundles->count() > 0)
                <div class="space-y-3 pt-4 border-t border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Course Bundles &amp; Packages ({{ $bundles->count() }})</span>
                        <button type="button" onclick="document.querySelectorAll('.bundle-checkbox').forEach(c => c.checked = true)" class="text-[11px] text-amber-400 hover:underline">Select All</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-60 overflow-y-auto pr-1">
                        @foreach($bundles as $bundle)
                            <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-800 bg-slate-950/40 hover:border-slate-700 transition cursor-pointer">
                                <input type="checkbox"
                                       name="bundles[]"
                                       value="{{ $bundle->id }}"
                                       class="bundle-checkbox h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400 mt-0.5"
                                       {{ in_array($bundle->id, old('bundles', [])) ? 'checked' : '' }}>
                                <div class="text-xs">
                                    <span class="font-semibold text-white block">{{ $bundle->title }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $bundle->formattedPrice() }} ({{ $bundle->publishedCourses->count() }} courses)</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.offers.index') }}"
               class="rounded-xl border border-slate-700 bg-slate-900 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition cursor-pointer">
                Save Promotional Offer
            </button>
        </div>
    </form>
</div>
@endsection
