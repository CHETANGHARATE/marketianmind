@props([
    'title' => 'No items found',
    'description' => 'There is currently no information available to display.',
    'actionUrl' => null,
    'actionLabel' => null,
])

<div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-8 ring-indigo-50/50">
        {{ $slot->isNotEmpty() ? $slot : '' }}
        @if ($slot->isEmpty())
            <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        @endif
    </div>

    <h3 class="mt-4 text-base font-bold text-slate-900">
        {{ $title }}
    </h3>

    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
        {{ $description }}
    </p>

    @if ($actionUrl && $actionLabel)
        <div class="mt-6">
            <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition">
                {{ $actionLabel }} &rarr;
            </a>
        </div>
    @endif
</div>