@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Course Management
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Manage and organize all Marketian Mind courses, curriculum structures, and pricing.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 focus:outline-hidden focus:ring-2 focus:ring-amber-500/50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Create New Course
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Courses</p>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Published</p>
            <p class="mt-2 text-2xl font-bold text-emerald-300">{{ $stats['published'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Drafts</p>
            <p class="mt-2 text-2xl font-bold text-amber-300">{{ $stats['draft'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Featured</p>
            <p class="mt-2 text-2xl font-bold text-indigo-300">{{ $stats['featured'] }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" action="{{ route('admin.courses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Title -->
            <div class="lg:col-span-2">
                <label for="search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Search course title..."
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <select name="category_id"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons: Filter & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'category_id', 'type']))
                    <a href="{{ route('admin.courses.index') }}"
                       class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white border border-slate-800 transition"
                       title="Reset Filters">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Course List / Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
        @if($courses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Course</th>
                            <th scope="col" class="px-3 py-3.5">Category</th>
                            <th scope="col" class="px-3 py-3.5">Price</th>
                            <th scope="col" class="px-3 py-3.5">Type</th>
                            <th scope="col" class="px-3 py-3.5">Status</th>
                            <th scope="col" class="px-3 py-3.5">Featured</th>
                            <th scope="col" class="px-3 py-3.5">Curriculum</th>
                            <th scope="col" class="px-3 py-3.5">Created</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($courses as $course)
                            <tr class="hover:bg-slate-800/40 transition">
                                <!-- Course Title & Thumbnail -->
                                <td class="py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        @if($course->thumbnailUrl())
                                            <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}" class="h-12 w-16 object-cover rounded-lg border border-slate-800 shrink-0 bg-slate-950">
                                        @else
                                            <div class="h-12 w-16 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center text-slate-600 font-semibold text-xs shrink-0">
                                                MM
                                            </div>
                                        @endif
                                        <div class="min-w-0 max-w-xs">
                                            <a href="{{ route('admin.courses.edit', $course) }}" class="font-bold text-white hover:text-amber-400 transition truncate block">
                                                {{ $course->title }}
                                            </a>
                                            <p class="text-xs text-slate-400 font-mono truncate">
                                                /{{ $course->slug }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($course->category)
                                        <span class="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 font-medium text-slate-300 border border-slate-700">
                                            {{ $course->category->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 italic">Uncategorized</span>
                                    @endif
                                </td>

                                <!-- Price -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($course->is_free)
                                        <span class="font-bold text-emerald-400">Free</span>
                                    @else
                                        <div class="flex flex-col">
                                            <span class="font-bold text-white">
                                                ₹{{ number_format($course->effectivePrice(), 2) }}
                                            </span>
                                            @if($course->hasDiscount())
                                                <span class="text-[10px] text-slate-500 line-through">
                                                    ₹{{ number_format($course->price, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Type -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($course->is_free)
                                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                            Free
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-indigo-500/10 px-2 py-0.5 text-[11px] font-semibold text-indigo-400 border border-indigo-500/20">
                                            Paid
                                        </span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($course->status->value === 'published')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            Published
                                        </span>
                                    @elseif($course->status->value === 'draft')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-400 border border-amber-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Draft
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-700/50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-400 border border-slate-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            Archived
                                        </span>
                                    @endif
                                </td>

                                <!-- Featured -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs">
                                    @if($course->featured)
                                        <span class="inline-flex items-center gap-1 text-amber-400 font-semibold" title="Featured on website">
                                            <svg class="w-4 h-4 fill-amber-400" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            Featured
                                        </span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>

                                <!-- Curriculum counts -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs text-slate-400">
                                    <span>{{ $course->modules_count }} mod</span> &middot;
                                    <span>{{ $course->lessons_count }} less</span>
                                </td>

                                <!-- Created -->
                                <td class="px-3 py-4 whitespace-nowrap text-xs text-slate-400">
                                    {{ $course->created_at->format('M d, Y') }}
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.courses.edit', $course) }}"
                                           class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Are you sure you want to delete the course &quot;{{ $course->title }}&quot;? All associated modules and lessons will also be deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg bg-rose-500/10 px-2.5 py-1.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($courses->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    {{ $courses->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-16 px-4">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">No courses found</h3>
                <p class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">
                    @if(request()->hasAny(['search', 'status', 'category_id', 'type']))
                        No courses match your filter criteria. Try clearing the active filters.
                    @else
                        Get started by creating your first course on Marketian Mind.
                    @endif
                </p>
                <div class="mt-6">
                    @if(request()->hasAny(['search', 'status', 'category_id', 'type']))
                        <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                            Clear Filters
                        </a>
                    @else
                        <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400">
                            + Create First Course
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
