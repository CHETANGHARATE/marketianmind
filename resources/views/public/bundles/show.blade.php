@extends('layouts.public')

@section('subcontent')
<div class="min-h-screen bg-slate-50 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Breadcrumb & Back Link -->
        <div class="flex items-center justify-between">
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
                <span>/</span>
                <a href="{{ route('bundles.index') }}" class="hover:text-indigo-600 transition">Bundles</a>
                <span>/</span>
                <span class="text-slate-800 font-bold truncate max-w-xs sm:max-w-md">{{ $bundle->title }}</span>
            </nav>
            <a href="{{ route('bundles.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                &larr; All Bundles
            </a>
        </div>

        <!-- Session Status & Alerts -->
        @if(session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Bundle Overview & Courses Included -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Header Banner -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if($bundle->featured)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500 text-white uppercase tracking-wider">
                                Featured Bundle
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/15 uppercase tracking-wider">
                            {{ $totalCoursesCount }} Courses Included
                        </span>
                        @if($bundle->savings() > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                                Save {{ $bundle->savingsPercentage() }}%
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900 leading-tight">
                        {{ $bundle->title }}
                    </h1>

                    @if($bundle->short_description)
                        <p class="mt-4 text-base text-slate-600 leading-relaxed">
                            {{ $bundle->short_description }}
                        </p>
                    @endif
                </div>

                @if($bundle->description)
                    <!-- Detailed Overview -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>About this Package</span>
                        </h2>
                        <div class="prose prose-sm prose-slate max-w-none text-slate-600 leading-relaxed">
                            {!! nl2br(e($bundle->description)) !!}
                        </div>
                    </div>
                @endif

                <!-- Included Courses Section -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Included Courses Curriculum
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                You receive immediate, full access to all {{ $totalCoursesCount }} programs below:
                            </p>
                        </div>
                        <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-full">
                            {{ $totalCoursesCount }} Programs
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($bundle->publishedCourses as $index => $bCourse)
                            @php
                                $userEnrolled = auth()->check() && auth()->user()->isEnrolledIn($bCourse);
                            @endphp
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 rounded-xl border {{ $userEnrolled ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' }} hover:border-indigo-200 transition">
                                <div class="flex items-start gap-3.5">
                                    <span class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-lg {{ $userEnrolled ? 'bg-emerald-600 text-white' : 'bg-slate-900 text-white' }} text-xs font-bold">
                                        @if($userEnrolled)
                                            &check;
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-bold text-slate-900 hover:text-indigo-600 transition">
                                                <a href="{{ route('courses.show', $bCourse) }}" target="_blank">
                                                    {{ $bCourse->title }} &nearr;
                                                </a>
                                            </h3>
                                            @if($userEnrolled)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    Already Enrolled
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                            @if($bCourse->category)
                                                <span>{{ $bCourse->category->name }}</span>
                                                <span>&bull;</span>
                                            @endif
                                            <span>Instructor: {{ $bCourse->instructorDisplayName() }}</span>
                                            @if($bCourse->lessons->count() > 0)
                                                <span>&bull;</span>
                                                <span>{{ $bCourse->lessons->count() }} Lessons</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ $bCourse->formattedPrice() }}
                                    </span>
                                    <a href="{{ route('courses.show', $bCourse) }}" target="_blank" class="text-[11px] font-semibold text-indigo-600 hover:underline">
                                        Course Details &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Pricing & Enrollment Card -->
            <div class="lg:col-span-4 space-y-6 sticky top-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                    @if($bundle->thumbnail_url)
                        <div class="aspect-video rounded-xl overflow-hidden mb-6 bg-slate-100">
                            <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->title }}" class="h-full w-full object-cover">
                        </div>
                    @endif

                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Bundle Enrollment
                    </h2>

                    <!-- Price breakdown -->
                    <div class="py-4 space-y-3 text-sm border-b border-slate-100">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Individual Courses Value</span>
                            <span class="font-semibold text-slate-800">{{ $bundle->formattedIndividualCoursesTotal() }}</span>
                        </div>

                        @if($bundle->savings() > 0)
                            <div class="flex items-center justify-between text-emerald-600 font-medium">
                                <span>Package Savings ({{ $bundle->savingsPercentage() }}% off)</span>
                                <span>- {{ $bundle->formattedSavings() }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Platform Access Fee</span>
                            <span class="text-emerald-600 font-semibold">Free</span>
                        </div>
                    </div>

                    @php
                        $hasBundleOffer = $bundle->hasActiveOffer();
                        $bundleOffer = $bundle->currentOffer();
                    @endphp

                    <div class="py-4 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase text-slate-400 block">Package Tuition</span>
                            @if($hasBundleOffer)
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-slate-900">{{ $bundle->formattedFinalPrice() }}</span>
                                    <span class="text-xs font-semibold text-slate-400 line-through">{{ $bundle->formattedPrice() }}</span>
                                </div>
                                <span class="text-[11px] font-bold text-amber-600 block mt-0.5">
                                    {{ $bundleOffer->displayBadge() }} Active
                                </span>
                            @else
                                <span class="text-2xl font-black text-slate-900">{{ $bundle->formattedPrice() }}</span>
                            @endif
                        </div>
                        @if($bundle->savings() > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                                Save {{ $bundle->savingsPercentage() }}%
                            </span>
                        @endif
                    </div>

                    <!-- Partial Ownership Notice -->
                    @auth
                        @if($ownedCount > 0 && ! $isFullyEnrolled)
                            <div class="p-3 mb-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-start gap-2">
                                <svg class="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    You already own <strong>{{ $ownedCount }} of {{ $totalCoursesCount }}</strong> courses in this bundle. Purchasing this bundle will unlock the remaining <strong>{{ $totalCoursesCount - $ownedCount }}</strong> courses.
                                </span>
                            </div>
                        @endif
                    @endauth

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-3">
                        @auth
                            @if($isFullyEnrolled)
                                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-center mb-3">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-800">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>You Own All Courses in this Bundle</span>
                                    </div>
                                    <p class="text-[11px] text-emerald-600 mt-1">Full course bundle access is active on your account.</p>
                                </div>
                                <a href="{{ route('student.my-learning') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800 transition">
                                    Go to My Learning &rarr;
                                </a>
                            @else
                                <form action="{{ route('student.bundles.purchase', $bundle) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                                        {{ $ctaVariant?->getConfigValue('button_text') ?? 'Enroll in Bundle Now • ' . $bundle->formattedFinalPrice() . ' →' }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login', ['redirect' => route('bundles.show', $bundle)]) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                                {{ $ctaVariant?->getConfigValue('button_text') ?? 'Sign In to Enroll • ' . $bundle->formattedFinalPrice() . ' →' }}
                            </a>
                        @endauth

                        @if($ctaVariant && $ctaVariant->getConfigValue('supporting_message'))
                            <p class="text-center text-xs font-semibold text-amber-600">
                                {{ $ctaVariant->getConfigValue('supporting_message') }}
                            </p>
                        @endif

                        <div class="text-center text-[11px] text-slate-400">
                            Instant access &bull; Course updates &bull; 100% Secure Checkout
                        </div>
                    </div>

                    <!-- Package Guarantees -->
                    <div class="mt-6 pt-6 border-t border-slate-100 space-y-2.5 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold">&check;</span>
                            <span>Full access to all {{ $totalCoursesCount }} included courses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold">&check;</span>
                            <span>Course completion certificates for every program</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold">&check;</span>
                            <span>Practical marketing templates, guides &amp; resources</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold">&check;</span>
                            <span>Encrypted payment via Razorpay (UPI, Cards, Netbanking)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
