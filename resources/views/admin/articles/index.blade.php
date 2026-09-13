@extends('layouts.admin')

@section('content')
<div class="p-6 sm:p-8 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <span>Educational Articles &amp; Blog CMS</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Manage educational articles, SEO metadata, tags, and organic acquisition funnels.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.article-categories.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition">
                Manage Categories
            </a>
            <a href="{{ route('admin.articles.create') }}"
               class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Article
            </a>
        </div>
    </div>

    <!-- Alert notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filter Tabs & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.articles.index') }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ empty($status) ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white bg-slate-800/60' }}">
                All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.articles.index', ['status' => 'published']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $status === 'published' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white bg-slate-800/60' }}">
                Published ({{ $counts['published'] }})
            </a>
            <a href="{{ route('admin.articles.index', ['status' => 'draft']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $status === 'draft' ? 'bg-slate-700 text-white font-bold' : 'text-slate-400 hover:text-white bg-slate-800/60' }}">
                Drafts ({{ $counts['draft'] }})
            </a>
            <a href="{{ route('admin.articles.index', ['status' => 'archived']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $status === 'archived' ? 'bg-rose-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white bg-slate-800/60' }}">
                Archived ({{ $counts['archived'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.articles.index') }}" method="GET" class="flex items-center gap-2">
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search title, excerpt, author..."
                       class="pl-9 pr-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500 w-64">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            @if($search)
                <a href="{{ route('admin.articles.index', array_filter(['status' => $status])) }}" class="text-xs text-rose-400 hover:text-rose-300">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5 font-bold">Article</th>
                        <th class="px-5 py-3.5 font-bold">Category</th>
                        <th class="px-5 py-3.5 font-bold">Author</th>
                        <th class="px-5 py-3.5 font-bold">Status</th>
                        <th class="px-5 py-3.5 font-bold">Published</th>
                        <th class="px-5 py-3.5 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($articles as $article)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if($article->featured_image)
                                        <img src="{{ $article->featured_image }}" alt="" class="w-10 h-10 rounded-lg object-cover bg-slate-800 border border-slate-700">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-slate-500 border border-slate-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold text-slate-100 hover:text-amber-400 transition line-clamp-1">
                                            {{ $article->title }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500">
                                            <span>/blog/{{ $article->slug }}</span>
                                            @if($article->is_featured)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
                                                    ★ Featured
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($article->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $article->category->name }}
                                    </span>
                                @else
                                    <span class="text-slate-500 italic">Uncategorized</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-300 font-medium">
                                {{ $article->author?->name ?? 'Unknown' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $article->status->badgeClasses() }}">
                                    {{ $article->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-400">
                                {{ $article->published_at ? $article->published_at->format('M d, Y H:i') : '—' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('blog.show', $article->slug) }}"
                                       target="_blank"
                                       class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold transition"
                                       title="View live or preview">
                                        Preview
                                    </a>
                                    <a href="{{ route('admin.articles.edit', $article) }}"
                                       class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-[11px] font-semibold transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.articles.destroy', $article) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this article?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-semibold transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                <p class="text-sm font-semibold">No articles found.</p>
                                <p class="text-xs text-slate-600 mt-1">Start writing your first high-converting marketing guide!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
