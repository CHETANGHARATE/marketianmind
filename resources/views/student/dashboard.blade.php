@extends('layouts.student')

@section('subcontent')
<div class="space-y-8">
    <!-- Welcome Header Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 sm:p-10 text-white shadow-sm border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-500/20 px-3.5 py-1 text-xs font-semibold text-indigo-300 border border-indigo-500/30 mb-4">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                Student Dashboard &bull; Marketian Mind
            </div>

            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                Welcome back, {{ $user->name }}!
            </h1>

            <p class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed">
                Continue building your practical marketing knowledge and grow your business without depending on expensive agencies.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                    Browse Courses &rarr;
                </a>
                <a href="{{ route('student.progress') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    View Progress
                </a>
            </div>
        </div>

        <!-- Decorative background glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Statistics Overview Grid -->
    <div>
        <h2 class="text-base font-bold text-slate-900 mb-4">
            Learning Overview
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
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

            <!-- 2. Lessons Completed -->
            <x-student.stat-card
                title="Lessons Completed"
                :value="$stats['lessons_completed']"
                description="Completed practical video lessons"
                color="emerald"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 3. Learning Progress -->
            <x-student.stat-card
                title="Overall Progress"
                :value="$stats['progress_percentage'] . '%'"
                description="Course completion across your library"
                color="sky"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>
        </div>
    </div>

    <!-- Continue Learning Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Continue Learning
                </h2>
                <p class="text-xs text-slate-500">
                    Pick up where you left off
                </p>
            </div>
            <a href="{{ route('student.courses') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                Browse catalog &rarr;
            </a>
        </div>

        @if ($stats['enrolled_courses'] === 0)
            <x-student.empty-state
                title="No courses yet"
                description="Explore Marketian Mind courses and start learning practical marketing strategies tailored for small business owners and startup founders."
                :actionUrl="route('student.courses')"
                actionLabel="Explore Courses"
            >
                <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-student.empty-state>
        @endif
    </div>

    <!-- Featured Curriculum Preview -->
    <div class="space-y-4 pt-2">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">
                Recommended for Business Owners
            </h2>
            <span class="text-xs font-medium text-slate-500">Core Curriculum</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-student.course-card
                title="Digital Marketing for Business Owners"
                description="Learn how to acquire real customers digitally without relying on complex agency jargon or spending thousands on unproven tactics."
                :modules="6"
                duration="4 Weeks"
                level="Beginner to Intermediate"
                badge="Flagship Course"
                :actionUrl="route('course.details')"
                actionLabel="Preview Curriculum"
            />

            <!-- Philosophy Reminder Card -->
            <div class="flex flex-col justify-between rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 via-white to-white p-6 shadow-sm">
                <div>
                    <span class="inline-flex items-center rounded-lg bg-indigo-100/80 px-2.5 py-1 text-xs font-bold text-indigo-800">
                        Marketian Mind Philosophy
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-slate-900 tracking-tight">
                        Marketing Knowledge for Business Owners
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Marketing is not just posting on social media. It is about understanding your customer, creating the right offer, and generating consistent customer conversations.
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-indigo-50">
                    <a href="{{ route('about') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-500 transition">
                        Read the 5 Pillars of Marketing &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection