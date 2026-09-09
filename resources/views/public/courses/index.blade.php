@extends('layouts.public')

@section('subcontent')
<div>
    <!-- Courses Page Header -->
    <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 to-white border-b border-slate-200">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 mb-6">
                Curriculum & Programs
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                Online Marketing Courses for Business Owners
            </h1>
            <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Step-by-step, actionable education designed to help entrepreneurs attract customers and grow their business online without expensive marketing agencies.
            </p>
        </div>
    </section>

    <!-- Courses Listing Container -->
    <section class="py-16 bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600">Available & Upcoming Programs</h2>
                    <p class="text-2xl font-bold tracking-tight text-slate-900 mt-1">Explore Marketing Programs</p>
                </div>
                <div class="inline-flex items-center gap-2 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Self-Paced & Practical Architecture</span>
                </div>
            </div>

            <!-- Featured Master Program (when no published courses in DB or as fallback) -->
            @if(!isset($courses) || $courses->isEmpty())
                <div class="mb-12">
                    <div class="rounded-2xl border-2 border-indigo-500/20 bg-gradient-to-br from-indigo-50/30 via-white to-white p-6 sm:p-10 shadow-sm">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                            <div class="lg:col-span-8">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">
                                        Featured Course
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Coming Soon
                                    </span>
                                    <span class="text-xs text-slate-500">6 Core Modules</span>
                                </div>

                                <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                                    Digital Marketing for Business Owners
                                </h3>

                                <p class="mt-4 text-base text-slate-600 leading-relaxed max-w-2xl">
                                    Learn how to manage and grow your business online without depending completely on expensive agencies. Gain full mastery of customer acquisition, content creation, social media, and digital channels.
                                </p>

                                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Self-Paced Learning
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Zero Complex Theory
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                        Small Business & Startup Focus
                                    </span>
                                </div>
                            </div>

                            <div class="lg:col-span-4 flex flex-col justify-center lg:items-end">
                                <a href="{{ route('course.details') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition w-full sm:w-auto text-center">
                                    View Course &rarr;
                                </a>
                                <span class="mt-3 text-xs text-slate-400 text-center lg:text-right">
                                    Free syllabus preview available
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Published Courses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                    @foreach($courses as $course)
                        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition duration-200">
                            <!-- Course Thumbnail -->
                            <div class="relative aspect-video bg-slate-100 overflow-hidden">
                                @if($course->thumbnailUrl())
                                    <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-slate-100 flex items-center justify-center p-6 text-center">
                                        <span class="text-sm font-semibold text-slate-400">{{ $course->title }}</span>
                                    </div>
                                @endif

                                @if($course->featured)
                                    <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-indigo-600 px-2.5 py-0.5 text-xs font-semibold text-white shadow-sm">
                                        Featured
                                    </span>
                                @endif

                                <div class="absolute bottom-3 right-3">
                                    @if($course->is_free)
                                        <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-bold text-white shadow-sm">
                                            Free
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-900/90 backdrop-blur-sm px-2.5 py-0.5 text-xs font-bold text-white shadow-sm">
                                            ₹{{ number_format($course->effectivePrice(), 2) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Course Body -->
                            <div class="flex flex-1 flex-col p-6">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($course->category)
                                        <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                                            {{ $course->category->name }}
                                        </span>
                                        <span class="text-slate-300">&bull;</span>
                                    @endif
                                    <span class="text-xs text-slate-500">
                                        {{ $course->modules_count }} {{ Str::plural('Module', $course->modules_count) }}
                                    </span>
                                    @if($course->estimated_duration)
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="text-xs text-slate-500">{{ $course->estimated_duration }}</span>
                                    @endif
                                </div>

                                <h3 class="text-lg font-bold text-slate-900 leading-snug">
                                    <a href="{{ route('courses.show', $course) }}" class="hover:text-indigo-600 transition">
                                        {{ $course->title }}
                                    </a>
                                </h3>

                                <p class="mt-2 text-xs text-slate-600 line-clamp-3 leading-relaxed flex-1">
                                    {{ $course->short_description ?? Str::limit($course->description, 120) }}
                                </p>

                                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                                    <div class="text-xs text-slate-500">
                                        {{ $course->lessons_count }} {{ Str::plural('Lesson', $course->lessons_count) }}
                                    </div>
                                    <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition">
                                        View Course &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Future Specialized Tracks -->
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-6">Upcoming Specialized Tracks</h3>

                @php
                    $upcomingTracks = [
                        [
                            'title' => 'Social Media Strategy for Founders',
                            'description' => 'A practical framework to plan, create, and distribute organic social content that generates consistent customer inquiries without burnout.',
                            'badge' => 'Coming Soon',
                            'modules' => '4 Modules',
                            'audience' => 'Solo Founders & Service Providers',
                            'url' => route('courses'),
                        ],
                        [
                            'title' => 'Local Business Customer Acquisition',
                            'description' => 'How neighborhood retail, clinics, and local services dominate local search, maps, and community word-of-mouth marketing.',
                            'badge' => 'Coming Soon',
                            'modules' => '5 Modules',
                            'audience' => 'Local Store Owners & Clinics',
                            'url' => route('courses'),
                        ],
                        [
                            'title' => 'Brand Communication & Value Clarity',
                            'description' => 'Clarify your message so prospective clients immediately understand what you do and why they should buy from you.',
                            'badge' => 'Coming Soon',
                            'modules' => '4 Modules',
                            'audience' => 'Product Builders & Consultants',
                            'url' => route('courses'),
                        ]
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($upcomingTracks as $track)
                        <x-course-card
                            :title="$track['title']"
                            :description="$track['description']"
                            :badge="$track['badge']"
                            :modules="$track['modules']"
                            :audience="$track['audience']"
                            :url="$track['url']"
                        />
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Why Our Courses Work -->
    <section class="py-16 bg-slate-50">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Created with the Realities of Small Business in Mind
            </h2>
            <p class="mt-3 text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                You don't have 40 hours a week to study marketing. Every lesson in our courses is condensed, actionable, and ready to implement on your actual business within minutes.
            </p>
            <div class="mt-8">
                <a href="{{ route('contact') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                    Want to suggest a specific course topic? Let us know &rarr;
                </a>
            </div>
        </div>
    </section>
</div>
@endsection