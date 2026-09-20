@extends('layouts.public')

@section('subcontent')
<div>
    <!-- Bundles Page Header -->
    <section class="py-14 sm:py-18 bg-gradient-to-b from-indigo-50/60 via-slate-50/40 to-white border-b border-slate-200">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-700/20 mb-4">
                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Course Packages &amp; Bundles
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                All-in-One Marketing Growth Bundles
            </h1>
            <p class="mt-2 text-xs font-bold uppercase tracking-wider text-indigo-600">
                Maximized Savings &bull; Complete Step-by-Step Curriculum
            </p>
            <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Accelerate your marketing execution. Unlock complete suites of practical courses together and save substantially compared to individual enrollments.
            </p>
        </div>
    </section>

    <!-- Bundles Listing Section -->
    <section class="py-12 bg-white min-h-screen">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Search & Count Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="GET" action="{{ route('bundles.index') }}" class="w-full sm:max-w-md">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Search bundles by keyword..."
                            class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 shadow-2xs transition"
                        />
                        @if($search !== '')
                            <a href="{{ route('bundles.index') }}" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                &times;
                            </a>
                        @endif
                    </div>
                </form>

                <div class="text-xs font-medium text-slate-500">
                    Showing <span class="font-bold text-slate-800">{{ $bundles->total() }}</span> {{ Str::plural('bundle', $bundles->total()) }}
                </div>
            </div>

            @if($bundles->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 p-12 text-center">
                    <div class="mx-auto h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No Bundles Found</h3>
                    <p class="mt-1 text-sm text-slate-500">No course packages match your current search criteria.</p>
                    @if($search !== '')
                        <div class="mt-4">
                            <a href="{{ route('bundles.index') }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                Clear Search Filter &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($bundles as $bundle)
                        <div class="group flex flex-col rounded-2xl border border-slate-200/90 bg-white shadow-xs hover:shadow-md transition duration-200 overflow-hidden">
                            <!-- Thumbnail / Header Banner -->
                            <div class="relative aspect-video bg-gradient-to-br from-slate-900 to-indigo-950 overflow-hidden">
                                @if($bundle->thumbnail_url)
                                    <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->title }}" loading="lazy" decoding="async" width="640" height="360" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center">
                                        <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center text-white mb-2">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Curated Suite</span>
                                    </div>
                                @endif

                                <!-- Badges Overlay -->
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    @if($bundle->featured)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-xs uppercase tracking-wider">
                                            Featured Bundle
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-900/80 backdrop-blur-xs text-white uppercase tracking-wider">
                                        {{ $bundle->publishedCourses->count() }} Courses
                                    </span>
                                </div>

                                @if($bundle->savings() > 0)
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-600 text-white shadow-xs uppercase tracking-wider">
                                            Save {{ $bundle->savingsPercentage() }}%
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Bundle Content -->
                            <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                                <div>
                                    <h2 class="text-lg font-black tracking-tight text-slate-900 group-hover:text-indigo-600 transition">
                                        <a href="{{ route('bundles.show', $bundle) }}">
                                            {{ $bundle->title }}
                                        </a>
                                    </h2>

                                    @if($bundle->short_description)
                                        <p class="mt-2 text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                            {{ $bundle->short_description }}
                                        </p>
                                    @endif

                                    <!-- Included Courses Mini-List -->
                                    <div class="mt-4 pt-4 border-t border-slate-100">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                                            Included in this bundle:
                                        </span>
                                        <ul class="space-y-1.5 text-xs text-slate-600">
                                            @foreach($bundle->publishedCourses->take(3) as $bCourse)
                                                <li class="flex items-center gap-2 truncate">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600 flex-shrink-0"></span>
                                                    <span class="truncate">{{ $bCourse->title }}</span>
                                                </li>
                                            @endforeach
                                            @if($bundle->publishedCourses->count() > 3)
                                                <li class="text-[11px] font-semibold text-indigo-600 pl-3.5">
                                                    + {{ $bundle->publishedCourses->count() - 3 }} more courses
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                                <!-- Pricing & CTA Section -->
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex items-end justify-between mb-4">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Package Price</span>
                                            <div class="flex items-baseline gap-2">
                                                @if($bundle->hasActiveOffer())
                                                    <span class="text-2xl font-black text-slate-900">{{ $bundle->formattedFinalPrice() }}</span>
                                                    <span class="text-xs text-slate-400 line-through">{{ $bundle->formattedPrice() }}</span>
                                                @else
                                                    <span class="text-2xl font-black text-slate-900">{{ $bundle->formattedPrice() }}</span>
                                                    @if($bundle->savings() > 0)
                                                        <span class="text-xs text-slate-400 line-through">{{ $bundle->formattedIndividualCoursesTotal() }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                            @if($bundle->hasActiveOffer())
                                                <span class="text-[10px] font-bold text-amber-600 block mt-0.5">
                                                    {{ $bundle->currentOffer()->displayBadge() }} Active
                                                </span>
                                            @endif
                                        </div>
                                        @if($bundle->savings() > 0)
                                            <div class="text-right">
                                                <span class="text-[11px] font-bold text-emerald-600">
                                                    Save {{ $bundle->formattedSavings() }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    @auth
                                        @if($bundle->hasUserAccess(auth()->user()))
                                            <div class="w-full flex items-center justify-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200 py-2.5 px-4 text-xs font-bold text-emerald-800">
                                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>You Own All Included Courses</span>
                                            </div>
                                        @else
                                            <a href="{{ route('bundles.show', $bundle) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 py-2.5 px-4 text-xs font-bold text-white hover:bg-slate-800 transition shadow-xs">
                                                View Bundle Details &rarr;
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('bundles.show', $bundle) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 py-2.5 px-4 text-xs font-bold text-white hover:bg-slate-800 transition shadow-xs">
                                            View Bundle Details &rarr;
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="pt-8">
                    {{ $bundles->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
