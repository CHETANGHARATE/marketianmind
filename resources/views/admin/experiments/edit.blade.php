@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-800 pb-6">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
            <a href="{{ route('admin.experiments.show', $experiment) }}" class="hover:text-amber-400 transition">&larr; Back to Experiment Details</a>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Edit Experiment: {{ $experiment->name }}</h1>
        <p class="mt-1 text-sm text-slate-400">
            Modify experiment metadata, audience, and traffic allocation settings.
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

    <form action="{{ route('admin.experiments.update', $experiment) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Experiment Settings</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Experiment Name *</label>
                    <input type="text" name="name" value="{{ old('name', $experiment->name) }}" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Key (Immutable)</label>
                    <input type="text" value="{{ $experiment->key }}" disabled
                           class="w-full rounded-xl border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs text-slate-400 font-mono cursor-not-allowed">
                    <p class="text-[11px] text-slate-500 mt-1">Keys cannot be altered after creation.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Hypothesis &amp; Description</label>
                <textarea name="description" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">{{ old('description', $experiment->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Metric *</label>
                    <select name="primary_metric" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                        <option value="course_enrolled" {{ old('primary_metric', $experiment->primary_metric) === 'course_enrolled' ? 'selected' : '' }}>course_enrolled</option>
                        <option value="bundle_purchased" {{ old('primary_metric', $experiment->primary_metric) === 'bundle_purchased' ? 'selected' : '' }}>bundle_purchased</option>
                        <option value="checkout_started" {{ old('primary_metric', $experiment->primary_metric) === 'checkout_started' ? 'selected' : '' }}>checkout_started</option>
                        <option value="payment_success" {{ old('primary_metric', $experiment->primary_metric) === 'payment_success' ? 'selected' : '' }}>payment_success</option>
                        <option value="course_cta_click" {{ old('primary_metric', $experiment->primary_metric) === 'course_cta_click' ? 'selected' : '' }}>course_cta_click</option>
                        <option value="bundle_cta_click" {{ old('primary_metric', $experiment->primary_metric) === 'bundle_cta_click' ? 'selected' : '' }}>bundle_cta_click</option>
                        <option value="lead_created" {{ old('primary_metric', $experiment->primary_metric) === 'lead_created' ? 'selected' : '' }}>lead_created</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Traffic Allocation % *</label>
                    <input type="number" name="traffic_percentage" value="{{ old('traffic_percentage', $experiment->traffic_percentage) }}" min="1" max="100" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Audience</label>
                    <input type="text" name="target_audience" value="{{ old('target_audience', $experiment->target_audience) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Variants Display -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Current Variants</h2>
            <div class="space-y-3">
                @foreach($experiment->variants as $variant)
                    <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-xs">{{ $variant->name }}</span>
                                <code class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-amber-400 font-mono">{{ $variant->key }}</code>
                                @if($variant->is_control)
                                    <span class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] text-slate-300 font-semibold">Control</span>
                                @endif
                            </div>
                            @if($variant->config)
                                <pre class="mt-2 text-[10px] text-slate-400 font-mono bg-slate-950/60 rounded p-2 overflow-x-auto">{{ json_encode($variant->config, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-300">Weight: {{ $variant->weight }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.experiments.show', $experiment) }}"
               class="rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-300 transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 hover:bg-amber-400 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
