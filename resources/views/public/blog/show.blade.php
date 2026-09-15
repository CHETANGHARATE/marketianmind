@extends('layouts.public')

@section('subcontent')
<div class="bg-slate-900 text-white py-12 lg:py-16 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center text-xs text-slate-400 gap-2 mb-6" aria-label="Breadcrumb">
            <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-white transition">Blog</a>
            @if($article->category)
                <span>/</span>
                <a href="{{ route('blog.index', ['category' => $article->category->slug]) }}" class="hover:text-white transition">
                    {{ $article->category->name }}
                </a>
            @endif
            <span>/</span>
            <span class="text-amber-400 truncate max-w-[200px]">{{ $article->title }}</span>
        </nav>

        <div class="flex items-center gap-3 mb-4">
            @if($article->category)
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    {{ $article->category->name }}
                </span>
            @endif
            <span class="text-xs text-slate-400 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $article->reading_time_minutes }} min read
            </span>
        </div>

        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
            {{ $article->title }}
        </h1>

        @if($article->excerpt)
            <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                {{ $article->excerpt }}
            </p>
        @endif

        <div class="mt-8 pt-6 border-t border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-amber-500 text-slate-950 font-black flex items-center justify-center text-sm shadow-sm">
                    {{ strtoupper(substr($article->author?->name ?? 'MM', 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-white">{{ $article->author?->name ?? 'Marketian Mind Team' }}</p>
                    <p class="text-xs text-slate-400">
                        Published on {{ $article->published_at ? $article->published_at->format('F d, Y') : $article->created_at->format('F d, Y') }}
                    </p>
                </div>
            </div>

            <!-- Social Share Trigger Buttons -->
            @php
                $shareUrl = urlencode(route('blog.show', $article->slug));
                $shareTitle = urlencode($article->title);
            @endphp
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1 hidden sm:inline">Share:</span>
                <!-- WhatsApp -->
                <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="p-2 rounded-xl bg-slate-800 hover:bg-emerald-600 text-slate-300 hover:text-white transition"
                   title="Share on WhatsApp">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </a>
                <!-- Twitter / X -->
                <a href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&url={{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="p-2 rounded-xl bg-slate-800 hover:bg-sky-500 text-slate-300 hover:text-white transition"
                   title="Share on X">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="p-2 rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-300 hover:text-white transition"
                   title="Share on LinkedIn">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    <!-- Featured Image -->
    @if($article->featured_image)
        <div class="mb-10 rounded-2xl overflow-hidden shadow-sm border border-slate-200">
            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full max-h-[480px] object-cover">
        </div>
    @endif

    <!-- Main Sanitized Content -->
    <div class="prose prose-slate lg:prose-lg max-w-none text-slate-800 leading-relaxed font-normal">
        {!! $article->sanitized_content !!}
    </div>

    <!-- Tags -->
    @if($article->tags && $article->tags->count() > 0)
        <div class="mt-12 pt-6 border-t border-slate-200">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Topics &amp; Tags</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($article->tags as $tag)
                    <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                       class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-800 transition">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Author Box -->
    <div class="mt-12 bg-slate-50 border border-slate-200 rounded-2xl p-6 sm:p-8 flex items-start gap-4 sm:gap-6">
        <div class="w-14 h-14 rounded-full bg-slate-900 text-amber-400 font-bold flex-shrink-0 flex items-center justify-center text-lg shadow-sm">
            {{ strtoupper(substr($article->author?->name ?? 'M', 0, 2)) }}
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900">{{ $article->author?->name ?? 'Marketian Mind Faculty' }}</h3>
            <p class="text-xs text-amber-600 font-semibold mb-2">Platform Instructor &amp; Content Specialist</p>
            <p class="text-sm text-slate-600 leading-relaxed">
                Dedicated to decoding growth loops, performance marketing, and conversion architectures for ambitious Indian entrepreneurs and business owners.
            </p>
        </div>
    </div>

    <!-- Internal Course CTA Banner -->
    @if($recommendedCourses->count() > 0)
        <div class="mt-14 bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-3xl p-8 sm:p-10 border border-slate-800 shadow-lg">
            <div class="max-w-2xl">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 mb-3">
                    Fast-Track Your Marketing Skills
                </span>
                <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Master practical digital marketing with our certified courses
                </h3>
                <p class="mt-3 text-slate-300 text-sm sm:text-base leading-relaxed">
                    Gain structured access to self-paced video lessons, downloadable templates, and practical assignments designed to generate measurable business ROI.
                </p>
            </div>

            <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($recommendedCourses as $recCourse)
                    <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-4 flex flex-col justify-between hover:border-amber-500/50 transition">
                        <div>
                            @if($recCourse->category)
                                <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">
                                    {{ $recCourse->category->name }}
                                </span>
                            @endif
                            <h4 class="text-sm font-bold text-white mt-1 line-clamp-2">
                                {{ $recCourse->title }}
                            </h4>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-700/60 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-200">
                                {{ $recCourse->is_free ? 'Free' : '₹' . number_format($recCourse->discount_price ?? $recCourse->price, 0) }}
                            </span>
                            <a href="{{ route('courses.show', $recCourse->slug) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300">
                                View &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Related Articles -->
    @if($relatedArticles->count() > 0)
        <div class="mt-16 pt-12 border-t border-slate-200">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900">Related Articles</h3>
                <a href="{{ route('blog.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700">
                    View All Articles &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach($relatedArticles as $relArticle)
                    <a href="{{ route('blog.show', $relArticle->slug) }}" class="group block bg-white rounded-xl border border-slate-200 p-5 hover:border-slate-300 hover:shadow-xs transition">
                        @if($relArticle->category)
                            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">
                                {{ $relArticle->category->name }}
                            </span>
                        @endif
                        <h4 class="text-sm font-bold text-slate-900 mt-1 group-hover:text-amber-600 transition line-clamp-2">
                            {{ $relArticle->title }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                            {{ $relArticle->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($relArticle->content), 80) }}
                        </p>
                        <span class="inline-block text-[11px] font-semibold text-slate-400 mt-4">
                            {{ $relArticle->reading_time_minutes }} min read
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
