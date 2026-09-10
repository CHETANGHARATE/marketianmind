@extends('layouts.public')

@section('subcontent')
<div>
    <!-- Courses Page Header -->
    <section class="py-14 sm:py-18 bg-gradient-to-b from-indigo-50/60 via-slate-50/40 to-white border-b border-slate-200">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 mb-4">
                Curriculum &amp; Programs
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                Online Marketing Courses for Business Owners
            </h1>
            <p class="mt-2 text-xs font-bold uppercase tracking-wider text-indigo-600">
                Explore Practical Marketing Programs
            </p>
            <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Step-by-step, actionable education designed to help business owners and startup founders attract customers and scale revenue online without expensive marketing agencies.
            </p>
        </div>
    </section>

    <!-- Course Catalog Section -->
    <section class="py-12 bg-white min-h-screen">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Search & Filters Bar (No JavaScript Required) -->
            <div class="rounded-2xl border border-slate-200/90 bg-slate-50/70 p-5 sm:p-6 shadow-xs">
                <form method="GET" action="{{ route('courses') }}" class="space-y-4">
                    <!-- Top Search Row -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="q"
                                value="{{ $searchQuery }}"
                                placeholder="Search courses by topic, skill, or instructor..."
                                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 shadow-2xs transition"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition cursor-pointer shrink-0"
                            >
                                Search Courses
                            </button>

                            @if($hasActiveFilters)
                                <a
                                    href="{{ route('courses') }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition shrink-0"
                                >
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Filter Controls Row (Category, Pricing, Sort) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-200/80 text-xs">
                        <!-- Category Filter -->
                        <div>
                            <label for="category" class="block font-semibold text-slate-700 mb-1">
                                Category
                            </label>
                            <select
                                id="category"
                                name="category"
                                onchange="this.form.submit()"
                                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 shadow-2xs transition"
                            >
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->slug }}" {{ $selectedCategorySlug === $category->slug ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->courses_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Price / Type Filter -->
                        <div>
                            <label for="type" class="block font-semibold text-slate-700 mb-1">
                                Course Type
                            </label>
                            <select
                                id="type"
                                name="type"
                                onchange="this.form.submit()"
                                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 shadow-2xs transition"
                            >
                                <option value="all" {{ $selectedType === 'all' ? 'selected' : '' }}>All Courses (Free &amp; Paid)</option>
                                <option value="free" {{ $selectedType === 'free' ? 'selected' : '' }}>Free Courses Only</option>
                                <option value="paid" {{ $selectedType === 'paid' ? 'selected' : '' }}>Paid &amp; Premium Programs</option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div>
                            <label for="sort" class="block font-semibold text-slate-700 mb-1">
                                Sort By
                            </label>
                            <select
                                id="sort"
                                name="sort"
                                onchange="this.form.submit()"
                                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 shadow-2xs transition"
                            >
                                <option value="newest" {{ $selectedSort === 'newest' ? 'selected' : '' }}>Newest Additions</option>
                                <option value="oldest" {{ $selectedSort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="price_low" {{ $selectedSort === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ $selectedSort === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="featured" {{ $selectedSort === 'featured' ? 'selected' : '' }}>Featured First</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Active Filters Indicators & Result Count Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100 text-xs">
                <div>
                    @if($courses->total() > 0)
                        <p class="font-bold text-slate-900 text-sm">
                            Showing {{ $courses->total() }} {{ \Illuminate\Support\Str::plural('course', $courses->total()) }}
                            @if($searchQuery !== '')
                                matching <span class="text-indigo-600">"{{ $searchQuery }}"</span>
                            @endif
                        </p>
                    @else
                        <p class="font-bold text-slate-900 text-sm">
                            No matching courses found
                        </p>
                    @endif
                </div>

                @if($hasActiveFilters)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-400 font-medium">Active filters:</span>

                        @if($searchQuery !== '')
                            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 border border-indigo-100">
                                Keyword: "{{ $searchQuery }}"
                            </span>
                        @endif

                        @if($selectedCategory)
                            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 border border-indigo-100">
                                Category: {{ $selectedCategory->name }}
                            </span>
                        @endif

                        @if($selectedType !== 'all')
                            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 border border-indigo-100">
                                Type: {{ ucfirst($selectedType) }}
                            </span>
                        @endif

                        @if($selectedSort !== 'newest')
                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">
                                Sort: {{ ucfirst(str_replace('_', ' ', $selectedSort)) }}
                            </span>
                        @endif

                        <a href="{{ route('courses') }}" class="text-indigo-600 hover:text-indigo-700 font-bold underline ml-1">
                            Clear all
                        </a>
                    </div>
                @endif
            </div>

            <!-- Published Courses Grid -->
            @if($courses->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($courses as $course)
                        @php
                            $isEnrolled = isset($enrolledCourseIds[$course->id]);
                            $isCompleted = isset($completedCourseIds[$course->id]);
                        @endphp

                        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs hover:shadow-md hover:border-slate-300 transition duration-200">
                            <!-- Course Thumbnail -->
                            <div class="relative aspect-video bg-slate-100 overflow-hidden">
                                @if($course->thumbnailUrl())
                                    <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-800 flex items-center justify-center p-6 text-center text-white font-black text-2xl">
                                        MM
                                    </div>
                                @endif

                                <!-- Top Badges -->
                                <div class="absolute top-3 left-3 flex flex-wrap items-center gap-1.5">
                                    @if($course->featured)
                                        <span class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                            Featured
                                        </span>
                                    @endif

                                    @if($isCompleted)
                                        <span class="inline-flex items-center rounded-md bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                            Completed
                                        </span>
                                    @elseif($isEnrolled)
                                        <span class="inline-flex items-center rounded-md bg-sky-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                            Enrolled
                                        </span>
                                    @endif
                                </div>

                                <!-- Price Pill -->
                                <div class="absolute bottom-3 right-3">
                                    @if($course->is_free)
                                        <span class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-black text-white shadow-sm">
                                            FREE
                                        </span>
                                    @elseif($course->discount_price && $course->discount_price < $course->price)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-900/90 backdrop-blur-xs px-3 py-1 text-xs font-bold text-white shadow-sm">
                                            <span>₹{{ number_format($course->discount_price, 0) }}</span>
                                            <span class="text-slate-400 line-through text-[11px]">₹{{ number_format($course->price, 0) }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-900/90 backdrop-blur-xs px-3 py-1 text-xs font-bold text-white shadow-sm">
                                            ₹{{ number_format($course->price, 0) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Course Body -->
                            <div class="flex flex-1 flex-col p-6">
                                <div class="flex items-center gap-2 mb-2 text-xs">
                                    @if($course->category)
                                        <span class="font-bold text-indigo-600 uppercase tracking-wider text-[10px]">
                                            {{ $course->category->name }}
                                        </span>
                                        <span class="text-slate-300">&bull;</span>
                                    @endif
                                    <span class="text-slate-500">
                                        {{ $course->modules_count }} {{ \Illuminate\Support\Str::plural('Module', $course->modules_count) }}
                                    </span>
                                    @if($course->estimated_duration)
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="text-slate-500">{{ $course->estimated_duration }}</span>
                                    @endif
                                </div>

                                <h2 class="text-lg font-bold text-slate-900 leading-snug">
                                    <a href="{{ route('courses.show', $course) }}" class="hover:text-indigo-600 transition line-clamp-2">
                                        {{ $course->title }}
                                    </a>
                                </h2>

                                <p class="mt-2 text-xs text-slate-600 line-clamp-3 leading-relaxed flex-1">
                                    {{ $course->short_description ?? \Illuminate\Support\Str::limit($course->description, 120) }}
                                </p>

                                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                    <span class="text-slate-400 text-[11px] truncate max-w-[140px]">
                                        By {{ $course->instructor_name ?? 'Marketian Mind Faculty' }}
                                    </span>

                                    @if($isCompleted)
                                        <a href="{{ route('student.courses.show', $course) }}" class="inline-flex items-center gap-1 font-bold text-emerald-600 hover:text-emerald-700 transition">
                                            Review Course &rarr;
                                        </a>
                                    @elseif($isEnrolled)
                                        <a href="{{ route('student.courses.show', $course) }}" class="inline-flex items-center gap-1 font-bold text-sky-600 hover:text-sky-700 transition">
                                            Continue Learning &rarr;
                                        </a>
                                    @else
                                        <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-700 transition">
                                            View Course &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($courses->hasPages())
                    <div class="pt-6 border-t border-slate-100">
                        {{ $courses->links() }}
                    </div>
                @endif
            @elseif($hasActiveFilters)
                <!-- Empty State for Filters -->
                <div class="rounded-3xl border border-slate-200/90 bg-slate-50/50 p-12 text-center max-w-xl mx-auto">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4 shadow-2xs">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-slate-900">
                        No courses found matching your criteria
                    </h2>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                        We couldn't find any courses matching your current search or filters. Try adjusting your search query or resetting the filters.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('courses') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                            Clear Filters &amp; View All
                        </a>
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                            Back to Homepage
                        </a>
                    </div>
                </div>
            @else
                <!-- Featured Master Program (when no published courses in DB or catalog is empty) -->
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
                                        Small Business &amp; Startup Focus
                                    </span>
                                </div>
                            </div>

                            <div class="lg:col-span-4 flex flex-col justify-center lg:items-end">
                                <a href="{{ route('courses.show', 'digital-marketing-for-business-owners') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition w-full sm:w-auto text-center">
                                    View Syllabus &amp; Details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

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
            @endif
        </div>
    </section>

    <!-- Why Our Courses Work -->
    <section class="py-16 bg-slate-50 border-t border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Created with the Realities of Small Business in Mind
            </h2>
            <p class="mt-3 text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                You don't have 40 hours a week to study marketing theory. Every lesson in our courses is condensed, actionable, and ready to implement on your actual business within minutes.
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