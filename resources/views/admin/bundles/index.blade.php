@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Course Bundles &amp; Packages
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Group multiple courses into purchasable bundles with special package pricing.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.bundles.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 focus:outline-hidden focus:ring-2 focus:ring-amber-500/50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Create New Bundle
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Bundles</p>
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
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Archived</p>
            <p class="mt-2 text-2xl font-bold text-slate-300">{{ $stats['archived'] }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" action="{{ route('admin.bundles.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <label for="search" class="sr-only">Search</label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="Search bundles by title or description..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white placeholder-slate-400 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
            </div>

            <div>
                <select name="status"
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\BundleStatus::cases() as $statusCase)
                        <option value="{{ $statusCase->value }}" {{ request('status') === $statusCase->value ? 'selected' : '' }}>
                            {{ $statusCase->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden">
        @if($bundles->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="text-base font-semibold text-white">No course bundles found</h3>
                <p class="mt-1 text-sm text-slate-400">Get started by creating your first package offer.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.bundles.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-400">
                        Create New Bundle
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-800/80 text-xs font-semibold uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th scope="col" class="px-6 py-4">Bundle</th>
                            <th scope="col" class="px-6 py-4">Courses Included</th>
                            <th scope="col" class="px-6 py-4">Package Price</th>
                            <th scope="col" class="px-6 py-4">Status</th>
                            <th scope="col" class="px-6 py-4">Orders</th>
                            <th scope="col" class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($bundles as $bundle)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($bundle->thumbnail_url)
                                            <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->title }}" class="h-10 w-14 rounded-md object-cover bg-slate-800 flex-shrink-0">
                                        @else
                                            <div class="h-10 w-14 rounded-md bg-slate-800 flex items-center justify-center text-slate-600 flex-shrink-0 text-xs font-bold">
                                                PKG
                                            </div>
                                        @endif
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-white">{{ $bundle->title }}</span>
                                                @if($bundle->featured)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                                        Featured
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-slate-400 font-mono">/bundles/{{ $bundle->slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ $bundle->courses_count }} Courses
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-bold">{{ $bundle->formattedPrice() }}</div>
                                    @if($bundle->savings() > 0)
                                        <div class="text-[11px] text-emerald-400">
                                            Save {{ $bundle->formattedSavings() }} ({{ $bundle->savingsPercentage() }}% off)
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $bundle->status->badgeColor() }}">
                                        {{ $bundle->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-semibold">
                                    {{ $bundle->orders_count }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($bundle->isPublished())
                                            <a href="{{ route('bundles.show', $bundle) }}" target="_blank" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" title="View Public Page">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.bundles.edit', $bundle) }}" class="p-1.5 text-slate-400 hover:text-amber-400 rounded-lg hover:bg-slate-800 transition" title="Edit Bundle">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.bundles.destroy', $bundle) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bundle?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition cursor-pointer" title="Delete Bundle">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($bundles->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $bundles->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
