<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author_id',
        'category_id',
        'status',
        'published_at',
        'is_featured',
        'reading_time_minutes',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'reading_time_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::saving(function ($article) {
            if (empty($article->reading_time_minutes) || $article->reading_time_minutes <= 0) {
                $words = str_word_count(strip_tags($article->content ?? ''));
                $article->reading_time_minutes = max(1, (int) ceil($words / 200));
            }
        });

        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_tag', 'article_id', 'tag_id');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Get sanitized HTML content safe for rendering.
     */
    public function getSanitizedContentAttribute(): string
    {
        return app(ContentSanitizerService::class)->sanitize($this->content ?? '');
    }

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('excerpt', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    public function scopeByCategory(Builder $query, ?string $categorySlug): Builder
    {
        if (empty($categorySlug)) {
            return $query;
        }

        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    public function scopeByTag(Builder $query, ?string $tagSlug): Builder
    {
        if (empty($tagSlug)) {
            return $query;
        }

        return $query->whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }
}
