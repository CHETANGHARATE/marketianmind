@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            My Learning
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            All marketing courses and practical training programs enrolled under your account.
        </p>
    </div>

    <!-- Content Area -->
    @if (count($enrolledCourses) === 0)
        <x-student.empty-state
            title="You haven't started any courses yet"
            description="Explore Marketian Mind courses and start learning practical marketing strategies designed specifically for small business owners and startup founders."
            :actionUrl="route('student.courses')"
            actionLabel="Browse Courses"
        >
            <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </x-student.empty-state>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($enrolledCourses as $course)
                <x-student.course-card
                    :title="$course['title']"
                    :description="$course['description']"
                    :modules="$course['modules']"
                    :duration="$course['duration']"
                    :actionUrl="$course['actionUrl']"
                    actionLabel="Continue Learning"
                />
            @endforeach
        </div>
    @endif
</div>
@endsection