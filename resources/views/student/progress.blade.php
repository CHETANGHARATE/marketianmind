@extends('layouts.student')

@section('subcontent')
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            Learning Progress
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Track your completed lessons, quiz milestones, and marketing skills development.
        </p>
    </div>

    <!-- Progress Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <x-student.stat-card
            title="Learning Hours"
            :value="$metrics['total_learning_hours']"
            description="Total time spent learning"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-student.stat-card>

        <x-student.stat-card
            title="Completed Lessons"
            :value="$metrics['completed_lessons']"
            description="Video lessons finished"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-student.stat-card>

        <x-student.stat-card
            title="In Progress"
            :value="$metrics['courses_in_progress']"
            description="Active course curricula"
            color="sky"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </x-slot:icon>
        </x-student.stat-card>

        <x-student.stat-card
            title="Certificates"
            :value="$metrics['certificates_earned']"
            description="Earned upon completion"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </x-slot:icon>
        </x-student.stat-card>
    </div>

    <!-- Zero State / Detailed Journey -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-900">
            Course Completion Journey
        </h2>

        <x-student.empty-state
            title="Your learning journey will appear here once you start a course"
            description="When you begin watching lessons in our marketing modules, your real-time progress, quiz milestones, and course completion will be tracked here."
            :actionUrl="route('student.courses')"
            actionLabel="Start Learning"
        >
            <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </x-student.empty-state>
    </div>
</div>
@endsection