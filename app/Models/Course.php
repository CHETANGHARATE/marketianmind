<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'course_category_id',
    'instructor_id',
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
    'access_validity_days',
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
        'access_validity_days' => 365,
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
            'access_validity_days' => 'integer',
        ];
    }

    /**
     * Bootstrap model events.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });
    }

    /**
     * Get the category that this course belongs to.
     */
     public function category(): BelongsTo
     {
         return $this->belongsTo(CourseCategory::class, 'course_category_id');
     }

    /**
     * Get the instructor that teaches this course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    /**
     * Get the display name of the instructor.
     */
    public function instructorDisplayName(): string
    {
        return $this->instructor?->name ?? $this->instructor_name ?? 'Marketian Mind Faculty';
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
     * Get SEO metadata for the course.
     */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Scope a query to only include published courses.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('courses.status', CourseStatus::PUBLISHED->value);
    }

    /**
     * Scope a query to only include featured courses.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('courses.featured', true);
    }

    /**
     * Scope a query to only include free courses.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('courses.is_free', true);
    }

    /**
     * Scope a query to only include paid courses.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('courses.is_free', false);
    }

    /**
     * Get SQL expression for effective price calculation across queries and sorting.
     */
    public static function effectivePriceSql(): string
    {
        return '(CASE WHEN courses.is_free = 1 THEN 0.00 WHEN courses.discount_price IS NOT NULL AND courses.discount_price > 0 AND courses.discount_price < courses.price THEN courses.discount_price ELSE courses.price END)';
    }

    /**
     * Scope a query to search courses by title, short_description, description, instructor_name, or category.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $this->scopeAdvancedSearch($query, $term);
    }

    /**
     * Scope a query to perform advanced multi-column search across courses.
     */
    public function scopeAdvancedSearch(Builder $query, ?string $term): Builder
    {
        $term = trim($term ?? '');

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('courses.title', 'like', "%{$term}%")
                ->orWhere('courses.short_description', 'like', "%{$term}%")
                ->orWhere('courses.description', 'like', "%{$term}%")
                ->orWhere('courses.instructor_name', 'like', "%{$term}%")
                ->orWhereHas('category', function (Builder $cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Scope a query to filter courses by effective price range.
     */
    public function scopePriceRange(Builder $query, ?float $minPrice = null, ?float $maxPrice = null): Builder
    {
        if ($minPrice !== null && $minPrice >= 0) {
            $minVal = (float) $minPrice;
            $query->whereRaw(static::effectivePriceSql() . " >= {$minVal}");
        }

        if ($maxPrice !== null && $maxPrice >= 0) {
            $maxVal = (float) $maxPrice;
            $query->whereRaw(static::effectivePriceSql() . " <= {$maxVal}");
        }

        return $query;
    }

    /**
     * Scope a query to filter courses by minimum average approved rating.
     */
    public function scopeMinRating(Builder $query, ?float $minRating): Builder
    {
        if ($minRating === null || $minRating <= 0) {
            return $query;
        }

        $minVal = (float) $minRating;

        return $query->whereHas('approvedReviews')
            ->whereRaw(
                "(SELECT COALESCE(AVG(rating), 0) FROM course_reviews WHERE course_reviews.course_id = courses.id AND course_reviews.status = ?) >= {$minVal}",
                [\App\Enums\CourseReviewStatus::APPROVED->value]
            );
    }

    /**
     * Scope a query to filter courses by duration range.
     */
    public function scopeDurationRange(Builder $query, ?string $duration): Builder
    {
        return match ($duration) {
            'short' => $query->whereRaw('(courses.estimated_duration + 0) > 0 AND (courses.estimated_duration + 0) < 3'),
            'medium' => $query->whereRaw('(courses.estimated_duration + 0) >= 3 AND (courses.estimated_duration + 0) <= 10'),
            'long' => $query->whereRaw('(courses.estimated_duration + 0) > 10'),
            default => $query,
        };
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
     * Get the effective purchase price in paise (integer).
     */
    public function effectivePriceInPaise(): int
    {
        return (int) round($this->effectivePrice() * 100);
    }

    /**
     * Get formatted course price with currency symbol.
     */
    public function formattedPrice(): string
    {
        if ($this->is_free) {
            return 'Free';
        }

        return '₹' . number_format($this->effectivePrice(), 2);
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

    /**
     * Get the number of days of access granted by one purchase or renewal.
     */
    public function getAccessValidityDays(): int
    {
        return (int) ($this->access_validity_days ?? 365);
    }

    /**
     * Get all enrollments for this course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all access periods for this course through enrollments.
     */
    public function accessPeriods(): HasManyThrough
    {
        return $this->hasManyThrough(CourseAccessPeriod::class, Enrollment::class);
    }

    /**
     * Get all orders placed for this course.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all certificates issued for this course.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the certificate for a specific user if one has been issued.
     */
    public function certificateFor(?User $user): ?Certificate
    {
        if (! $user) {
            return null;
        }

        return Certificate::query()
            ->where('user_id', $user->id)
            ->where('course_id', $this->id)
            ->first();
    }

    /**
     * Get all users enrolled in this course.
     */
    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Check if a specific user is enrolled in this course.
     */
    public function isEnrolledBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->exists();
    }

    /**
     * Get learning progress metrics for a specific user.
     *
     * @return array{total: int, completed: int, percentage: int, is_completed: bool}
     */
    public function progressFor(?User $user): array
    {
        if (! $user) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'is_completed' => false,
            ];
        }

        $publishedLessonIds = Lesson::query()
            ->where('status', LessonStatus::PUBLISHED->value)
            ->whereHas('module', function (Builder $query) {
                $query->where('course_id', $this->id);
            })
            ->pluck('id');

        $total = $publishedLessonIds->count();
        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'is_completed' => false,
            ];
        }

        $completed = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $publishedLessonIds)
            ->where('completed', true)
            ->count();

        $percentage = (int) round(($completed / $total) * 100);

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => min(100, $percentage),
            'is_completed' => $completed >= $total && $total > 0,
        ];
    }

    /**
     * Find the next incomplete lesson for a user, or the first lesson.
     */
    public function nextLessonFor(?User $user): ?Lesson
    {
        $modules = $this->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => function ($query) {
                $query->where('status', LessonStatus::PUBLISHED->value)
                    ->orderBy('sort_order');
            }])
            ->get();

        if ($modules->isEmpty()) {
            return null;
        }

        if (! $user) {
            return $modules->first()->lessons->first();
        }

        $completedLessonIds = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->flip();

        foreach ($modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (! isset($completedLessonIds[$lesson->id])) {
                    return $lesson;
                }
            }
        }

        return $modules->first()->lessons->first();
    }

    /**
     * Retrieve the model for a bound value (supports ID or slug).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        return is_numeric($value)
            ? $this->where('id', $value)->first()
            : $this->where('slug', $value)->first();
    }
    /**
     * Get all student reviews for this course.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    /**
     * Get only approved student reviews visible publicly.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class)->where('status', \App\Enums\CourseReviewStatus::APPROVED->value);
    }

    /**
     * Get average rating score rounded to 1 decimal place.
     */
    public function averageRating(): float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Get total count of approved reviews.
     */
    public function reviewsCount(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get detailed breakdown of star ratings and percentages.
     *
     * @return array<int, array{stars: int, count: int, percentage: int}>
     */
    public function ratingDistribution(): array
    {
        $total = $this->reviewsCount();
        $ratings = $this->approvedReviews()
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->all();

        $distribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = $ratings[$star] ?? 0;
            $percentage = $total > 0 ? (int) round(($count / $total) * 100) : 0;
            $distribution[$star] = [
                'stars' => $star,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $distribution;
    }

    /**
     * Find a specific user's review for this course.
     */
    public function userReview(User $user): ?CourseReview
    {
        return $this->reviews()->where('user_id', $user->id)->first();
    }

    /**
     * Check if a specific user has reviewed this course.
     */
    public function hasReviewFrom(User $user): bool
    {
        return $this->reviews()->where('user_id', $user->id)->exists();
    }
    /**
     * Get all wishlist entries for this course.
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Check if this course is in a specific user's wishlist.
     */
    public function isWishlistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->wishlists()->where('user_id', $user->id)->exists();
    }

    /**
     * Get bundles that include this course.
     */
    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'bundle_courses', 'course_id', 'bundle_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Get offers targeting this course.
     */
    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_products', 'product_id', 'offer_id')
            ->withPivotValue('product_type', 'course')
            ->withTimestamps();
    }

    /**
     * Get the active winning promotional offer for this course, if any.
     */
    public function currentOffer(): ?Offer
    {
        if ($this->relationLoaded('offers')) {
            return $this->offers->first();
        }

        return app(\App\Services\PricingService::class)->getWinningOffer($this);
    }

    /**
     * Check if this course currently has an active promotional offer.
     */
    public function hasActiveOffer(): bool
    {
        return ! $this->is_free && $this->currentOffer() !== null;
    }

    /**
     * Get the resolved current selling price (in rupees) after applying any active promotional offer.
     */
    public function finalPrice(): float
    {
        $pricing = app(\App\Services\PricingService::class)->resolveForProduct($this);
        return $pricing['final_price'];
    }

    /**
     * Get the resolved current selling price in paise.
     */
    public function finalPriceInPaise(): int
    {
        $pricing = app(\App\Services\PricingService::class)->resolveForProduct($this);
        return $pricing['final_price_in_paise'];
    }

    /**
     * Get the formatted final price.
     */
    public function formattedFinalPrice(): string
    {
        if ($this->is_free) {
            return 'Free';
        }

        return '₹' . number_format($this->finalPrice(), 2);
    }

    /**
     * Check if a given user currently has active learning access to this course.
     */
    public function hasActiveAccessFor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasActiveAccessTo($this);
    }

    /**
     * Get the purchase type for a given user.
     */
    public function getPurchaseTypeFor(?User $user): \App\Enums\CoursePurchaseType
    {
        if (! $user) {
            return \App\Enums\CoursePurchaseType::INITIAL_PURCHASE;
        }

        return $user->getCoursePurchaseType($this);
    }

    /**
     * Check if a given user can purchase this course.
     */
    public function canPurchaseFor(?User $user): bool
    {
        if (! $user) {
            return true;
        }

        return $user->canPurchaseCourse($this);
    }


    /**
     * Get human-friendly access duration label.
     * Examples:
     * - Free course: "Free Access"
     * - 365 days: "1 Year Access"
     * - 730 days: "2 Years Access"
     * - 180 days: "180 Days Access"
     * - 90 days: "90 Days Access"
     * - 30 days: "30 Days Access"
     * - 1 day: "1 Day Access"
     * - X days: "X Days Access"
     */
    public function accessDurationLabel(): string
    {
        if ($this->is_free) {
            return 'Free Access';
        }

        $days = $this->getAccessValidityDays();

        if ($days === 365) {
            return '1 Year Access';
        }

        if ($days === 730) {
            return '2 Years Access';
        }

        if ($days > 365 && $days % 365 === 0) {
            $years = (int) ($days / 365);
            return "{$years} Years Access";
        }

        if ($days === 1) {
            return '1 Day Access';
        }

        return "{$days} Days Access";
    }

    /**
     * Get a short duration descriptor without the word "Access".
     * E.g. "1 Year", "180 Days", "Free"
     */
    public function accessDurationShort(): string
    {
        if ($this->is_free) {
            return 'Free';
        }

        $days = $this->getAccessValidityDays();

        if ($days === 365) {
            return '1 Year';
        }

        if ($days === 730) {
            return '2 Years';
        }

        if ($days > 365 && $days % 365 === 0) {
            $years = (int) ($days / 365);
            return "{$years} Years";
        }

        if ($days === 1) {
            return '1 Day';
        }

        return "{$days} Days";
    }

    /**
     * Get a descriptive sentence summarizing access and payment terms.
     */
    public function accessValidityDescription(): string
    {
        if ($this->is_free) {
            return '100% free access for business owners & founders.';
        }

        return 'Pay once for ' . $this->accessDurationShort() . ' of full access. Manual renewal available with permanent progress preservation.';
    }
}