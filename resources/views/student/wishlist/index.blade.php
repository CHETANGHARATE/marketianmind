@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Wishlist
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Courses you have saved for later. Keep track of skills and strategies you plan to learn.
            </p>
        </div>
        <div>
            <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                Browse More Courses &rarr;
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Content Area -->
    @if ($wishlists->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-xs">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-500 mb-4 ring-8 ring-rose-50/50">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Your wishlist is empty</h3>
            <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500">
                Explore our catalog of practical marketing courses and save the ones that match your business growth goals.
            </p>
            <div class="mt-6">
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                    Browse Courses &rarr;
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($wishlists as $item)
                @php
                    $course = $item->course;
                    if (!$course) continue;
                    $isEnrolled = $user->isEnrolledIn($course);
                @endphp
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs hover:shadow-md transition">
                    <!-- Thumbnail -->
                    <div class="relative aspect-video bg-slate-100 overflow-hidden">
                        @if($course->thumbnailUrl())
                            <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-slate-100 flex items-center justify-center p-6 text-center">
                                <span class="text-xs font-semibold text-slate-400">{{ $course->title }}</span>
                            </div>
                        @endif

                        @if($isEnrolled)
                            <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white shadow-xs">
                                Enrolled ✓
                            </span>
                        @elseif($course->is_free)
                            <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-0.5 text-xs font-semibold text-white shadow-xs">
                                Free
                            </span>
                        @endif

                        <!-- Remove from wishlist button top-right -->
                        <form action="{{ route('student.wishlist.destroy', $course) }}" method="POST" class="absolute top-3 right-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Remove from Wishlist" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-xs text-rose-500 hover:text-rose-600 hover:bg-white shadow-xs transition cursor-pointer">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4.5 4.5 0 116.364 6.364L10 19.071l-7.536-7.535a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Card Body -->
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                            @if($course->category)
                                <span class="font-semibold text-indigo-600">{{ $course->category->name }}</span>
                                <span>&bull;</span>
                            @endif
                            <span>{{ $course->modules_count ?? $course->modules->count() }} {{ Str::plural('Module', $course->modules_count ?? $course->modules->count()) }}</span>
                            @if($course->estimated_duration)
                                <span>&bull;</span>
                                <span>{{ $course->estimated_duration }}</span>
                            @endif
                        </div>

                        <h3 class="text-base font-bold text-slate-900 line-clamp-2 mb-2">
                            <a href="{{ route('courses.show', $course->slug) }}" class="hover:text-indigo-600 transition">
                                {{ $course->title }}
                            </a>
                        </h3>

                        <p class="text-xs text-slate-500 line-clamp-2 mb-4 leading-relaxed">
                            {{ $course->short_description ?? Str::limit(strip_tags($course->description), 120) }}
                        </p>

                        <!-- Card Footer -->
                        <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                            <div>
                                @if($course->is_free)
                                    <span class="text-sm font-bold text-emerald-600">Free</span>
                                @else
                                    <span class="text-sm font-bold text-slate-900">₹{{ number_format($course->price, 0) }}</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if($isEnrolled)
                                    <a href="{{ route('student.courses.show', $course) }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500 transition shadow-2xs">
                                        Learn Now &rarr;
                                    </a>
                                @else
                                    <a href="{{ route('courses.show', $course->slug) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-500 transition shadow-2xs">
                                        View Details &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $wishlists->links() }}
        </div>
    @endif
</div>
@endsection