@extends('layouts.public')

@section('title', $course ? $course->title : 'Digital Marketing for Business Owners')
@section('meta_description', Str::limit(strip_tags($course ? ($course->short_description ?? $course->description) : 'Comprehensive marketing education for business owners and startup founders.'), 155))

@section('subcontent')
<div>
    @if($course)
        @php
            $totalLessonsCount = $course->modules->sum(fn($m) => $m->lessons->count());
            $hasSavings = $course->hasDiscount();
            $savingsAmount = $hasSavings ? ($course->price - $course->discount_price) : 0;
            $savingsPercentage = ($hasSavings && $course->price > 0) ? round(($savingsAmount / $course->price) * 100) : 0;
        @endphp

        <!-- Course Detail Hero Section -->
        <section class="py-12 sm:py-16 bg-gradient-to-b from-indigo-50/60 via-slate-50/40 to-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-8 flex-wrap">
                    <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
                    <span class="text-slate-300">&rsaquo;</span>
                    <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">Courses</a>
                    @if($course->category)
                        <span class="text-slate-300">&rsaquo;</span>
                        <a href="{{ route('courses', ['category' => $course->category->slug]) }}" class="hover:text-indigo-600 transition">
                            {{ $course->category->name }}
                        </a>
                    @endif
                    <span class="text-slate-300">&rsaquo;</span>
                    <span class="text-slate-900 font-medium truncate max-w-[280px]" aria-current="page">
                        {{ $course->title }}
                    </span>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
                    <!-- Left: Course Main Information -->
                    <div class="lg:col-span-8 space-y-6">
                        <!-- Badges Row -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            @if($course->category)
                                <a href="{{ route('courses', ['category' => $course->category->slug]) }}" class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/15 hover:bg-indigo-100 transition">
                                    {{ $course->category->name }}
                                </a>
                            @endif

                            @if($course->is_free)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    Free Course
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white shadow-2xs">
                                    Paid Program
                                </span>
                            @endif

                            @if($course->featured)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Featured Track
                                </span>
                            @endif

                            @if(isset($reviewsCount) && $reviewsCount > 0)
                                <a href="#course-reviews" class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-500/20 hover:bg-amber-500/20 transition">
                                    <svg class="h-3.5 w-3.5 text-amber-500 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span>{{ number_format($averageRating, 1) }} ({{ $reviewsCount }} {{ Str::plural('review', $reviewsCount) }})</span>
                                </a>
                            @endif
                        </div>

                        <!-- Course Title -->
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-slate-900 leading-tight">
                            {{ $course->title }}
                        </h1>

                        <!-- Course Short Description -->
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-3xl">
                            {{ $course->short_description ?? Str::limit($course->description, 180) }}
                        </p>

                        <!-- Key Course Facts Bar -->
                        <div class="pt-4 border-t border-slate-200/80 flex flex-wrap items-center gap-x-8 gap-y-3 text-xs text-slate-600">
                            @if($course->instructor_name)
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Instructor: <strong class="text-slate-900 font-semibold">{{ $course->instructor_name }}</strong></span>
                                </div>
                            @endif

                            @if($course->estimated_duration)
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Duration: <strong class="text-slate-900 font-semibold">{{ $course->estimated_duration }}</strong></span>
                                </div>
                            @endif

                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span>Modules: <strong class="text-slate-900 font-semibold">{{ $course->modules->count() }} {{ Str::plural('Module', $course->modules->count()) }}</strong></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Lessons: <strong class="text-slate-900 font-semibold">{{ $totalLessonsCount }} {{ Str::plural('Lesson', $totalLessonsCount) }}</strong></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Format: <strong class="text-slate-900 font-semibold">Self-Paced Online</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Sticky Pricing & Action Sidebar Card -->
                    <div class="lg:col-span-4 w-full">
                        <div class="rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-7 shadow-sm sticky top-6">
                            <!-- Course Thumbnail / Media Preview -->
                            <div class="aspect-video rounded-2xl overflow-hidden mb-6 bg-slate-100 border border-slate-100 shadow-2xs relative">
                                @if($course->thumbnailUrl())
                                    <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-900 flex flex-col items-center justify-center p-6 text-center text-white">
                                        <span class="text-3xl font-black tracking-wider mb-1">MM</span>
                                        <span class="text-xs font-semibold text-indigo-200 line-clamp-2">{{ $course->title }}</span>
                                    </div>
                                @endif

                                @if($course->featured)
                                    <span class="absolute top-3 left-3 inline-flex items-center rounded-md bg-indigo-600/90 backdrop-blur-xs px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-2xs">
                                        Featured
                                    </span>
                                @endif
                            </div>

                            <!-- Pricing Display Area -->
                            <div class="text-center pb-5 border-b border-slate-100">
                                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400">Tuition &amp; Access</span>
                                <div class="mt-2 flex items-baseline justify-center gap-2 flex-wrap">
                                    @if($course->is_free)
                                        <span class="text-3xl sm:text-4xl font-black text-emerald-600">Free</span>
                                    @else
                                        <span class="text-3xl sm:text-4xl font-black text-slate-900">
                                            ₹{{ number_format($course->effectivePrice(), 2) }}
                                        </span>

                                        @if($hasSavings)
                                            <span class="text-sm font-semibold text-slate-400 line-through">
                                                ₹{{ number_format($course->price, 2) }}
                                            </span>
                                        @endif
                                    @endif
                                </div>

                                @if($hasSavings)
                                    <div class="mt-2">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-100">
                                            Save ₹{{ number_format($savingsAmount, 0) }} ({{ $savingsPercentage }}% OFF)
                                        </span>
                                    </div>
                                @endif

                                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                    @if($course->is_free)
                                        100% free lifetime access for business owners &amp; founders.
                                    @else
                                        One-time enrollment &bull; Lifetime access &bull; All future updates included
                                    @endif
                                </p>
                            </div>

                            <!-- Student Progress for Enrolled Students -->
                            @if($isEnrolled)
                                <div class="mt-5 rounded-2xl bg-slate-50 p-4 border border-slate-200/80">
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-bold text-slate-800">Your Learning Progress</span>
                                        <span class="font-bold text-indigo-600">{{ $progress['percentage'] }}%</span>
                                    </div>
                                    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" style="width: {{ $progress['percentage'] }}%"></div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                                        <span>{{ $progress['completed'] }} of {{ $progress['total'] }} lessons completed</span>
                                        @if($isCompleted)
                                            <span class="text-emerald-600 font-bold flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Completed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Primary Dynamic CTA -->
                            <div class="mt-6">
                                @if($isCompleted)
                                    @if(isset($certificate) && $certificate)
                                        <a href="{{ route('student.certificates.show', $certificate) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition text-center mb-2.5">
                                            View Certificate &rarr;
                                        </a>
                                    @endif
                                    <a href="{{ route('student.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-xs hover:bg-emerald-500 transition text-center">
                                        Review Course &rarr;
                                    </a>
                                @elseif($isEnrolled)
                                    <a href="{{ route('student.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-sky-500 transition text-center">
                                        Continue Learning &rarr;
                                    </a>
                                @elseif($course->is_free)
                                    @auth
                                        @if(auth()->user()->isStudent())
                                            <form action="{{ route('student.courses.enroll', $course) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer text-center">
                                                    Enroll for Free &rarr;
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-800 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-slate-700 transition text-center">
                                                Manage Course (Admin) &rarr;
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition text-center">
                                            Login to Enroll Free &rarr;
                                        </a>
                                        <p class="mt-2.5 text-center text-xs text-slate-400">
                                            New here? <a href="{{ route('register') }}" class="text-indigo-600 font-semibold underline">Register free account</a>
                                        </p>
                                    @endauth
                                @else
                                    @auth
                                        @if(auth()->user()->isStudent())
                                            <form action="{{ route('student.courses.purchase', $course) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer text-center">
                                                    Buy Now &rarr;
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.courses.show', $course) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-800 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-slate-700 transition text-center">
                                                Manage Course (Admin) &rarr;
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-xs hover:bg-indigo-500 transition text-center font-bold">
                                            Buy Now &rarr;
                                        </a>
                                        <p class="mt-2.5 text-center text-xs text-slate-400">
                                            New here? <a href="{{ route('register') }}" class="text-indigo-600 font-semibold underline">Create free account</a>
                                        </p>
                                    @endauth
                                @endif
                            </div>

                            @auth
                                @if(auth()->user()->isStudent())
                                    <div class="mt-3">
                                        <form action="{{ route('student.wishlist.toggle', $course) }}" method="POST">
                                            @csrf
                                            @if(auth()->user()->hasInWishlist($course))
                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-100 hover:border-rose-300 transition shadow-2xs cursor-pointer">
                                                    <svg class="w-4 h-4 text-rose-600 fill-current" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4.5 4.5 0 116.364 6.364L10 19.071l-7.536-7.535a4 4 0 010-5.656z" clip-rule="evenodd" />
                                                    </svg>
                                                    Saved to Wishlist
                                                </button>
                                            @else
                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs cursor-pointer">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                    </svg>
                                                    Save to Wishlist
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                @endif
                            @endauth

                            <!-- Value Inclusions List -->
                            <div class="mt-6 pt-5 border-t border-slate-100 space-y-3 text-xs text-slate-600">
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Full curriculum &amp; all module lessons</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Actionable video &amp; text execution guides</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Self-paced access on desktop and mobile</span>
                                </div>

                                @if(! $course->is_free)
                                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
                                        <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <span>Secure 256-Bit Razorpay Checkout</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Course Content & Curriculum Body -->
        <section class="py-14 sm:py-18 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12">
                    <div class="lg:col-span-8 space-y-12">
                        <!-- About This Course -->
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Course Overview</span>
                            <h2 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                                About This Course
                            </h2>
                            <div class="mt-4 text-sm sm:text-base text-slate-600 leading-relaxed space-y-4 whitespace-pre-line">
                                {{ $course->description ?? $course->short_description }}
                            </div>
                        </div>

                        <!-- Curriculum Accordion Breakdown -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-6">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Curriculum Breakdown</span>
                                    <h2 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                                        Course Modules Preview
                                    </h2>
                                    <p class="mt-1 text-xs sm:text-sm text-slate-500">
                                        Structured modules designed to build actionable marketing competence without agency dependence.
                                    </p>
                                </div>
                                <div class="text-xs font-semibold text-slate-500 shrink-0">
                                    {{ $course->modules->count() }} {{ Str::plural('Module', $course->modules->count()) }} &bull; {{ $totalLessonsCount }} {{ Str::plural('Lesson', $totalLessonsCount) }}
                                </div>
                            </div>

                            @if($course->modules->count() > 0)
                                <div class="space-y-4">
                                    @foreach($course->modules as $index => $module)
                                        <details class="group rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden" @if($loop->first) open @endif>
                                            <summary class="p-5 sm:p-6 bg-white border-b border-slate-100 flex items-center justify-between gap-4 cursor-pointer select-none list-none hover:bg-slate-50/50 transition">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">
                                                            Module {{ sprintf('%02d', $index + 1) }}
                                                        </span>
                                                        <span class="text-slate-300">&bull;</span>
                                                        <span class="text-[11px] font-semibold text-slate-400">
                                                            {{ $module->lessons->count() }} {{ Str::plural('Lesson', $module->lessons->count()) }}
                                                        </span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-slate-900 leading-snug">
                                                        {{ $module->title }}
                                                    </h3>
                                                    @if($module->description)
                                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2">
                                                            {{ $module->description }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <div class="flex items-center gap-2 shrink-0">
                                                    <svg class="h-5 w-5 text-slate-400 transition-transform duration-200 group-open:rotate-180 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </summary>

                                            @if($module->lessons->count() > 0)
                                                <div class="divide-y divide-slate-100 bg-slate-50/30">
                                                    @foreach($module->lessons as $lesson)
                                                        <div class="px-5 sm:px-6 py-3.5 flex items-center justify-between gap-4 text-xs hover:bg-slate-50 transition">
                                                            <div class="flex items-center gap-3 min-w-0">
                                                                @if($lesson->isVideo())
                                                                    <svg class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                @elseif($lesson->isPdf())
                                                                    <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                @endif

                                                                <span class="font-medium text-slate-800 truncate">
                                                                    {{ $lesson->title }}
                                                                </span>
                                                            </div>

                                                            <div class="flex items-center gap-3 shrink-0">
                                                                @if($lesson->duration)
                                                                    <span class="text-slate-400 text-[11px]">{{ $lesson->duration }}</span>
                                                                @endif

                                                                @if($isEnrolled && $user && $lesson->isCompletedBy($user))
                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                        Completed
                                                                    </span>
                                                                @elseif($lesson->isPreview())
                                                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                                                        Preview
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center text-slate-400" title="Enrolled students only">
                                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                        </svg>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="p-4 text-center text-xs text-slate-400 bg-slate-50/50">
                                                    Lessons in this module will be available shortly.
                                                </div>
                                            @endif
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-8 text-center text-xs text-slate-500">
                                    Curriculum structure is currently being finalized.
                                </div>
                            @endif
                        </div>

                        <!-- Instructor Section -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-6 sm:p-8">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Course Faculty</span>
                            <h2 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Meet Your Instructor
                            </h2>

                            @if($course->instructor)
                                <div class="mt-5 flex flex-col sm:flex-row sm:items-start gap-5">
                                    @if($course->instructor->avatarUrl())
                                        <img src="{{ $course->instructor->avatarUrl() }}"
                                             alt="{{ $course->instructor->name }}"
                                             class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover border border-slate-200 shadow-xs shrink-0">
                                    @else
                                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl sm:text-2xl shadow-xs shrink-0">
                                            {{ $course->instructor->initials() }}
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <div>
                                                <h3 class="text-lg sm:text-xl font-bold text-slate-900">
                                                    {{ $course->instructor->name }}
                                                </h3>
                                                @if($course->instructor->title)
                                                    <p class="text-xs sm:text-sm font-medium text-slate-600">
                                                        {{ $course->instructor->title }}
                                                    </p>
                                                @endif
                                            </div>

                                            <span class="inline-flex items-center gap-1 self-start px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                </svg>
                                                {{ $course->instructor->publishedCoursesCount() }} {{ Str::plural('Course', $course->instructor->publishedCoursesCount()) }}
                                            </span>
                                        </div>

                                        @if($course->instructor->bio)
                                            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                                                {{ $course->instructor->bio }}
                                            </p>
                                        @else
                                            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                                Teaching practical, tested online marketing frameworks created specifically for small business owners and startup founders who need genuine customer acquisition without agency overhead.
                                            </p>
                                        @endif

                                        <!-- Social / External Links -->
                                        @if($course->instructor->website_url || $course->instructor->linkedin_url || $course->instructor->twitter_url)
                                            <div class="mt-4 flex items-center gap-4 pt-3 border-t border-slate-200">
                                                @if($course->instructor->website_url)
                                                    <a href="{{ $course->instructor->website_url }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       title="Personal Website"
                                                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                        </svg>
                                                        <span>Website</span>
                                                    </a>
                                                @endif

                                                @if($course->instructor->linkedin_url)
                                                    <a href="{{ $course->instructor->linkedin_url }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       title="LinkedIn Profile"
                                                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
                                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                                            <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.64 1.64 0 1 0-.02-3.28 1.64 1.64 0 0 0 .02 3.28m1.4 9.74v-8.37H5.06v8.37z"/>
                                                        </svg>
                                                        <span>LinkedIn</span>
                                                    </a>
                                                @endif

                                                @if($course->instructor->twitter_url)
                                                    <a href="{{ $course->instructor->twitter_url }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       title="Twitter / X Profile"
                                                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
                                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                                        </svg>
                                                        <span>Twitter</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-4">
                                    <div class="h-14 w-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-xs shrink-0">
                                        {{ substr($course->instructorDisplayName(), 0, 2) }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">
                                            {{ $course->instructorDisplayName() }}
                                        </h3>
                                        <p class="text-xs font-medium text-slate-500">
                                            Practitioner &bull; Marketian Mind Marketing Education
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Teaching practical, tested online marketing frameworks created specifically for small business owners and startup founders who need genuine customer acquisition without agency overhead.
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Additional Sidebar Context on Desktop -->
                    <div class="hidden lg:block lg:col-span-4 space-y-6">
                        <!-- Fast Discovery Backlink -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2">Explore All Courses</h4>
                            <p class="text-xs text-slate-500 leading-relaxed mb-3">
                                Filter programs by category, search by skill, or discover free tracks across Marketian Mind.
                            </p>
                            <a href="{{ route('courses') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition">
                                &larr; Back to Full Catalog
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

                <!-- Course Student Reviews & Ratings Section -->
        <section id="course-reviews" class="py-16 bg-white border-b border-slate-200 scroll-mt-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Section Title -->
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Student Feedback</span>
                        <h2 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                            Course Reviews &amp; Ratings
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Real feedback from business owners and founders enrolled in this program.
                        </p>
                    </div>

                    @if(isset($reviewsCount) && $reviewsCount > 0)
                        <div class="flex items-center gap-3 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200">
                            <span class="text-3xl font-black text-slate-900">{{ number_format($averageRating, 1) }}</span>
                            <div>
                                <div class="flex items-center text-amber-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="h-4 w-4 {{ $i <= round($averageRating) ? 'text-amber-400 fill-current' : 'text-slate-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-xs font-semibold text-slate-500">{{ $reviewsCount }} {{ Str::plural('rating', $reviewsCount) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    <!-- Left Column: Rating Breakdown & Write Review Box -->
                    <div class="lg:col-span-5 space-y-6">
                        <!-- Rating Breakdown Card -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6">
                            <h3 class="text-sm font-bold text-slate-900 mb-4">Rating Breakdown</h3>
                            @if(isset($ratingDistribution) && count($ratingDistribution) > 0)
                                <div class="space-y-2.5">
                                    @foreach($ratingDistribution as $star => $data)
                                        <div class="flex items-center gap-3 text-xs">
                                            <span class="w-12 font-semibold text-slate-700 flex items-center gap-1">
                                                <span>{{ $star }}</span>
                                                <svg class="h-3 w-3 text-amber-500 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </span>
                                            <div class="flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
                                                <div class="h-full bg-amber-400 rounded-full transition-all duration-300" style="width: {{ $data['percentage'] }}%"></div>
                                            </div>
                                            <span class="w-8 text-right font-medium text-slate-500">{{ $data['percentage'] }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500">No ratings breakdown available yet.</p>
                            @endif
                        </div>

                        <!-- Student Write / Edit Review Card -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            @if(auth()->check() && $isEnrolled)
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-slate-900">
                                        {{ isset($userReview) && $userReview ? 'Your Course Review' : 'Write a Review' }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                        &check; Enrolled Student
                                    </span>
                                </div>

                                @if(isset($userReview) && $userReview)
                                    <!-- Current Review Display -->
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-2 mb-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center text-amber-400">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="h-4 w-4 {{ $i <= $userReview->rating ? 'text-amber-400 fill-current' : 'text-slate-200' }}" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-[11px] text-slate-400">{{ $userReview->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-700 leading-relaxed">{{ $userReview->review }}</p>
                                        <div class="pt-2 flex items-center justify-between">
                                            <button type="button" onclick="document.getElementById('edit-review-form').classList.toggle('hidden')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 cursor-pointer">
                                                Edit Review &rarr;
                                            </button>
                                            <form action="{{ route('student.courses.reviews.destroy', [$course, $userReview]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your review?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                <!-- Review Form -->
                                <form id="edit-review-form" action="{{ route('student.courses.reviews.store', $course) }}" method="POST" class="space-y-4 {{ isset($userReview) && $userReview ? 'hidden' : '' }}">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Overall Rating <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="flex items-center gap-3">
                                            @for($r = 5; $r >= 1; $r--)
                                                <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-slate-700 hover:text-amber-500 transition">
                                                    <input type="radio"
                                                           name="rating"
                                                           value="{{ $r }}"
                                                           {{ old('rating', $userReview->rating ?? 5) == $r ? 'checked' : '' }}
                                                           required
                                                           class="text-amber-500 focus:ring-amber-500">
                                                    <span>{{ $r }}★</span>
                                                </label>
                                            @endfor
                                        </div>
                                    </div>

                                    <div>
                                        <label for="review" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Your Honest Review <span class="text-rose-500">*</span>
                                        </label>
                                        <textarea name="review"
                                                  id="review"
                                                  rows="3"
                                                  required
                                                  minlength="5"
                                                  maxlength="2000"
                                                  placeholder="What did you learn? How has this helped your business growth?"
                                                  class="w-full rounded-xl border border-slate-300 p-3 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600">{{ old('review', $userReview->review ?? '') }}</textarea>
                                    </div>

                                    <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-bold py-2.5 text-xs transition cursor-pointer">
                                        {{ isset($userReview) && $userReview ? 'Update Review' : 'Submit Review' }}
                                    </button>
                                </form>
                            @elseif(auth()->check())
                                <div class="text-center py-4">
                                    <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <h4 class="text-xs font-bold text-slate-900">Enrolled Students Only</h4>
                                    <p class="text-[11px] text-slate-500 mt-1 max-w-xs mx-auto">
                                        Enroll in this course to share your rating and review with the community.
                                    </p>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <p class="text-xs text-slate-600">
                                        <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:underline">Sign in</a> to leave a review if you are enrolled in this course.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Column: Approved Reviews List -->
                    <div class="lg:col-span-7 space-y-4">
                        @forelse($reviews as $reviewItem)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-slate-300">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <!-- User Avatar -->
                                        <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($reviewItem->user->name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-900">{{ $reviewItem->user->name ?? 'Enrolled Student' }}</span>
                                                <span class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.2 text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    &check; Verified
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5 text-amber-400">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="h-3.5 w-3.5 {{ $i <= $reviewItem->rating ? 'text-amber-400 fill-current' : 'text-slate-200' }}" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                @endfor
                                                <span class="text-[11px] font-semibold text-slate-500 ml-1">{{ $reviewItem->rating }}.0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[11px] text-slate-400 whitespace-nowrap">
                                        {{ $reviewItem->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="mt-3 text-xs text-slate-700 leading-relaxed">
                                    {{ $reviewItem->review }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-8 text-center text-slate-500">
                                <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                <p class="text-xs font-semibold text-slate-700">No student reviews yet</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Enrolled students can be the first to share their learning experience!</p>
                            </div>
                        @endforelse

                        @if(method_exists($reviews, 'hasPages') && $reviews->hasPages())
                            <div class="pt-4">
                                {{ $reviews->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- Course Trust & Guarantee Badges -->
        <section class="py-12 bg-indigo-900 text-white border-b border-indigo-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-indigo-800/80 border border-indigo-700 flex items-center justify-center shrink-0 text-amber-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">30-Day Money-Back Guarantee</h4>
                            <p class="text-xs text-indigo-200 mt-0.5">100% risk reversal if not satisfied.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-indigo-800/80 border border-indigo-700 flex items-center justify-center shrink-0 text-amber-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Lifetime Access &amp; Updates</h4>
                            <p class="text-xs text-indigo-200 mt-0.5">Continuous curriculum enhancements.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-indigo-800/80 border border-indigo-700 flex items-center justify-center shrink-0 text-amber-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Verified Certificate</h4>
                            <p class="text-xs text-indigo-200 mt-0.5">Shareable digital credential.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-indigo-800/80 border border-indigo-700 flex items-center justify-center shrink-0 text-amber-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Practical Resources</h4>
                            <p class="text-xs text-indigo-200 mt-0.5">Downloadable worksheets &amp; templates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Course Frequently Asked Questions -->
        <section class="py-16 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Common Questions</span>
                    <h2 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Course Frequently Asked Questions
                    </h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl border border-slate-200 bg-slate-50/50 p-5 open:bg-white open:shadow-xs transition" open>
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-slate-900 text-sm select-none">
                            <span>How long do I have access to this course after enrolling?</span>
                            <span class="ml-4 shrink-0 transition group-open:-rotate-180 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-2.5 text-xs text-slate-600 leading-relaxed">
                            You get lifetime access! Once enrolled, you can revisit any video lesson, review downloadable materials, and access future curriculum updates anytime at your convenience.
                        </p>
                    </details>

                    <details class="group rounded-xl border border-slate-200 bg-slate-50/50 p-5 open:bg-white open:shadow-xs transition">
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-slate-900 text-sm select-none">
                            <span>Can I learn at my own schedule?</span>
                            <span class="ml-4 shrink-0 transition group-open:-rotate-180 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-2.5 text-xs text-slate-600 leading-relaxed">
                            Yes, 100%. There are no fixed class timings. You can study on desktop or mobile whenever you have free time between running your business operations.
                        </p>
                    </details>

                    <details class="group rounded-xl border border-slate-200 bg-slate-50/50 p-5 open:bg-white open:shadow-xs transition">
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-slate-900 text-sm select-none">
                            <span>What if I get stuck or need help?</span>
                            <span class="ml-4 shrink-0 transition group-open:-rotate-180 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-2.5 text-xs text-slate-600 leading-relaxed">
                            You can reach our educational support team anytime via our contact inquiry form, and faculty members provide regular guidance for enrolled students.
                        </p>
                    </details>

                    <details class="group rounded-xl border border-slate-200 bg-slate-50/50 p-5 open:bg-white open:shadow-xs transition">
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-slate-900 text-sm select-none">
                            <span>What payment methods are supported?</span>
                            <span class="ml-4 shrink-0 transition group-open:-rotate-180 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-2.5 text-xs text-slate-600 leading-relaxed">
                            We support UPI, Credit Cards, Debit Cards, NetBanking, and Digital Wallets via secure Razorpay checkout.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Course Lead Inquiry / Pre-Enrollment Questions Form -->
        <section class="py-14 bg-slate-50 border-b border-slate-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="md:max-w-md">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Pre-Enrollment Inquiries</span>
                            <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                                Have questions before enrolling?
                            </h3>
                            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                                Not sure if this course fits your exact business model? Send a question directly to our course faculty and we'll reply within 1 business day.
                            </p>
                        </div>

                        <div class="flex-1 min-w-0 md:max-w-sm">
                            @if(session('lead_success'))
                                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-semibold text-emerald-800">
                                    {{ session('lead_success') }}
                                </div>
                            @else
                                <form action="{{ route('leads.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <!-- Honeypot -->
                                    <div style="display:none !important;" aria-hidden="true">
                                        <label for="website_course_inq">Website</label>
                                        <input type="text" name="website" id="website_course_inq" tabindex="-1" autocomplete="off">
                                    </div>
                                    <input type="hidden" name="course_id" value="{{ $course->id }}">
                                    <input type="hidden" name="source" value="course_landing_faq">
                                    <input type="hidden" name="subject" value="Pre-enrollment Question: {{ $course->title }}">

                                    <div>
                                        <input type="text"
                                               name="name"
                                               required
                                               value="{{ old('name') }}"
                                               placeholder="Your Name"
                                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600">
                                    </div>

                                    <div>
                                        <input type="email"
                                               name="email"
                                               required
                                               value="{{ old('email') }}"
                                               placeholder="Your Email Address"
                                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600">
                                    </div>

                                    <div>
                                        <textarea name="message"
                                                  rows="2"
                                                  required
                                                  placeholder="What would you like to know before enrolling?"
                                                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600">{{ old('message') }}</textarea>
                                    </div>

                                    <button type="submit"
                                            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer">
                                        Ask Faculty &rarr;
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Courses Section -->
        @if($relatedCourses->isNotEmpty())
            <section class="py-14 sm:py-16 bg-slate-50/60 border-b border-slate-200">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">More Learning Paths</span>
                            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">Related Courses</h2>
                        </div>
                        <a href="{{ route('courses') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700 transition">
                            View all catalog &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($relatedCourses as $related)
                            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs hover:shadow-md transition">
                                <div class="relative aspect-video bg-slate-100 overflow-hidden">
                                    @if($related->thumbnailUrl())
                                        <img src="{{ $related->thumbnailUrl() }}" alt="{{ $related->title }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="h-full w-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-800 flex items-center justify-center p-6 text-center text-white font-black text-2xl">
                                            MM
                                        </div>
                                    @endif

                                    <div class="absolute bottom-3 right-3">
                                        @if($related->is_free)
                                            <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-black text-white shadow-2xs">
                                                FREE
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-900/90 backdrop-blur-xs px-2.5 py-0.5 text-xs font-bold text-white shadow-2xs">
                                                ₹{{ number_format($related->effectivePrice(), 0) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    @if($related->category)
                                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-1">
                                            {{ $related->category->name }}
                                        </span>
                                    @endif

                                    <h3 class="text-base font-bold text-slate-900 leading-snug">
                                        <a href="{{ route('courses.show', $related) }}" class="hover:text-indigo-600 transition line-clamp-2">
                                            {{ $related->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-2 text-xs text-slate-600 line-clamp-2 leading-relaxed flex-1">
                                        {{ $related->short_description ?? Str::limit($related->description, 100) }}
                                    </p>

                                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="text-slate-400 text-[11px] truncate max-w-[140px]">
                                            By {{ $related->instructor_name ?? 'Faculty' }}
                                        </span>
                                        <a href="{{ route('courses.show', $related) }}" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-700 transition">
                                            View Course &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @else
        <!-- Static Course Hero & Preview Fallback (Used when database is empty / Phase 1 compatibility) -->
        <section class="py-16 sm:py-20 bg-gradient-to-b from-indigo-50/50 via-white to-white border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
                    <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">&larr; Back to Courses</a>
                    <span>/</span>
                    <span class="text-indigo-600">Digital Marketing for Business Owners</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <div class="lg:col-span-8">
                        <div class="flex flex-wrap items-center gap-2.5 mb-4">
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/15">
                                Comprehensive Program
                            </span>
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                Coming Soon
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            Digital Marketing for Business Owners
                        </h1>

                        <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed">
                            Learn how to grow your business online with limited time and budget. Gain complete marketing independence and master customer acquisition without relying on expensive monthly agency retainers.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-6 text-xs text-slate-500">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Format:</span>
                                <span>Self-Paced Video Lessons</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Structure:</span>
                                <span>6 Core Modules</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">Level:</span>
                                <span>Designed for Business Owners</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="text-center pb-6 border-b border-slate-100">
                                <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Program Status</span>
                                <div class="mt-2 text-2xl font-black text-indigo-600">Coming Soon</div>
                                <p class="mt-1 text-xs text-slate-500">Curriculum finalized &amp; currently in production</p>
                            </div>

                            <div class="py-6 space-y-3 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Actionable step-by-step video lessons</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>Fill-in marketing plan templates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 font-bold">&check;</span>
                                    <span>No technical background required</span>
                                </div>
                            </div>

                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition text-center">
                                Register Interest &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Static Curriculum Breakdown Fallback -->
        <section class="py-16 bg-slate-50/70 border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-12">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Curriculum Breakdown</span>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Course Modules Preview
                    </h2>
                    <p class="mt-3 text-sm text-slate-600">
                        Six structured modules covering the end-to-end journey of growing a business online:
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 01</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Understanding Digital Marketing</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Demystifying online channels, defining digital marketing without jargon, and mapping how online channels directly connect to business revenue.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 02</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Customer Understanding</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Building realistic customer personas, identifying buyer triggers, and knowing where customers look for answers.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 03</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Social Media Marketing</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Selecting the right platforms, converting profiles, and maintaining consistent publishing calendars.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 04</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Content Strategy</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Framing clear content pillars, avoiding burn-out, and producing content that solves prospect problems.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 05</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Creating Marketing Content</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Simple copywriting templates, hooks, calls to action, and batch workflow methods for busy founders.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Module 06</span>
                        <h3 class="text-base font-bold text-slate-900 mt-1 mb-1">Growing Your Business Online</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Measuring key acquisition channels, tracking conversion metrics, and compounding audience trust over time.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection