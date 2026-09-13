@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6 max-w-4xl">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-400 ring-1 ring-inset ring-indigo-500/20">
                    Marketing Automation
                </span>
                <span class="text-xs text-slate-500">Edit Rule</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Edit Automation: {{ $automation->name }}
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Update trigger configurations, delay duration, template, and condition filters.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.automations.show', $automation) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                &larr; View Logs
            </a>
            <a href="{{ route('admin.automations.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-200 shadow-sm transition">
                All Automations
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.automations.update', $automation) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-5 shadow-xs">
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Automation Rule Name <span class="text-rose-400">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $automation->name) }}" required
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                @error('name')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Internal Description / Goal
                </label>
                <input type="text" id="description" name="description" value="{{ old('description', $automation->description) }}"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                @error('description')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="trigger_type" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Trigger Event <span class="text-rose-400">*</span>
                    </label>
                    <select id="trigger_type" name="trigger_type" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                        @foreach($triggers as $trg)
                            <option value="{{ $trg->value }}" {{ old('trigger_type', $automation->trigger_type->value) === $trg->value ? 'selected' : '' }}>
                                {{ $trg->label() }} ({{ $trg->recipientType() }})
                            </option>
                        @endforeach
                    </select>
                    @error('trigger_type')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Status <span class="text-rose-400">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ old('status', $automation->status->value) === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="template_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Email Template <span class="text-rose-400">*</span>
                    </label>
                    <select id="template_id" name="template_id" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ old('template_id', $automation->template_id) == $tpl->id ? 'selected' : '' }}>
                                {{ $tpl->name }} ({{ $tpl->subject }})
                            </option>
                        @endforeach
                    </select>
                    @error('template_id')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="delay_minutes" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Execution Delay (Minutes) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" id="delay_minutes" name="delay_minutes" value="{{ old('delay_minutes', $automation->delay_minutes) }}" min="0" max="10080" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-[11px] text-slate-500 mt-1">0 = Immediate delivery.</p>
                    @error('delay_minutes')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="conditions" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Matching Conditions <span class="text-slate-500 font-normal">(Optional JSON format)</span>
                </label>
                <textarea id="conditions" name="conditions" rows="3"
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500">{{ old('conditions', $automation->conditions ? json_encode($automation->conditions, JSON_PRETTY_PRINT) : '') }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1">Specify key-value pairs to restrict trigger execution (e.g. {"status": "qualified"}).</p>
                @error('conditions')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-800/60">
                <a href="{{ route('admin.automations.show', $automation) }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-sm transition">
                    Update Automation Rule
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
