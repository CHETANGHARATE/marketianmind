@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'color' => 'indigo'
])

@php
    $colorClasses = match($color) {
        'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'amber' => 'bg-amber-50 text-amber-600 border-amber-100',
        'sky' => 'bg-sky-50 text-sky-600 border-sky-100',
        default => 'bg-indigo-50 text-indigo-600 border-indigo-100',
    };
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">
            {{ $title }}
        </span>
        @if ($icon)
            <div class="flex h-11 w-11 items-center justify-center rounded-xl border {{ $colorClasses }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-4 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">
            {{ $value }}
        </span>
    </div>

    @if ($description)
        <p class="mt-2 text-xs font-medium text-slate-500">
            {{ $description }}
        </p>
    @endif
</div>