@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Browse Courses
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Practical online marketing education built for small business owners and startup founders.
            </p>
        </div>
        <a href="{{ route('courses') }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
            View Public Course Page &rarr;
        </a>
    </div>

    <!-- Course Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($courses as $course)
            <x-student.course-card
                :title="$course['title']"
                :description="$course['description']"
                :modules="$course['modules']"
                :duration="$course['duration']"
                :level="$course['level']"
                :badge="$course['badge']"
                :actionUrl="$course['actionUrl']"
                :actionLabel="$course['actionLabel']"
            />
        @empty
            <div class="col-span-full">
                <x-student.empty-state
                    title="No courses available yet"
                    description="Our educational curriculum is being actively developed. Check back soon for new practical marketing courses."
                />
            </div>
        @endforelse
    </div>
</div>
@endsection