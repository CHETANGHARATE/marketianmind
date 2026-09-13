@extends('layouts.student')

@section('subcontent')
<div class="space-y-8">
    <!-- 1. Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 sm:p-10 text-white shadow-sm border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/20 px-3.5 py-1 text-xs font-semibold text-amber-300 border border-amber-500/30 mb-4">
                <span>🔥</span>
                <span>Consistency &bull; Learning Milestones</span>
            </div>

            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                Achievements &amp; Streaks
            </h1>

            <p class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed">
                Celebrate your daily marketing progress. Build consistent study habits, accumulate learning points, and unlock certified milestone badges.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                    &larr; Back to Dashboard
                </a>
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    My Courses
                </a>
            </div>
        </div>

        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- 2. Gamification Metric Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Current Streak -->
        <div class="rounded-2xl border border-rose-200/80 bg-white p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Current Streak</span>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 border border-rose-100 text-lg">
                    🔥
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $streaks['current'] }}</span>
                <span class="text-xs font-semibold text-slate-500">{{ \Illuminate\Support\Str::plural('Day', $streaks['current']) }}</span>
            </div>
            <p class="mt-2 text-xs font-medium text-slate-500">
                @if($streaks['current'] > 0)
                    Streak active! Learn today to keep it burning.
                @else
                    Complete a lesson today to start a streak!
                @endif
            </p>
        </div>

        <!-- 2. Longest Streak -->
        <div class="rounded-2xl border border-amber-200/80 bg-white p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Longest Streak</span>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 text-lg">
                    ⚡
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $streaks['longest'] }}</span>
                <span class="text-xs font-semibold text-slate-500">{{ \Illuminate\Support\Str::plural('Day', $streaks['longest']) }}</span>
            </div>
            <p class="mt-2 text-xs font-medium text-slate-500">
                Personal best continuous study record
            </p>
        </div>

        <!-- 3. Total Learning Points -->
        <div class="rounded-2xl border border-indigo-200/80 bg-white p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Learning Points</span>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 text-lg">
                    ⭐
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($pointsBalance) }}</span>
                <span class="text-xs font-semibold text-slate-500">Points</span>
            </div>
            <p class="mt-2 text-xs font-medium text-emerald-600 flex items-center gap-1">
                <span>+{{ number_format($pointsToday) }} earned today</span>
            </p>
        </div>

        <!-- 4. Badges Unlocked -->
        <div class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Milestone Badges</span>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 text-lg">
                    🏆
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $earnedCount }}</span>
                <span class="text-xs font-semibold text-slate-400">/ {{ $totalCount }} unlocked</span>
            </div>
            <p class="mt-2 text-xs font-medium text-slate-500">
                {{ $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0 }}% completion
            </p>
        </div>
    </div>

    <!-- 3. Milestone Achievement Badges Showcase -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Milestone Badges
                </h2>
                <p class="text-xs text-slate-500">
                    Badges unlocked as you complete lessons, finish courses, and maintain study streaks
                </p>
            </div>
            <span class="text-xs font-bold text-indigo-600">
                {{ $earnedCount }} of {{ $totalCount }} Earned
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($achievements as $ach)
                <div class="rounded-2xl border {{ $ach['is_earned'] ? 'border-amber-300/80 bg-gradient-to-br from-amber-50/30 via-white to-white shadow-xs' : 'border-slate-200/80 bg-white/70 shadow-2xs' }} p-5 flex flex-col justify-between space-y-4 transition">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $ach['is_earned'] ? 'bg-amber-100 text-amber-700 ring-4 ring-amber-50' : 'bg-slate-100 text-slate-400' }} text-xl shrink-0">
                                @if($ach['icon'] === 'rocket') 🚀
                                @elseif($ach['icon'] === 'bolt') ⚡
                                @elseif($ach['icon'] === 'academic-cap') 🎓
                                @elseif($ach['icon'] === 'trophy') 🏆
                                @elseif($ach['icon'] === 'star') ⭐
                                @elseif($ach['icon'] === 'fire') 🔥
                                @elseif($ach['icon'] === 'sparkles') ✨
                                @else 🏅
                                @endif
                            </div>

                            <div class="text-right">
                                @if($ach['is_earned'])
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 border border-emerald-100">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Unlocked
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Locked
                                    </span>
                                @endif
                                <span class="block text-[11px] font-bold text-amber-600 mt-1">+{{ $ach['points'] }} pts</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h3 class="font-bold text-slate-900 text-sm">
                                {{ $ach['name'] }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                {{ $ach['description'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Progress towards unlock -->
                    <div class="space-y-1.5 pt-3 border-t border-slate-100">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500">
                            <span>Progress</span>
                            <span>{{ $ach['current_progress'] }} / {{ $ach['requirement_value'] }} ({{ $ach['progress_percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden" role="progressbar" aria-valuenow="{{ $ach['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $ach['name'] }} badge progress">
                            <div class="{{ $ach['is_earned'] ? 'bg-emerald-500' : 'bg-indigo-600' }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $ach['progress_percentage'] }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 4. Points Ledger & History Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Recent Points Ledger
                </h2>
                <p class="text-xs text-slate-500">
                    Immutable history of points awarded for completed lessons and courses
                </p>
            </div>
            <span class="text-xs font-semibold text-slate-500">
                Points are server-controlled &amp; motivational
            </span>
        </div>

        <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
            @if($recentPoints->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5">Activity Description</th>
                                <th class="px-5 py-3.5">Category</th>
                                <th class="px-5 py-3.5">Points Awarded</th>
                                <th class="px-5 py-3.5 text-right">Earned Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentPoints as $item)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900">
                                        {{ $item->description }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                            {{ str_replace('_', ' ', $item->event_type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 font-bold text-emerald-600">
                                        +{{ $item->points }} pts
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-slate-400">
                                        {{ $item->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center">
                    <p class="text-xs font-semibold text-slate-600">No point transactions yet.</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Complete lessons and courses in your curriculum tracks to earn your first learning points.</p>
                    <div class="mt-3">
                        <a href="{{ route('student.courses') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                            Start Learning &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
