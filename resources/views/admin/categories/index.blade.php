@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Course Categories
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Organize courses into distinct learning categories for student browsing.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create Category Form (Left Column) -->
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 sticky top-24">
                <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3 mb-4">
                    Add New Category
                </h2>

                <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Category Name <span class="text-amber-400">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               required
                               placeholder="e.g. Social Media Marketing"
                               class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Custom Slug -->
                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Slug <span class="text-slate-500 lowercase font-normal">(optional)</span>
                        </label>
                        <input type="text"
                               name="slug"
                               id="slug"
                               value="{{ old('slug') }}"
                               placeholder="e.g. social-media-marketing"
                               class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 font-mono">
                        @error('slug')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Description
                        </label>
                        <textarea name="description"
                                  id="description"
                                  rows="3"
                                  placeholder="Brief description of this marketing category..."
                                  class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Status <span class="text-amber-400">*</span>
                        </label>
                        <select name="status"
                                id="status"
                                required
                                class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 focus:outline-hidden transition">
                        Save Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories Table (Right Column) -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search categories by name or description..."
                               class="w-full rounded-lg border border-slate-700 bg-slate-950 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.categories.index') }}" class="text-xs text-slate-400 hover:text-white">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Categories List -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
                @if($categories->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-800 bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    <th class="py-3.5 pl-4 pr-3 sm:pl-6">Name & Slug</th>
                                    <th class="px-3 py-3.5">Courses</th>
                                    <th class="px-3 py-3.5">Status</th>
                                    <th class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($categories as $category)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="py-4 pl-4 pr-3 sm:pl-6">
                                            <div class="font-bold text-white">
                                                {{ $category->name }}
                                            </div>
                                            <div class="text-xs text-slate-400 font-mono mt-0.5">
                                                {{ $category->slug }}
                                            </div>
                                            @if($category->description)
                                                <p class="text-xs text-slate-400 mt-1 max-w-sm line-clamp-1">
                                                    {{ $category->description }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-xs">
                                            <span class="inline-flex items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-300 border border-slate-700">
                                                {{ $category->courses_count }} {{ Str::plural('Course', $category->courses_count) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-xs">
                                            @if($category->status->value === 'active')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-700/40 px-2.5 py-0.5 text-[11px] font-semibold text-slate-400 border border-slate-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 pl-3 pr-4 sm:pr-6 whitespace-nowrap text-right text-xs">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button"
                                                        onclick="openEditCategoryModal({{ json_encode($category) }})"
                                                        class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700 transition">
                                                    Edit
                                                </button>

                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Are you sure you want to delete category &quot;{{ $category->name }}&quot;? Courses in this category will become uncategorized.');">
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

                    @if($categories->hasPages())
                        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                            {{ $categories->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 px-4">
                        <p class="text-sm text-slate-400">No categories found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="edit-category-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" onclick="closeEditCategoryModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl z-10 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white">Edit Category</h3>
                <button type="button" onclick="closeEditCategoryModal()" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="edit-category-form" method="POST" action="" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Category Name <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="edit_name"
                           required
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="edit_slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Slug <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="slug"
                           id="edit_slug"
                           required
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 font-mono">
                </div>

                <div>
                    <label for="edit_description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Description
                    </label>
                    <textarea name="description"
                              id="edit_description"
                              rows="3"
                              class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500"></textarea>
                </div>

                <div>
                    <label for="edit_status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Status <span class="text-amber-400">*</span>
                    </label>
                    <select name="status"
                            id="edit_status"
                            required
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            onclick="closeEditCategoryModal()"
                            class="rounded-lg border border-slate-700 bg-slate-950 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditCategoryModal(category) {
        const modal = document.getElementById('edit-category-modal');
        const form = document.getElementById('edit-category-form');
        const nameInput = document.getElementById('edit_name');
        const slugInput = document.getElementById('edit_slug');
        const descInput = document.getElementById('edit_description');
        const statusSelect = document.getElementById('edit_status');

        form.action = '/admin/course-categories/' + category.id;
        nameInput.value = category.name || '';
        slugInput.value = category.slug || '';
        descInput.value = category.description || '';
        statusSelect.value = (typeof category.status === 'object' && category.status !== null)
            ? category.status.value
            : category.status;

        modal.classList.remove('hidden');
    }

    function closeEditCategoryModal() {
        const modal = document.getElementById('edit-category-modal');
        modal.classList.add('hidden');
    }
</script>
@endsection
