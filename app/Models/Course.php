<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'course_category_id',
    'title',
    'slug',
    'short_description',
    'description',
    'thumbnail',
    'instructor_name',
    'price',
    'discount_price',
    'is_free',
    'status',
    'featured',
    'estimated_duration',
])]
class Course extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price' => 0.00,
        'is_free' => false,
        'status' => 'draft',
        'featured' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_free' => 'boolean',
            'status' => CourseStatus::class,
            'featured' => 'boolean',
        ];
    }

    /**
     * Get the category that this course belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    /**
     * Alias for category relation.
     */
    public function courseCategory(): BelongsTo
    {
        return $this->category();
    }

    /**
     * Get all modules for the course ordered by sort order.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'course_id')->orderBy('sort_order');
    }

    /**
     * Alias for modules relation.
     */
    public function courseModules(): HasMany
    {
        return $this->modules();
    }

    /**
     * Get all lessons through course modules.
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, CourseModule::class, 'course_id', 'course_module_id');
    }

    /**
     * Scope a query to only include published courses.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::PUBLISHED->value);
    }

    /**
     * Scope a query to only include featured courses.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Scope a query to only include free courses.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope a query to only include paid courses.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    /**
     * Check if the course is published.
     */
    public function isPublished(): bool
    {
        return $this->status === CourseStatus::PUBLISHED;
    }

    /**
     * Check if the course is in draft mode.
     */
    public function isDraft(): bool
    {
        return $this->status === CourseStatus::DRAFT;
    }

    /**
     * Check if the course is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === CourseStatus::ARCHIVED;
    }

    /**
     * Determine if this course has a valid discount price.
     */
    public function hasDiscount(): bool
    {
        return ! $this->is_free
            && $this->discount_price !== null
            && (float) $this->discount_price < (float) $this->price;
    }

    /**
     * Get the effective purchase price for the course.
     */
    public function effectivePrice(): float
    {
        if ($this->is_free) {
            return 0.00;
        }

        if ($this->hasDiscount()) {
            return (float) $this->discount_price;
        }

        return (float) $this->price;
    }

    /**
     * Get the full URL for the course thumbnail.
     */
    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://')) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }
}

