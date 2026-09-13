@extends('layouts.admin')

@section('content')
<div class="p-6 sm:p-8 max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <a href="{{ route('admin.articles.index') }}" class="text-slate-400 hover:text-white transition">&larr;</a>
                <span>Blog &amp; Article Categories</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Organize educational content, topic clusters, and organic discovery hubs.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- New Category Form (1 col) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 h-fit space-y-4">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Category</span>
            </h2>

            <form action="{{ route('admin.article-categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Category Name *</label>
                    <input type="text"
                           name="name"
                           id="name"
                           required
                           placeholder="e.g. Lead Generation"
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Slug (optional)</label>
                    <input type="text"
                           name="slug"
                           id="slug"
                           placeholder="lead-generation"
                           class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Description</label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Brief description of articles in this topic..."
                              class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox"
                           name="is_active"
                           id="is_active"
                           value="1"
                           checked
                           class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500">
                    <label for="is_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                        Active &amp; Visible
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition shadow-sm">
                    Save Category
                </button>
            </form>
        </div>

        <!-- Categories List (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                            <tr>
                                <th class="px-5 py-3 font-bold">Category</th>
                                <th class="px-5 py-3 font-bold">Slug</th>
                                <th class="px-5 py-3 font-bold">Articles</th>
                                <th class="px-5 py-3 font-bold">Status</th>
                                <th class="px-5 py-3 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($categories as $category)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-slate-100">{{ $category->name }}</p>
                                        @if($category->description)
                                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $category->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-[11px] text-slate-400">
                                        {{ $category->slug }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ $category->articles_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if($category->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/20">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <form action="{{ route('admin.article-categories.destroy', $category) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this category? Associated articles will be set to uncategorized.');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-semibold transition">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                        No categories yet. Add your first topic category on the left.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($categories->hasPages())
                    <div class="p-4 border-t border-slate-800">
                        {{ $categories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
