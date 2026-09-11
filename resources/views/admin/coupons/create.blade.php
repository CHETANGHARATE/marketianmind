@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-800 pb-6">
        <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition mb-3">
            &larr; Back to Coupons List
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
            Create Promotional Coupon
        </h1>
        <p class="mt-1 text-sm text-slate-400">
            Define promotional codes, discount parameters, redemption limitations, and course applicability.
        </p>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-xs text-rose-400">
            <p class="font-bold mb-1">Please fix the following validation errors:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">
                1. Basic Information
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Coupon Code -->
                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Coupon Code <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="code"
                           id="code"
                           value="{{ old('code') }}"
                           required
                           placeholder="e.g. FOUNDER50"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Case-insensitive promo code entered by student during checkout.</p>
                </div>

                <!-- Friendly Name -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Coupon Name / Label <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="e.g. Early Founder Launch Special"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Internal description and campaign name.</p>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Description <span class="text-slate-500">(Optional)</span>
                </label>
                <textarea name="description"
                          id="description"
                          rows="2"
                          placeholder="Brief notes about target audience or campaign purpose..."
                          class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- Discount Rules Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">
                2. Discount Parameters
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Discount Type -->
                <div>
                    <label for="discount_type" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Discount Type <span class="text-amber-400">*</span>
                    </label>
                    <select name="discount_type"
                            id="discount_type"
                            required
                            class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage Discount (%)</option>
                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount Discount (₹)</option>
                    </select>
                </div>

                <!-- Discount Value -->
                <div>
                    <label for="discount_value" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Discount Value <span class="text-amber-400">*</span>
                    </label>
                    <input type="number"
                           step="any"
                           min="1"
                           name="discount_value"
                           id="discount_value"
                           value="{{ old('discount_value') }}"
                           required
                           placeholder="e.g. 20 (for 20%) or 500 (for ₹500)"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs font-semibold text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">If Percentage: enter number 1-100. If Fixed: enter amount in INR Rupees.</p>
                </div>

                <!-- Minimum Order Amount -->
                <div>
                    <label for="min_order_amount" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Minimum Order Amount (₹) <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="number"
                           step="any"
                           min="0"
                           name="min_order_amount"
                           id="min_order_amount"
                           value="{{ old('min_order_amount') }}"
                           placeholder="e.g. 999"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Leave blank if no minimum order value required.</p>
                </div>

                <!-- Maximum Discount Amount (for Percentage) -->
                <div>
                    <label for="max_discount_amount" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Maximum Discount Cap (₹) <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="number"
                           step="any"
                           min="0"
                           name="max_discount_amount"
                           id="max_discount_amount"
                           value="{{ old('max_discount_amount') }}"
                           placeholder="e.g. 1500"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Cap the total rupee discount for percentage coupons.</p>
                </div>
            </div>
        </div>

        <!-- Limits & Scope Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">
                3. Redemption Limits & Course Scope
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Global Usage Limit -->
                <div>
                    <label for="usage_limit" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Total Platform Usage Limit <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="number"
                           min="1"
                           name="usage_limit"
                           id="usage_limit"
                           value="{{ old('usage_limit') }}"
                           placeholder="e.g. 100"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Total redemptions across all users. Leave blank for unlimited.</p>
                </div>

                <!-- Per-User Limit -->
                <div>
                    <label for="per_user_limit" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Per-User Usage Limit <span class="text-amber-400">*</span>
                    </label>
                    <input type="number"
                           min="1"
                           name="per_user_limit"
                           id="per_user_limit"
                           value="{{ old('per_user_limit', 1) }}"
                           required
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="mt-1 text-[11px] text-slate-500">Maximum redemptions allowed per student (default 1).</p>
                </div>

                <!-- Start Date -->
                <div>
                    <label for="starts_at" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Starts At <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="datetime-local"
                           name="starts_at"
                           id="starts_at"
                           value="{{ old('starts_at') }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <!-- Expiry Date -->
                <div>
                    <label for="expires_at" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Expires At <span class="text-slate-500">(Optional)</span>
                    </label>
                    <input type="datetime-local"
                           name="expires_at"
                           id="expires_at"
                           value="{{ old('expires_at') }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <!-- Course Restriction -->
                <div class="sm:col-span-2">
                    <label for="course_id" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Applicable Course <span class="text-slate-500">(Optional)</span>
                    </label>
                    <select name="course_id"
                            id="course_id"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="">All Courses (Sitewide Promotion)</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Active Status Toggle -->
            <div class="pt-4 border-t border-slate-800">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500 focus:ring-offset-slate-900">
                    <span class="text-xs font-semibold text-slate-300">Set coupon as Active immediately</span>
                </label>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.coupons.index') }}"
               class="rounded-xl bg-slate-800 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 transition cursor-pointer">
                Save & Publish Coupon
            </button>
        </div>
    </form>
</div>
@endsection