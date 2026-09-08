@props([
    'title',
    'description',
    'badge' => 'Coming Soon',
    'modules' => '6 Modules',
    'level' => 'Beginner to Intermediate',
    'audience' => 'Business Owners & Founders',
    'url' => route('course.details'),
    'featured' => false,
])

<div class="rounded-2xl border {{ $featured ? 'border-indigo-200 bg-gradient-to-b from-indigo-50/40 via-white to-white ring-1 ring-indigo-500/10' : 'border-slate-200 bg-white' }} p-6 sm:p-8 flex flex-col justify-between shadow-sm hover:shadow-md transition">
    <div>
        <div class="flex items-center justify-between gap-3 mb-5">
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                {{ $badge }}
            </span>
            <span class="text-xs text-slate-500 font-medium">
                {{ $modules }}
            </span>
        </div>

        <h3 class="text-xl font-bold tracking-tight text-slate-900 mb-3">
            {{ $title }}
        </h3>

        <p class="text-sm text-slate-600 leading-relaxed mb-6">
            {{ $description }}
        </p>

        <div class="border-t border-slate-100 pt-4 mb-6 space-y-2 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span>Target: <strong class="text-slate-700">{{ $audience }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>Practical, real-world execution without theory fluff</span>
            </div>
        </div>
    </div>

    <div class="pt-2">
        <a href="{{ $url }}" class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition">
            Explore Course &rarr;
        </a>
    </div>
</div>
