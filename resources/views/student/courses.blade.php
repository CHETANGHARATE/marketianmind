@extends('layouts.student')

@section('subcontent')
<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Browse Courses
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                All marketing courses and practical training programs for business owners.
            </p>
        </div>
        <a href="{{ route('courses') }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
            View Public Course Catalog &rarr;
        </a>
    </div>

    <!-- Enrolled Courses Section -->
    @if(isset($enrolledCourses) && count($enrolledCourses) > 0)
        <div>
            <h2 class="text-lg font-bold text-slate-900 mb-4">My Enrolled Courses</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($enrolledCourses as $course)
                    <div class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition">
                        <!-- Thumbnail -->
                        <div class="relative aspect-video bg-slate-100 overflow-hidden">
                            @if(!empty($course['thumbnail']))
                                <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-slate-100 flex items-center justify-center p-6 text-center">
                                    <span class="text-xs font-semibold text-slate-400">{{ $course['title'] }}</span>
                                </div>
                            @endif

                            @if($course['is_completed'])
                                <span class="absolute top-3 right-3 inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white shadow-sm">
                                    Completed ✓
                                </span>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                                @if(!empty($course['category']))
                                    <span class="font-semibold text-indigo-600">{{ $course['category'] }}</span>
                                    <span>&bull;</span>
                                @endif
                                <span>{{ $course['modules'] }} {{ Str::plural('Module', $course['modules']) }}</span>
                                @if(!empty($course['duration']))
                                    <span>&bull;</span>
                                    <span>{{ $course['duration'] }}</span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-slate-900 line-clamp-2 mb-2">
                                {{ $course['title'] }}
                            </h3>

                            <p class="text-xs text-slate-500 line-clamp-2 mb-5 flex-1">
                                {{ $course['description'] }}
                            </p>

                            <!-- Progress Bar -->
                            <div class="pt-3 border-t border-slate-100 mb-4">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-slate-700">{{ $course['progress']['percentage'] }}% Complete</span>
                                    <span class="text-slate-400">
                                        {{ $course['progress']['completed'] }} of {{ $course['progress']['total'] }} lessons
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-300" style="width: {{ $course['progress']['percentage'] }}%"></div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <a href="{{ $course['actionUrl'] }}" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                                {{ $course['actionLabel'] }} &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Browse Courses Section -->
    <div class="pt-6 border-t border-slate-200">
        <div class="mb-6">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
                Available Courses
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Explore our full curriculum of self-paced marketing training for business owners.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-student.course-card
                title="Digital Marketing for Business Owners"
                description="A practical, non-agency framework to understand digital channels, customer acquisition, and marketing funnels without agency fees."
                :modules="6"
                duration="4 Weeks"
                level="Beginner to Intermediate"
                badge="Featured Course"
                :actionUrl="route('course.details')"
                actionLabel="Course Curriculum & Preview"
            />
        </div>
    </div>
</div>
@endsection