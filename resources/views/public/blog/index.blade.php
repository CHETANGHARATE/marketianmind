@extends('layouts.public')

@section('subcontent')
<div class="bg-slate-900 text-white py-14 lg:py-20 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 mb-4">
                Educational Marketing Hub
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
                Actionable Marketing <span class="text-amber-400">Playbooks &amp; Insights</span>
            </h1>
            <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed">
                Step-by-step digital marketing tutorials, performance advertising tactics, and lead generation frameworks engineered for startup founders and business owners.
            </p>

            <!-- Search Form -->
            <form action="{{ route('blog.index') }}" method="GET" class="mt-8 flex flex-col sm:flex-row gap-3">
                @if($categorySlug)
                    <input type="hidden" name="category" value="{{ $categorySlug }}">
                @endif
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Search articles, tactics, SEO, ads..."
                           class="w-full pl-10 pr-4 py-3 bg-slate-800/80 border border-slate-700 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                </div>
                <button type="submit"
                        class="px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm transition shadow-sm">
                    Search Articles
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    <!-- Category & Tag Filters -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-8 border-b border-slate-200">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('blog.index') }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ !$categorySlug && !$tagSlug ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                All Topics
            </a>
            @foreach($categories as $category)
                <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $categorySlug === $category->slug ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $category->name }}
                    <span class="ml-1 opacity-70 text-[10px]">({{ $category->articles_count }})</span>
                </a>
            @endforeach
        </div>

        @if($search || $categorySlug || $tagSlug)
            <a href="{{ route('blog.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Clear Filters
            </a>
        @endif
    </div>

    <!-- Featured Article Banner (if present) -->
    @if($featuredArticle)
        <div class="mt-8 mb-12 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-7 p-6 sm:p-8 lg:p-10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20">
                            ★ Featured Story
                        </span>
                        @if($featuredArticle->category)
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ $featuredArticle->category->name }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                        <a href="{{ route('blog.show', $featuredArticle->slug) }}" class="hover:text-amber-600 transition">
                            {{ $featuredArticle->title }}
                        </a>
                    </h2>
                    <p class="mt-3 text-slate-600 text-sm sm:text-base leading-relaxed line-clamp-3">
                        {{ $featuredArticle->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($featuredArticle->content), 180) }}
                    </p>
                    <div class="mt-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs">
                                {{ strtoupper(substr($featuredArticle->author?->name ?? 'MM', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">{{ $featuredArticle->author?->name ?? 'Editorial Team' }}</p>
                                <p class="text-[11px] text-slate-500">
                                    {{ $featuredArticle->published_at ? $featuredArticle->published_at->format('M d, Y') : $featuredArticle->created_at->format('M d, Y') }} &bull; {{ $featuredArticle->reading_time_minutes }} min read
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('blog.show', $featuredArticle->slug) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition">
                            Read Article &rarr;
                        </a>
                    </div>
                </div>
                <div class="lg:col-span-5 h-64 lg:h-full min-h-[260px] bg-slate-100 flex items-center justify-center overflow-hidden">
                    @if($featuredArticle->featured_image)
                        <img src="{{ $featuredArticle->featured_image }}" alt="{{ $featuredArticle->title }}" fetchpriority="high" decoding="async" width="640" height="360" class="w-full h-full object-cover">
                    @else
                        <div class="p-8 text-center text-slate-400">
                            <svg class="w-16 h-16 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                            <span class="text-xs font-semibold uppercase tracking-wider">Marketian Mind Article</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Articles Grid -->
    @if($articles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
                <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col hover:border-slate-300 hover:shadow-md transition">
                    <a href="{{ route('blog.show', $article->slug) }}" class="block aspect-video bg-slate-100 overflow-hidden relative">
                        @if($article->featured_image)
                            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" loading="lazy" decoding="async" width="640" height="360" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-slate-900/5 text-slate-400">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        @endif

                        @if($article->category)
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white/95 text-slate-800 shadow-xs backdrop-blur-xs">
                                {{ $article->category->name }}
                            </span>
                        @endif
                    </a>

                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                            <span>{{ $article->published_at ? $article->published_at->format('M d, Y') : $article->created_at->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span>{{ $article->reading_time_minutes }} min read</span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 leading-snug line-clamp-2 hover:text-amber-600 transition">
                            <a href="{{ route('blog.show', $article->slug) }}">
                                {{ $article->title }}
                            </a>
                        </h3>

                        <p class="mt-2 text-slate-600 text-sm leading-relaxed line-clamp-3 flex-1">
                            {{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}
                        </p>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px]">
                                    {{ strtoupper(substr($article->author?->name ?? 'M', 0, 1)) }}
                                </div>
                                <span class="text-xs font-medium text-slate-700 truncate max-w-[120px]">
                                    {{ $article->author?->name ?? 'Editorial' }}
                                </span>
                            </div>

                            <a href="{{ route('blog.show', $article->slug) }}"
                               class="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                                Read more &rarr;
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $articles->links() }}
        </div>
    @else
        <div class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8">
            <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-bold text-slate-900">No articles found</h3>
            <p class="mt-1 text-sm text-slate-500">We couldn't find any articles matching your search or filter criteria.</p>
            <a href="{{ route('blog.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition">
                Reset Filters
            </a>
        </div>
    @endif
</div>
@endsection
