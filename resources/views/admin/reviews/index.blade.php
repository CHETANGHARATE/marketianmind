@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
                    Community & Quality
                </span>
                <span class="text-xs text-slate-500">Student Reviews &amp; Moderation</span>
            </div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Course Reviews &amp; Ratings
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Monitor student satisfaction, moderate submitted feedback, and manage review visibility.
            </p>
        </div>
    </div>

    <!-- Session Feedback -->
    @if(session('status'))
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-xs font-medium text-emerald-400 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Average Rating -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Platform Rating</span>
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['average_rating'], 1) }} <span class="text-sm font-semibold text-slate-400">/ 5.0</span></p>
                <p class="mt-1 text-[11px] text-slate-500">Across approved reviews</p>
            </div>
        </div>

        <!-- Total Reviews -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Reviews</span>
                <div class="rounded-lg bg-slate-800 p-2 text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['total']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">All submitted feedback</p>
            </div>
        </div>

        <!-- Approved Reviews -->
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Approved</span>
                <div class="rounded-lg bg-emerald-500/20 p-2 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['approved']) }}</p>
                <p class="mt-1 text-[11px] text-emerald-400/80">Publicly visible</p>
            </div>
        </div>

        <!-- Hidden Reviews -->
        <div class="rounded-2xl border border-amber-500/20 bg-amber-950/10 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Hidden / Moderated</span>
                <div class="rounded-lg bg-amber-500/20 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black tracking-tight text-white">{{ number_format($metrics['hidden']) }}</p>
                <p class="mt-1 text-[11px] text-amber-400/80">Restricted from public display</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
            <!-- Search -->
            <div class="sm:col-span-4">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by student or review text..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            </div>

            <!-- Course Filter -->
            <div class="sm:col-span-3">
                <select name="course_id"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Courses</option>
                    @foreach($courses as $courseItem)
                        <option value="{{ $courseItem->id }}" {{ request('course_id') == $courseItem->id ? 'selected' : '' }}>
                            {{ Str::limit($courseItem->title, 32) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Rating Filter -->
            <div class="sm:col-span-2">
                <select name="rating"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Ratings</option>
                    <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars ★★★★★</option>
                    <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars ★★★★☆</option>
                    <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars ★★★☆☆</option>
                    <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars ★★☆☆☆</option>
                    <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star ★☆☆☆☆</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-2">
                <select name="status"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-xs text-slate-300 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="sm:col-span-1 flex items-center gap-1.5">
                <button type="submit"
                        class="w-full rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-2.5 text-xs font-semibold transition text-center cursor-pointer">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Reviews Table Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-left text-xs">
                <thead class="bg-slate-950/80 text-slate-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Student</th>
                        <th scope="col" class="px-4 py-3.5">Course</th>
                        <th scope="col" class="px-4 py-3.5">Rating</th>
                        <th scope="col" class="px-5 py-3.5">Review Content</th>
                        <th scope="col" class="px-4 py-3.5">Status</th>
                        <th scope="col" class="px-4 py-3.5">Date</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-bold text-white">{{ $review->user?->name ?? 'Deleted User' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $review->user?->email }}</div>
                            </td>
                            <td class="px-4 py-4 max-w-[180px]">
                                <span class="font-semibold text-white block truncate" title="{{ $review->course?->title }}">
                                    {{ $review->course?->title ?? 'Deleted Course' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1 text-amber-400">
                                    <span class="font-black text-white text-xs mr-0.5">{{ $review->rating }}.0</span>
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="h-3.5 w-3.5 {{ $i <= $review->rating ? 'text-amber-400 fill-current' : 'text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                            </td>
                            <td class="px-5 py-4 max-w-xs sm:max-w-md">
                                <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ $review->review }}
                                </p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $review->isApproved() ? 'bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20' : 'bg-amber-500/10 text-amber-400 ring-1 ring-inset ring-amber-500/20' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $review->isApproved() ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                    {{ $review->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-[11px] text-slate-400">
                                {{ $review->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Toggle Visibility Form -->
                                    <form action="{{ route('admin.reviews.toggle', $review) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-lg px-2.5 py-1 text-xs font-semibold transition cursor-pointer {{ $review->isApproved() ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' }}">
                                            {{ $review->isApproved() ? 'Hide' : 'Approve' }}
                                        </button>
                                    </form>

                                    <!-- Delete Form -->
                                    <form action="{{ route('admin.reviews.destroy', $review) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to permanently delete this review?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <svg class="mx-auto h-8 w-8 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                <p class="text-sm font-semibold text-white">No course reviews found</p>
                                <p class="text-xs text-slate-500 mt-1">Submitted student reviews will appear here for moderation.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="border-t border-slate-800 p-4">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection