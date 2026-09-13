@extends('layouts.admin')

@section('content')
<div class="p-6 sm:p-8 max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <a href="{{ route('admin.articles.index') }}" class="text-slate-400 hover:text-white transition">&larr;</a>
                <span>Create New Article</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Draft or publish a new educational marketing guide with integrated SEO.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold">
            <p class="font-bold mb-1">Please correct the following errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.articles.store') }}" method="POST" class="space-y-6" id="article-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Title & Slug -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div>
                        <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Article Title *</label>
                        <input type="text"
                               name="title"
                               id="title"
                               value="{{ old('title') }}"
                               required
                               placeholder="e.g. 10 High-Converting Lead Generation Strategies for 2026"
                               class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 font-semibold">
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">URL Slug (leave blank to auto-generate)</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 bg-slate-800 border border-r-0 border-slate-700 rounded-l-xl text-xs text-slate-400">/blog/</span>
                            <input type="text"
                                   name="slug"
                                   id="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="high-converting-lead-generation-strategies"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-r-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                    </div>

                    <div>
                        <label for="excerpt" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Excerpt / Summary (optional)</label>
                        <textarea name="excerpt"
                                  id="excerpt"
                                  rows="2"
                                  placeholder="Short 1-2 sentence teaser for listing cards and meta tags..."
                                  class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">{{ old('excerpt') }}</textarea>
                    </div>
                </div>

                <!-- Rich Article Body -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="content" class="block text-xs font-bold uppercase tracking-wider text-slate-400">Article Content (HTML / Text) *</label>
                        <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Strict XSS Sanitized
                        </span>
                    </div>
                    <textarea name="content"
                              id="content"
                              rows="14"
                              required
                              placeholder="Write your article in formatted HTML or paragraphs (e.g. <h2>Subheading</h2><p>Article body...</p>)..."
                              class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 leading-relaxed">{{ old('content') }}</textarea>
                    <p class="text-[11px] text-slate-500">
                        Supports standard formatting tags: <code>&lt;h2&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;ol&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;blockquote&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;img&gt;</code>, <code>&lt;a&gt;</code>. Malicious scripts and event handlers are automatically neutralized.
                    </p>
                </div>

                <!-- SEO Meta Configuration Accordion -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-amber-500/10 text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <span>SEO Metadata &amp; Social Previews</span>
                        </h2>
                        <span class="text-[10px] text-slate-400">Custom SERP overrides</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="meta_title" class="block text-xs font-bold text-slate-400 mb-1">Custom Meta Title (optional)</label>
                            <input type="text"
                                   name="meta_title"
                                   id="meta_title"
                                   value="{{ old('meta_title') }}"
                                   placeholder="Defaults to Article Title"
                                   class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        </div>

                        <div>
                            <label for="meta_description" class="block text-xs font-bold text-slate-400 mb-1">Custom Meta Description (optional)</label>
                            <textarea name="meta_description"
                                      id="meta_description"
                                      rows="2"
                                      placeholder="Defaults to Article Excerpt (recommended: 140-160 characters)"
                                      class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">{{ old('meta_description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="canonical_url" class="block text-xs font-bold text-slate-400 mb-1">Canonical URL Override</label>
                                <input type="url"
                                       name="canonical_url"
                                       id="canonical_url"
                                       value="{{ old('canonical_url') }}"
                                       placeholder="Leave blank for automatic canonical"
                                       class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            </div>

                            <div>
                                <label for="robots" class="block text-xs font-bold text-slate-400 mb-1">Robots Directives</label>
                                <select name="robots"
                                        id="robots"
                                        class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                                    <option value="index, follow" {{ old('robots', 'index, follow') === 'index, follow' ? 'selected' : '' }}>index, follow (Default - Recommended)</option>
                                    <option value="noindex, follow" {{ old('robots') === 'noindex, follow' ? 'selected' : '' }}>noindex, follow</option>
                                    <option value="noindex, nofollow" {{ old('robots') === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                                </select>
                            </div>
                        </div>

                        <!-- Visual SEO Preview Box -->
                        <div class="pt-4 border-t border-slate-800">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Live Google SERP Snippet Preview</p>
                            <div class="p-4 bg-white rounded-xl border border-slate-300 text-slate-900 shadow-xs">
                                <div class="text-[11px] text-slate-600 flex items-center gap-1 mb-1">
                                    <span class="font-medium">marketianmind.com</span>
                                    <span>&rsaquo; blog &rsaquo;</span>
                                    <span id="preview-slug" class="text-slate-500">sample-slug</span>
                                </div>
                                <h3 id="preview-title" class="text-base text-blue-700 font-medium hover:underline cursor-pointer leading-snug line-clamp-1">
                                    Article Title Preview | Marketian Mind
                                </h3>
                                <p id="preview-desc" class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">
                                    Article meta description will preview here as you type. Keep it informative, clear, and compelling for organic search searchers.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Controls (1 col) -->
            <div class="space-y-6">
                <!-- Publishing Controls -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Publishing Settings</h2>

                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-300 mb-1.5">Status *</label>
                        <select name="status"
                                id="status"
                                required
                                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500 font-semibold">
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div>
                        <label for="published_at" class="block text-xs font-bold text-slate-300 mb-1.5">Publish Date</label>
                        <input type="datetime-local"
                               name="published_at"
                               id="published_at"
                               value="{{ old('published_at') }}"
                               class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Leave empty to use current time on publishing.</span>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-slate-800">
                        <input type="checkbox"
                               name="is_featured"
                               id="is_featured"
                               value="1"
                               {{ old('is_featured') ? 'checked' : '' }}
                               class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500">
                        <label for="is_featured" class="text-xs font-bold text-slate-300 cursor-pointer">
                            Mark as Featured Article
                        </label>
                    </div>

                    <div class="pt-2">
                        <label for="reading_time_minutes" class="block text-xs font-bold text-slate-300 mb-1.5">Reading Time (minutes)</label>
                        <input type="number"
                               name="reading_time_minutes"
                               id="reading_time_minutes"
                               min="1"
                               value="{{ old('reading_time_minutes') }}"
                               placeholder="Auto-calculated if left blank"
                               class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Taxonomy & Author -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Organization &amp; Author</h2>

                    <div>
                        <label for="author_id" class="block text-xs font-bold text-slate-300 mb-1.5">Author *</label>
                        <select name="author_id"
                                id="author_id"
                                required
                                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ old('author_id', auth()->id()) == $author->id ? 'selected' : '' }}>
                                    {{ $author->name }} ({{ ucfirst($author->role ?? 'admin') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="category_id" class="block text-xs font-bold text-slate-300 mb-1.5">Category</label>
                        <select name="category_id"
                                id="category_id"
                                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tags" class="block text-xs font-bold text-slate-300 mb-1.5">Tags (comma separated)</label>
                        <input type="text"
                               name="tags"
                               id="tags"
                               value="{{ old('tags') }}"
                               placeholder="seo, google ads, lead generation"
                               class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div>
                        <label for="featured_image" class="block text-xs font-bold text-slate-300 mb-1.5">Featured Image URL</label>
                        <input type="text"
                               name="featured_image"
                               id="featured_image"
                               value="{{ old('featured_image') }}"
                               placeholder="https://... or /images/..."
                               class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition shadow-sm">
                        Create Article
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const metaTitleInput = document.getElementById('meta_title');
    const excerptInput = document.getElementById('excerpt');
    const metaDescInput = document.getElementById('meta_description');

    const previewSlug = document.getElementById('preview-slug');
    const previewTitle = document.getElementById('preview-title');
    const previewDesc = document.getElementById('preview-desc');

    function updatePreview() {
        const title = metaTitleInput.value.trim() || titleInput.value.trim() || 'Article Title Preview';
        previewTitle.textContent = title + ' | Marketian Mind';

        const slug = slugInput.value.trim() || 'sample-slug';
        previewSlug.textContent = slug;

        const desc = metaDescInput.value.trim() || excerptInput.value.trim() || 'Article meta description will preview here as you type.';
        previewDesc.textContent = desc;
    }

    titleInput.addEventListener('input', function () {
        if (!slugInput.value) {
            previewSlug.textContent = titleInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }
        updatePreview();
    });

    slugInput.addEventListener('input', updatePreview);
    metaTitleInput.addEventListener('input', updatePreview);
    excerptInput.addEventListener('input', updatePreview);
    metaDescInput.addEventListener('input', updatePreview);

    updatePreview();
});
</script>
@endsection
