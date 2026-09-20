@extends('layouts.student')

@section('subcontent')
<div class="space-y-8">
    <!-- 1. Welcome Header Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 sm:p-10 text-white shadow-sm border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-500/20 px-3.5 py-1 text-xs font-semibold text-indigo-300 border border-indigo-500/30 mb-4">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                Student Portal &bull; Marketian Mind
            </div>

            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                Welcome back, {{ $user->name ?: 'Student' }}!
            </h1>

            <p class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed">
                Continue your learning journey and keep building practical marketing skills to grow your business online without depending on expensive agencies.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if($continueLearningCourse)
                    <a href="{{ $continueLearningCourse['actionUrl'] }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                        {{ $continueLearningCourse['actionLabel'] }} &rarr;
                    </a>
                @else
                    <a href="{{ route('courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                        Explore Courses &rarr;
                    </a>
                @endif
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    My Courses
                </a>
                <a href="{{ route('student.wishlist.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    Saved Courses
                </a>
                <a href="{{ route('student.orders.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    Purchase History
                </a>
                <a href="{{ route('student.profile') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    Account Settings
                </a>
            </div>
        </div>

        <!-- Decorative background glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- 1.5. Gamification Quick Snapshot -->
    @if(isset($gamification))
        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-6">
                <!-- Left: Streaks & Points -->
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <!-- Streak Indicator -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 border border-amber-200 text-xl shadow-xs shrink-0">
                            🔥
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm sm:text-base font-black text-slate-900">
                                    {{ $gamification['streaks']['current_streak'] }} Day {{ \Illuminate\Support\Str::plural('Streak', $gamification['streaks']['current_streak']) }}
                                </span>
                                @if($gamification['streaks']['has_learned_today'])
                                    <span class="rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5">Active</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Best: {{ $gamification['streaks']['longest_streak'] }} {{ \Illuminate\Support\Str::plural('day', $gamification['streaks']['longest_streak']) }}
                            </p>
                        </div>
                    </div>

                    <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

                    <!-- Points Badge -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 border border-indigo-200 text-xl shadow-xs shrink-0">
                            ⭐
                        </div>
                        <div>
                            <span class="text-sm sm:text-base font-black text-slate-900">
                                {{ number_format($gamification['points_balance'] ?? ($gamification['points']['total'] ?? 0)) }} Points
                            </span>
                            <p class="text-[11px] text-slate-500">
                                +10/lesson &bull; +100/course
                            </p>
                        </div>
                    </div>

                    <div class="hidden md:block h-8 w-px bg-slate-200"></div>

                    <!-- Recent Milestone Badges -->
                    <div class="flex items-center gap-2">
                        @php
                            $earnedBadges = collect($gamification['earned_achievements'] ?? [])->take(3);
                        @endphp
                        @if($earnedBadges->count() > 0)
                            <div class="flex -space-x-2 overflow-hidden">
                                @foreach($earnedBadges as $badge)
                                    <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 border-2 border-white shadow-xs text-sm" title="{{ $badge['name'] ?? 'Badge' }}">
                                        {{ $badge['icon'] ?? '🏅' }}
                                    </div>
                                @endforeach
                            </div>
                            <div class="text-xs text-slate-600 pl-1">
                                <span class="font-bold text-slate-900">{{ $gamification['earned_count'] ?? 0 }}</span>/{{ $gamification['total_count'] ?? 0 }} Badges
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="text-base">🎯</span>
                                <span>Complete 1 lesson to unlock your first badge</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right: Link to Achievements Hub -->
                <div class="flex items-center self-start lg:self-center shrink-0">
                    <a href="{{ route('student.achievements.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100/80 px-3.5 py-2 rounded-xl border border-indigo-200/80 transition">
                        <span>Achievements Hub</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. Statistics Overview Grid (4 Cards) -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <h2 class="text-base font-bold text-slate-900">
                Learning Statistics
            </h2>

            @if($stats['enrolled_courses'] > 0)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Access:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 border border-emerald-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ $stats['active_access_count'] }} Active
                    </span>
                    @if($stats['expiring_soon_count'] > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 border border-amber-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ $stats['expiring_soon_count'] }} Expiring Soon
                        </span>
                    @endif
                    @if($stats['expired_access_count'] > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            {{ $stats['expired_access_count'] }} Expired
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Enrolled Courses -->
            <x-student.stat-card
                title="Enrolled Courses"
                :value="$stats['enrolled_courses']"
                description="Active courses in your library"
                color="indigo"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 2. In Progress -->
            <x-student.stat-card
                title="In Progress"
                :value="$stats['in_progress']"
                description="Courses actively advancing"
                color="sky"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 3. Completed -->
            <x-student.stat-card
                title="Completed"
                :value="$stats['completed']"
                description="Finished curriculum tracks"
                color="emerald"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 4. Certificates -->
            <x-student.stat-card
                title="Certificates"
                :value="$stats['certificates']"
                description="Earned course credentials"
                color="amber"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>
        </div>

        <!-- Overall Progress Strip (Step 10) -->
        <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Overall Progress</h3>
                    <p class="text-xs text-slate-500">{{ $stats['lessons_completed'] }} of {{ $stats['total_lessons'] }} total curriculum lessons completed</p>
                </div>
            </div>
            <div class="flex items-center gap-4 w-full sm:w-72">
                <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden" role="progressbar" aria-valuenow="{{ $stats['overall_progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Overall curriculum progress">
                    <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $stats['overall_progress'] }}%"></div>
                </div>
                <span class="text-sm font-extrabold text-slate-900 shrink-0">{{ $stats['overall_progress'] }}%</span>
            </div>
        </div>
    </div>

    <!-- 2.5 New Student Quick Start Onboarding Guide -->
    @if($stats['enrolled_courses'] > 0 && ($stats['lessons_completed'] ?? 0) == 0)
        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 via-white to-indigo-50/50 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-700 uppercase tracking-wider">
                        <span class="flex h-2 w-2 rounded-full bg-indigo-600"></span> Quick Start Guide
                    </span>
                    <h3 class="text-base font-bold text-slate-900">Welcome to your practical learning journey</h3>
                    <p class="text-xs text-slate-600 max-w-xl">
                        Follow these 3 simple steps to start applying practical marketing strategies to your business. If you have any questions along the way, <a href="{{ route('contact') }}" class="font-bold text-indigo-600 hover:text-indigo-800 underline">reach out to support</a> anytime.
                    </p>
                </div>
                @if($continueLearningCourse)
                    <a href="{{ $continueLearningCourse['actionUrl'] }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition shrink-0">
                        Start Your First Lesson &rarr;
                    </a>
                @endif
            </div>
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-indigo-100/80 text-xs">
                <div class="flex items-start gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-[11px]">1</span>
                    <div>
                        <span class="font-bold text-slate-900 block">Select Course</span>
                        <span class="text-slate-500 text-[11px]">Open your enrolled curriculum from your dashboard below.</span>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-[11px]">2</span>
                    <div>
                        <span class="font-bold text-slate-900 block">Watch &amp; Execute</span>
                        <span class="text-slate-500 text-[11px]">Actionable, bite-sized lessons with practical business templates.</span>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-[11px]">3</span>
                    <div>
                        <span class="font-bold text-slate-900 block">Earn Certificate</span>
                        <span class="text-slate-500 text-[11px]">Complete all lessons and quizzes to earn your verified credential.</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. Continue Learning (Top Priority Section) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Continue Learning
                </h2>
                <p class="text-xs text-slate-500">
                    Pick up where you left off in your active course
                </p>
            </div>
            @if($stats['enrolled_courses'] > 0)
                <a href="{{ route('student.courses') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    View all enrolled courses &rarr;
                </a>
            @endif
        </div>

        @if ($continueLearningCourse)
            <!-- Featured Primary Continue Learning Hero Card -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm hover:border-indigo-200 transition">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        @if($continueLearningCourse['thumbnail'])
                            <img src="{{ $continueLearningCourse['thumbnail'] }}" alt="{{ $continueLearningCourse['title'] }}" class="h-24 w-36 rounded-xl object-cover border border-slate-100 bg-slate-100 shrink-0">
                        @else
                            <div class="h-24 w-36 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-xs shrink-0">
                                MM
                            </div>
                        @endif

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($continueLearningCourse['category'])
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 border border-indigo-100">
                                        {{ $continueLearningCourse['category'] }}
                                    </span>
                                @endif
                                <x-student.access-badge :state="$continueLearningCourse['access_state']" size="sm" />
                                <span class="text-xs text-slate-400">
                                    {{ $continueLearningCourse['modules_count'] }} {{ \Illuminate\Support\Str::plural('Module', $continueLearningCourse['modules_count']) }} &bull; {{ $continueLearningCourse['duration'] }}
                                </span>
                            </div>

                            <h3 class="text-lg sm:text-xl font-bold text-slate-900 leading-snug">
                                {{ $continueLearningCourse['title'] }}
                            </h3>

                            <!-- Access Validity & Expiry Countdown -->
                            <div class="text-xs text-slate-500 flex items-center gap-1.5 flex-wrap">
                                @if($continueLearningCourse['access_state'] === 'lifetime')
                                    <span class="text-indigo-600 font-semibold">Lifetime Access &bull; Curriculum unlocked</span>
                                @elseif($continueLearningCourse['access_state'] === 'expired')
                                    <span class="text-rose-600 font-semibold">Access expired{{ $continueLearningCourse['formatted_expiry'] ? ' on ' . $continueLearningCourse['formatted_expiry'] : '' }} &bull; Progress preserved</span>
                                @else
                                    <span>Access until <strong>{{ $continueLearningCourse['formatted_expiry'] }}</strong> &bull; <strong class="{{ $continueLearningCourse['access_state'] === 'expiring' ? 'text-amber-600' : 'text-slate-700' }}">{{ $continueLearningCourse['remaining_days_text'] }}</strong></span>
                                @endif
                            </div>

                            @if($continueLearningCourse['next_lesson'])
                                <p class="text-xs font-medium text-indigo-600 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Next Lesson: {{ $continueLearningCourse['next_lesson']->title }}</span>
                                </p>
                            @else
                                <p class="text-xs text-slate-500">
                                    Course completed &bull; Review lessons at any time.
                                </p>
                            @endif

                            <!-- Accessible Progress Bar -->
                            <div class="pt-1 max-w-md">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $continueLearningCourse['progress']['percentage'] }}% ({{ $continueLearningCourse['progress']['completed'] }}/{{ $continueLearningCourse['progress']['total'] }} lessons)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden" role="progressbar" aria-valuenow="{{ $continueLearningCourse['progress']['percentage'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Course completion progress">
                                    <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $continueLearningCourse['progress']['percentage'] }}%"></div>
                                </div>
                                @if($continueLearningCourse['access_state'] === 'expired')
                                    <p class="text-[11px] text-slate-500 mt-2 flex items-center gap-1.5 font-medium">
                                        <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Your learning progress ({{ $continueLearningCourse['progress']['percentage'] }}%) and certificate records are permanently preserved.</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2.5 shrink-0">
                        @if($continueLearningCourse['is_completed'] && !empty($continueLearningCourse['certificate']))
                            <a href="{{ route('student.certificates.show', $continueLearningCourse['certificate']) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-xs hover:bg-emerald-500 transition w-full sm:w-auto">
                                View Certificate &rarr;
                            </a>
                        @endif

                        @if($continueLearningCourse['access_state'] === 'expired')
                            <form action="{{ route('student.courses.purchase', $continueLearningCourse['model']) }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-rose-600 px-6 py-3 text-xs font-bold text-white shadow-xs hover:bg-rose-500 transition cursor-pointer">
                                    {{ $continueLearningCourse['renewal_label'] }} &rarr;
                                </button>
                            </form>
                        @else
                            @if($continueLearningCourse['can_renew'])
                                <form action="{{ route('student.courses.purchase', $continueLearningCourse['model']) }}" method="POST" class="w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-3 text-xs font-bold text-white shadow-xs hover:bg-amber-500 transition cursor-pointer">
                                        {{ $continueLearningCourse['renewal_label'] }} &rarr;
                                    </button>
                                </form>
                            @endif
                            <a href="{{ $continueLearningCourse['actionUrl'] }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition w-full sm:w-auto">
                                {{ $continueLearningCourse['actionLabel'] }} &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Additional In-Progress Courses (if more than 1 active) -->
            @if(count($inProgressCourses) > 1)
                <div class="pt-2">
                    <p class="text-xs font-bold text-slate-700 mb-3">Other Active Courses</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach(array_slice($inProgressCourses, 1) as $activeCourse)
                            <div class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-3">
                                <div class="flex items-start gap-3">
                                    @if($activeCourse['thumbnail'])
                                        <img src="{{ $activeCourse['thumbnail'] }}" alt="{{ $activeCourse['title'] }}" class="h-12 w-16 rounded-lg object-cover border border-slate-100 shrink-0">
                                    @else
                                        <div class="h-12 w-16 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                            MM
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            @if($activeCourse['category'])
                                                <span class="text-[10px] font-bold uppercase text-indigo-600 truncate block">{{ $activeCourse['category'] }}</span>
                                            @else
                                                <span></span>
                                            @endif
                                            <x-student.access-badge :state="$activeCourse['access_state']" :label="$activeCourse['badge_details']['label']" size="xs" />
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ $activeCourse['title'] }}</h4>
                                        <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500">
                                            @if($activeCourse['access_state'] === 'lifetime')
                                                <span class="text-emerald-600 font-semibold">Lifetime Access</span>
                                            @elseif($activeCourse['formatted_expiry'])
                                                <span>Exp: {{ $activeCourse['formatted_expiry'] }}</span>
                                                <span>&bull;</span>
                                                <span class="{{ $activeCourse['access_state'] === 'expired' ? 'text-rose-600 font-semibold' : ($activeCourse['access_state'] === 'expiring' ? 'text-amber-600 font-semibold' : 'text-slate-500') }}">
                                                    {{ $activeCourse['remaining_days_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[10px] font-semibold text-slate-500">
                                        <span>Progress</span>
                                        <span>{{ $activeCourse['progress']['percentage'] }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden" role="progressbar" aria-valuenow="{{ $activeCourse['progress']['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $activeCourse['progress']['percentage'] }}%"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($activeCourse['access_state'] === 'expired')
                                        <form action="{{ route('student.courses.purchase', $activeCourse['model']) }}" method="POST" class="w-full">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-500 transition cursor-pointer">
                                                {{ $activeCourse['renewal_label'] }} &rarr;
                                            </button>
                                        </form>
                                    @else
                                        @if($activeCourse['can_renew'])
                                            <form action="{{ route('student.courses.purchase', $activeCourse['model']) }}" method="POST" class="shrink-0">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 hover:bg-amber-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition cursor-pointer">
                                                    Renew
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ $activeCourse['actionUrl'] }}" class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition">
                                            {{ $activeCourse['actionLabel'] }} &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @elseif($stats['enrolled_courses'] > 0)
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center space-y-3">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">You don't have any courses in progress</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">You have successfully completed all your active enrolled courses! Explore our catalog to acquire new practical marketing skills.</p>
                <div>
                    <a href="{{ route('courses') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                        Browse Courses &rarr;
                    </a>
                </div>
            </div>
        @else
            <x-student.empty-state
                title="Start your learning journey"
                description="You're ready to start learning! You haven't enrolled in any courses yet. Explore Marketian Mind courses to start acquiring practical marketing skills for your business."
                :actionUrl="route('courses')"
                actionLabel="Explore Courses"
            >
                <svg class="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-student.empty-state>
        @endif
    </div>

    <!-- 4. Completed Courses Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Completed Courses
                </h2>
                <p class="text-xs text-slate-500">
                    Curriculum tracks you have completed 100%
                </p>
            </div>
            @if(count($completedCourses) > 0)
                <span class="text-xs font-semibold text-emerald-600">
                    {{ count($completedCourses) }} {{ \Illuminate\Support\Str::plural('Course', count($completedCourses)) }} Completed
                </span>
            @endif
        </div>

        @if(count($completedCourses) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($completedCourses as $completed)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white shadow-xs hover:shadow-md transition overflow-hidden">
                        <div class="relative aspect-video w-full bg-slate-100 overflow-hidden">
                            @if($completed['thumbnail'])
                                <img src="{{ $completed['thumbnail'] }}" alt="{{ $completed['title'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-emerald-600 to-teal-800 flex items-center justify-center text-white font-black text-xl">
                                    MM
                                </div>
                            @endif
                            <div class="absolute top-2.5 right-2.5">
                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-600/90 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Completed
                                </span>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    @if($completed['category'])
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block">
                                            {{ $completed['category'] }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif
                                    <x-student.access-badge :state="$completed['access_state']" :label="$completed['badge_details']['label']" size="xs" />
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm line-clamp-2 leading-snug">
                                    {{ $completed['title'] }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-[11px] text-slate-500">
                                    @if($completed['completed_at'])
                                        <span>Finished: {{ \Carbon\Carbon::parse($completed['completed_at'])->format('M d, Y') }}</span>
                                    @endif
                                    @if($completed['access_state'] === 'lifetime')
                                        <span>&bull;</span>
                                        <span class="text-emerald-600 font-semibold">Lifetime Access</span>
                                    @elseif($completed['formatted_expiry'])
                                        <span>&bull;</span>
                                        <span class="{{ $completed['access_state'] === 'expired' ? 'text-rose-600 font-semibold' : ($completed['access_state'] === 'expiring' ? 'text-amber-600 font-semibold' : 'text-slate-500') }}">
                                            {{ $completed['remaining_days_text'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                @if(!empty($completed['certificate']))
                                    <a href="{{ route('student.certificates.show', $completed['certificate']) }}" class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-500 shadow-2xs transition">
                                        View Certificate &rarr;
                                    </a>
                                @endif

                                @if($completed['access_state'] === 'expired')
                                    <form action="{{ route('student.courses.purchase', $completed['model']) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 shadow-2xs transition cursor-pointer">
                                            {{ $completed['renewal_label'] }} &rarr;
                                        </button>
                                    </form>
                                @else
                                    <div class="flex items-center gap-2">
                                        @if($completed['can_renew'])
                                            <form action="{{ route('student.courses.purchase', $completed['model']) }}" method="POST" class="shrink-0">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-amber-500 hover:bg-amber-600 px-3 py-2 text-xs font-bold text-white transition cursor-pointer">
                                                    Renew
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ $completed['actionUrl'] }}" class="flex-1 inline-flex items-center justify-center rounded-xl bg-slate-50 border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 shadow-2xs transition">
                                            {{ $completed['actionLabel'] }} &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center">
                <p class="text-xs font-semibold text-slate-600">No courses completed yet.</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Keep learning through your curriculum tracks to complete your first course and earn your certificate.</p>
            </div>
        @endif
    </div>

    <!-- 5. Earned Certificates Section -->
    <div class="space-y-4" id="certificates-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Earned Certificates
                </h2>
                <p class="text-xs text-slate-500">
                    Official certifications earned from completed Marketian Mind courses
                </p>
            </div>
            @if(count($recentCertificates) > 0)
                <span class="text-xs font-semibold text-amber-600">
                    {{ count($recentCertificates) }} {{ \Illuminate\Support\Str::plural('Certificate', count($recentCertificates)) }}
                </span>
            @endif
        </div>

        @if(count($recentCertificates) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($recentCertificates as $cert)
                    <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/40 via-white to-white p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </span>
                                <span class="text-[10px] font-mono font-bold text-slate-400">
                                    {{ $cert->certificate_number }}
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-xs line-clamp-2">
                                {{ $cert->course_title }}
                            </h4>
                            <p class="text-[11px] text-slate-500">
                                Issued: {{ $cert->issued_at?->format('M d, Y') ?? 'Recently' }}
                            </p>
                        </div>
                        <a href="{{ route('student.certificates.show', $cert) }}" class="inline-flex items-center justify-center rounded-xl bg-amber-500/10 hover:bg-amber-500 hover:text-white border border-amber-300/60 px-3 py-2 text-xs font-bold text-amber-800 transition">
                            View Certificate &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center">
                <p class="text-xs font-semibold text-slate-600">Complete a course to earn your first certificate.</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Upon 100% completion of any course, your verifiable certificate will appear here.</p>
            </div>
        @endif
    </div>

    <!-- 6. Two-Column Grid: Saved Courses + Activity & Support -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-2">
        <!-- Main Column: Saved Courses + Recent Orders (2 cols) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- 6A. Saved Courses (Wishlist Preview) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Saved Courses
                        </h2>
                        <p class="text-xs text-slate-500">
                            Courses saved in your wishlist for future learning
                        </p>
                    </div>
                    <a href="{{ route('student.wishlist.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                        View all saved courses &rarr;
                    </a>
                </div>

                @if($savedCourses->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($savedCourses as $wishlist)
                            @if($wishlist->course)
                                <div class="flex gap-3.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs hover:shadow-sm transition">
                                    @if($wishlist->course->thumbnailUrl())
                                        <img src="{{ $wishlist->course->thumbnailUrl() }}" alt="{{ $wishlist->course->title }}" class="h-16 w-20 rounded-xl object-cover border border-slate-100 shrink-0">
                                    @else
                                        <div class="h-16 w-20 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                                            MM
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase text-indigo-600 truncate">{{ $wishlist->course->category?->name ?? 'Course' }}</p>
                                            <h4 class="text-xs font-bold text-slate-900 line-clamp-1">{{ $wishlist->course->title }}</h4>
                                            <p class="text-[11px] font-bold text-slate-700 mt-0.5">
                                                @if($wishlist->course->is_free)
                                                    <span class="text-emerald-600">Free</span>
                                                @else
                                                    ₹{{ number_format($wishlist->course->effectivePrice(), 2) }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="mt-2">
                                            <a href="{{ route('student.courses.show', $wishlist->course) }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-500">
                                                View Course &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center">
                        <p class="text-xs font-semibold text-slate-600">No saved courses yet.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Save courses to your wishlist while browsing to easily enroll in them later.</p>
                        <div class="mt-3">
                            <a href="{{ route('courses') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 transition">
                                Browse Courses &rarr;
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 6B. Recent Purchases / Orders -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Recent Purchases
                        </h2>
                        <p class="text-xs text-slate-500">
                            Your latest course transactions and order receipts
                        </p>
                    </div>
                    <a href="{{ route('student.orders.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                        View all orders &rarr;
                    </a>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
                    @if($recentOrders->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3.5">Order #</th>
                                        <th class="px-5 py-3.5">Course</th>
                                        <th class="px-5 py-3.5">Amount</th>
                                        <th class="px-5 py-3.5">Status</th>
                                        <th class="px-5 py-3.5 text-right">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($recentOrders as $order)
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900">
                                                {{ $order->order_number }}
                                            </td>
                                            <td class="px-5 py-3.5 font-semibold text-slate-900">
                                                {{ $order->course->title }}
                                            </td>
                                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                                {{ $order->formattedAmount() }}
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $order->status->badgeClasses() }}">
                                                    {{ $order->status->label() }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <a href="{{ route('student.orders.show', $order) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition">
                                                    Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <p class="text-xs font-semibold text-slate-600">No orders yet.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">When you purchase course certifications, your invoices will appear here.</p>
                            <div class="mt-3">
                                <a href="{{ route('courses') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">
                                    Browse Paid Courses &rarr;
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Column: Notifications + Recent Activity + Support (1 col) -->
        <div class="space-y-6">
            <!-- 6C. Notifications Preview -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Notifications
                        </h2>
                        <p class="text-xs text-slate-500">
                            Latest alerts &amp; announcements
                        </p>
                    </div>
                    <a href="{{ route('student.notifications.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                        View all &rarr;
                    </a>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm space-y-3">
                    @if($recentNotifications->count() > 0)
                        <div class="divide-y divide-slate-100">
                            @foreach($recentNotifications as $notif)
                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-start gap-2.5">
                                    @if(is_null($notif->read_at))
                                        <span class="h-2 w-2 rounded-full bg-indigo-600 mt-1.5 shrink-0" title="Unread"></span>
                                    @else
                                        <span class="h-2 w-2 rounded-full bg-slate-300 mt-1.5 shrink-0"></span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-900 truncate">
                                            {{ $notif->data['title'] ?? 'Notification' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                            {{ $notif->data['message'] ?? '' }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $notif->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <p class="text-xs font-semibold text-slate-600">{{ "You're all caught up." }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">No new notifications at this time.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 6D. Recent Learning Activity (Authentic Records) -->
            <div class="space-y-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Recent Learning Activity
                    </h2>
                    <p class="text-xs text-slate-500">
                        Real lesson completion timeline
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm space-y-3">
                    @if($recentActivities->count() > 0)
                        <div class="divide-y divide-slate-100">
                            @foreach($recentActivities as $activity)
                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-start gap-2.5">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-slate-900 truncate">
                                            Completed: {{ $activity->lesson?->title ?? 'Lesson' }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 truncate">
                                            {{ $activity->lesson?->module?->course?->title ?? 'Course' }} &bull; {{ $activity->completed_at?->diffForHumans() ?? 'Recently' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 text-center">
                            <p class="text-xs font-semibold text-slate-600">No recent learning activity.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">When you finish lessons, your learning timeline will update here.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 6E. Quick Account Shortcuts & Support -->
            <div class="space-y-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Account &amp; Support
                    </h2>
                    <p class="text-xs text-slate-500">
                        Manage credentials &amp; reach mentors
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm space-y-4">
                    <!-- User summary -->
                    <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-sm shadow-xs shrink-0">
                            {{ strtoupper(substr($user->name ?: 'S', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900 text-xs truncate">{{ $user->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $user->email }}</p>
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 mt-0.5">
                                {{ ucfirst($user->role->value ?? 'Student') }} Account
                            </span>
                        </div>
                    </div>

                    <!-- Shortcuts -->
                    <div class="space-y-1.5">
                        <a href="{{ route('student.profile') }}" class="flex items-center justify-between p-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profile Information</span>
                            </div>
                            <span class="text-slate-400">&rsaquo;</span>
                        </a>

                        <a href="{{ route('student.profile') }}#password-settings" class="flex items-center justify-between p-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <span>Change Password</span>
                            </div>
                            <span class="text-slate-400">&rsaquo;</span>
                        </a>

                        <a href="{{ route('student.orders.index') }}" class="flex items-center justify-between p-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Purchase History</span>
                            </div>
                            <span class="text-slate-400">&rsaquo;</span>
                        </a>
                    </div>

                    <!-- Support Guidance -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-500">
                        <p class="font-bold text-slate-800">Curriculum Guidance</p>
                        <p class="mt-0.5">Reach out to our curriculum mentors anytime at <a href="mailto:support@marketianmind.com" class="text-indigo-600 font-medium hover:underline">support@marketianmind.com</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection