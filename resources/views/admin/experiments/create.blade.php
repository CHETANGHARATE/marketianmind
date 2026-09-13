@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-800 pb-6">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
            <a href="{{ route('admin.experiments.index') }}" class="hover:text-amber-400 transition">&larr; Back to Experiments</a>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Create A/B Experiment</h1>
        <p class="mt-1 text-sm text-slate-400">
            Configure a randomized, deterministic A/B test. Safe data-driven configurations only (pure JSON properties).
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-xs font-medium text-rose-400">
            <div class="font-semibold mb-1">Please correct the following errors:</div>
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.experiments.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Core Experiment Details -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">1. Experiment Specification</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Experiment Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Course Detail CTA Urgency"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">Human-friendly name for reporting</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unique Key / Identifier *</label>
                    <input type="text" name="key" value="{{ old('key') }}" required placeholder="e.g. course_cta"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white font-mono focus:border-amber-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">Referenced in code via <code>ExperimentService::resolveVariant('key')</code></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Hypothesis &amp; Description</label>
                <textarea name="description" rows="2" placeholder="Describe the hypothesis being tested..."
                          class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Metric *</label>
                    <select name="primary_metric" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                        <option value="course_enrolled" {{ old('primary_metric') === 'course_enrolled' ? 'selected' : '' }}>course_enrolled</option>
                        <option value="bundle_purchased" {{ old('primary_metric') === 'bundle_purchased' ? 'selected' : '' }}>bundle_purchased</option>
                        <option value="checkout_started" {{ old('primary_metric') === 'checkout_started' ? 'selected' : '' }}>checkout_started</option>
                        <option value="payment_success" {{ old('primary_metric') === 'payment_success' ? 'selected' : '' }}>payment_success</option>
                        <option value="course_cta_click" {{ old('primary_metric') === 'course_cta_click' ? 'selected' : '' }}>course_cta_click</option>
                        <option value="bundle_cta_click" {{ old('primary_metric') === 'bundle_cta_click' ? 'selected' : '' }}>bundle_cta_click</option>
                        <option value="lead_created" {{ old('primary_metric') === 'lead_created' ? 'selected' : '' }}>lead_created</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Traffic Allocation % *</label>
                    <input type="number" name="traffic_percentage" value="{{ old('traffic_percentage', 100) }}" min="1" max="100" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">% of visitors included in experiment</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Audience</label>
                    <input type="text" name="target_audience" value="{{ old('target_audience', 'all') }}" placeholder="all, guests, students"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Variants Section -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">2. Experiment Variants</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">At least 2 variants are required. Exactly one should be marked as Control.</p>
                </div>
            </div>

            <div class="space-y-4" id="variants-container">
                <!-- Variant 1 (Control) -->
                <div class="rounded-xl border border-slate-700/60 bg-slate-800/40 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700/50 pb-2">
                        <span class="text-xs font-bold text-white flex items-center gap-2">
                            <span class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] text-slate-300">Variant 1</span>
                            Baseline / Control
                        </span>
                        <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" name="variants[0][is_control]" value="1" checked class="rounded border-slate-700 text-amber-500 focus:ring-amber-500">
                            <span>Is Control</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Key *</label>
                            <input type="text" name="variants[0][key]" value="{{ old('variants.0.key', 'control') }}" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white font-mono focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Name *</label>
                            <input type="text" name="variants[0][name]" value="{{ old('variants.0.name', 'Original') }}" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Weight % *</label>
                            <input type="number" name="variants[0][weight]" value="{{ old('variants.0.weight', 50) }}" min="1" max="100" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Configuration (JSON)</label>
                        <textarea name="variants[0][config]" rows="2" placeholder='{"cta_text": "Enroll Now", "badge": "Popular"}'
                                  class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white font-mono focus:border-amber-500 focus:outline-none">{{ old('variants.0.config', '{"cta_text": "Buy Now →", "badge": ""}') }}</textarea>
                    </div>
                </div>

                <!-- Variant 2 -->
                <div class="rounded-xl border border-slate-700/60 bg-slate-800/40 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700/50 pb-2">
                        <span class="text-xs font-bold text-white flex items-center gap-2">
                            <span class="rounded bg-indigo-900/60 text-indigo-300 px-1.5 py-0.5 text-[10px]">Variant 2</span>
                            Treatment Variant B
                        </span>
                        <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" name="variants[1][is_control]" value="1" class="rounded border-slate-700 text-amber-500 focus:ring-amber-500">
                            <span>Is Control</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Key *</label>
                            <input type="text" name="variants[1][key]" value="{{ old('variants.1.key', 'urgency_cta') }}" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white font-mono focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Name *</label>
                            <input type="text" name="variants[1][name]" value="{{ old('variants.1.name', 'Urgency CTA') }}" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Weight % *</label>
                            <input type="number" name="variants[1][weight]" value="{{ old('variants.1.weight', 50) }}" min="1" max="100" required
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Configuration (JSON)</label>
                        <textarea name="variants[1][config]" rows="2" placeholder='{"cta_text": "Claim Your Seat Now →", "badge": "Limited Offer"}'
                                  class="w-full rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs text-white font-mono focus:border-amber-500 focus:outline-none">{{ old('variants.1.config', '{"cta_text": "Start Learning Today →", "badge": "Fast-Track"}') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.experiments.index') }}"
               class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-300 transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 hover:bg-amber-400 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition">
                Create Experiment (Draft)
            </button>
        </div>
    </form>
</div>
@endsection
