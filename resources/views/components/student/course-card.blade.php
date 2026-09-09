@props([
    'title',
    'description',
    'modules' => 6,
    'duration' => '4 Weeks',
    'level' => 'Beginner to Intermediate',
    'badge' => 'Practical Marketing',
    'actionUrl' => null,
    'actionLabel' => 'View Course',
])

<div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm hover:shadow-md transition">
    <div>
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 border border-indigo-100">
                {{ $badge }}
            </span>
            <span class="text-xs font-medium text-slate-500">
                {{ $level }}
            </span>
        </div>

        <h4 class="mt-4 text-lg font-bold text-slate-900 tracking-tight">
            {{ $title }}
        </h4>

        <p class="mt-2 text-sm text-slate-600 line-clamp-3 leading-relaxed">
            {{ $description }}
        </p>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between text-xs font-medium text-slate-500 mb-4">
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                {{ $modules }} Modules
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $duration }}
            </span>
        </div>

        @if ($actionUrl)
            <a href="{{ $actionUrl }}" class="w-full flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                {{ $actionLabel }} &rarr;
            </a>
        @endif
    </div>
</div>