<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\SlugRedirect;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles with search and status filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Article::with(['category', 'author'])
            ->latest('id');

        if ($status && in_array($status, ['draft', 'published', 'archived'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhereHas('author', fn($aq) => $aq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $articles = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => Article::count(),
            'published' => Article::where('status', ArticleStatus::PUBLISHED->value)->count(),
            'draft' => Article::where('status', ArticleStatus::DRAFT->value)->count(),
            'archived' => Article::where('status', ArticleStatus::ARCHIVED->value)->count(),
        ];

        return view('admin.articles.index', compact('articles', 'counts', 'status', 'search'));
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): View
    {
        $categories = ArticleCategory::active()->orderBy('name')->get();
        $authors = User::whereIn('role', ['admin', 'instructor'])
            ->orWhere('id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('admin.articles.create', compact('categories', 'authors'));
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'author_id' => 'required|exists:users,id',
            'category_id' => 'nullable|exists:article_categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'reading_time_minutes' => 'nullable|integer|min:1',
            'tags' => 'nullable|string|max:500',
            // SEO Meta Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'canonical_url' => 'nullable|string|max:500',
            'robots' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:1000',
            'og_image' => 'nullable|string|max:500',
            'twitter_card' => 'nullable|string|max:50',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $uniqueSlug = $this->generateUniqueSlug($slug);

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === ArticleStatus::PUBLISHED->value && empty($publishedAt)) {
            $publishedAt = now();
        }

        $article = Article::create([
            'title' => $validated['title'],
            'slug' => $uniqueSlug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'author_id' => $validated['author_id'],
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'reading_time_minutes' => $validated['reading_time_minutes'] ?? 0,
        ]);

        // Sync Tags
        $this->syncTags($article, $validated['tags'] ?? '');

        // Save SEO Meta
        $article->seo()->create([
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
            'robots' => $validated['robots'] ?? 'index, follow',
            'og_title' => $validated['og_title'] ?? null,
            'og_description' => $validated['og_description'] ?? null,
            'og_image' => $validated['og_image'] ?? null,
            'twitter_card' => $validated['twitter_card'] ?? 'summary_large_image',
        ]);

        // Audit Logging
        AuditLogger::log(
            action: 'created',
            auditable: $article,
            description: "Created article '{$article->title}' (status: {$article->status->value})",
            oldValues: null,
            newValues: $article->only(['title', 'slug', 'status', 'category_id', 'author_id']),
            resourceLabel: $article->title
        );

        Cache::forget('sitemap_xml');

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "Article '{$article->title}' created successfully.");
    }

    /**
     * Show the form for editing an article with visual SEO preview.
     */
    public function edit(Article $article): View
    {
        $article->load(['category', 'author', 'tags', 'seo']);
        $categories = ArticleCategory::all();
        $authors = User::whereIn('role', ['admin', 'instructor'])
            ->orWhere('id', auth()->id())
            ->orderBy('name')
            ->get();

        $tagsString = $article->tags->pluck('name')->implode(', ');

        return view('admin.articles.edit', compact('article', 'categories', 'authors', 'tagsString'));
    }

    /**
     * Update the specified article in storage.
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "nullable|string|max:255|unique:articles,slug,{$article->id}",
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:500',
            'author_id' => 'required|exists:users,id',
            'category_id' => 'nullable|exists:article_categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'reading_time_minutes' => 'nullable|integer|min:1',
            'tags' => 'nullable|string|max:500',
            // SEO Meta Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'canonical_url' => 'nullable|string|max:500',
            'robots' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:1000',
            'og_image' => 'nullable|string|max:500',
            'twitter_card' => 'nullable|string|max:50',
        ]);

        $oldValues = $article->only(['title', 'slug', 'status', 'category_id', 'author_id']);
        $oldSlug = $article->slug;

        $newSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        if ($newSlug !== $oldSlug) {
            $newSlug = $this->generateUniqueSlug($newSlug, $article->id);

            // Create 301 Redirect for old URL path
            $oldPath = "/blog/{$oldSlug}";
            $newPath = "/blog/{$newSlug}";

            SlugRedirect::updateOrCreate(
                ['old_path' => $oldPath],
                ['new_path' => $newPath, 'status_code' => 301]
            );
        }

        $publishedAt = $validated['published_at'] ?? $article->published_at;
        if ($validated['status'] === ArticleStatus::PUBLISHED->value && empty($publishedAt)) {
            $publishedAt = now();
        }

        $article->update([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image' => $validated['featured_image'] ?? null,
            'author_id' => $validated['author_id'],
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'reading_time_minutes' => $validated['reading_time_minutes'] ?? 0,
        ]);

        // Sync Tags
        $this->syncTags($article, $validated['tags'] ?? '');

        // Update or create SEO Meta
        $article->seo()->updateOrCreate(
            ['seoable_id' => $article->id, 'seoable_type' => Article::class],
            [
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'canonical_url' => $validated['canonical_url'] ?? null,
                'robots' => $validated['robots'] ?? 'index, follow',
                'og_title' => $validated['og_title'] ?? null,
                'og_description' => $validated['og_description'] ?? null,
                'og_image' => $validated['og_image'] ?? null,
                'twitter_card' => $validated['twitter_card'] ?? 'summary_large_image',
            ]
        );

        // Audit Logging
        AuditLogger::log(
            action: 'updated',
            auditable: $article,
            description: "Updated article '{$article->title}'",
            oldValues: $oldValues,
            newValues: $article->only(['title', 'slug', 'status', 'category_id', 'author_id']),
            resourceLabel: $article->title
        );

        Cache::forget('sitemap_xml');

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "Article '{$article->title}' updated successfully.");
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $title = $article->title;
        $oldValues = $article->only(['title', 'slug', 'status']);

        $article->seo()?->delete();
        $article->tags()->detach();
        $article->delete();

        AuditLogger::log(
            action: 'deleted',
            auditable: 'Article',
            description: "Deleted article '{$title}'",
            oldValues: $oldValues,
            newValues: null,
            resourceLabel: $title
        );

        Cache::forget('sitemap_xml');

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "Article '{$title}' deleted successfully.");
    }

    /**
     * Quick toggle or update of article status.
     */
    public function toggleStatus(Request $request, Article $article): RedirectResponse
    {
        $status = $request->input('status');
        if (!in_array($status, ['draft', 'published', 'archived'])) {
            return back()->with('error', 'Invalid status specified.');
        }

        $oldStatus = $article->status->value;
        $publishedAt = $article->published_at;
        if ($status === ArticleStatus::PUBLISHED->value && empty($publishedAt)) {
            $publishedAt = now();
        }

        $article->update([
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        AuditLogger::log(
            action: 'status_changed',
            auditable: $article,
            description: "Changed status of article '{$article->title}' from {$oldStatus} to {$status}",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $status],
            resourceLabel: $article->title
        );

        Cache::forget('sitemap_xml');

        return back()->with('success', "Article status updated to " . ucfirst($status) . ".");
    }

    /**
     * Sync comma-separated tags with Article.
     */
    protected function syncTags(Article $article, string $tagsString): void
    {
        if (empty(trim($tagsString))) {
            $article->tags()->sync([]);
            return;
        }

        $tagNames = array_filter(array_map('trim', explode(',', $tagsString)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            if (empty($slug)) continue;

            $tag = ArticleTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $article->tags()->sync($tagIds);
    }

    /**
     * Generate unique slug for article.
     */
    protected function generateUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $original = $slug;
        $count = 1;

        while (Article::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
