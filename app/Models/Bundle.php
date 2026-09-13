<?php

namespace App\Models;

use App\Enums\BundleStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'slug',
    'short_description',
    'description',
    'thumbnail',
    'price',
    'status',
    'featured',
])]
class Bundle extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price' => 0.00,
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
            'status' => BundleStatus::class,
            'featured' => 'boolean',
        ];
    }

    /**
     * Courses included in this bundle ordered by sort_order.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'bundle_courses', 'bundle_id', 'course_id')
            ->withPivot('sort_order')
            ->orderBy('bundle_courses.sort_order')
            ->withTimestamps();
    }

    /**
     * Get SEO metadata for the bundle.
     */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Only published courses included in this bundle.
     */
    public function publishedCourses(): BelongsToMany
    {
        return $this->courses()
            ->where('courses.status', CourseStatus::PUBLISHED->value);
    }

    /**
     * Orders placed for this bundle.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Payments made for this bundle.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope a query to only include published bundles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('bundles.status', BundleStatus::PUBLISHED->value);
    }

    /**
     * Scope a query to only include featured bundles.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('bundles.featured', true);
    }

    /**
     * Scope a query to search bundles.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim($term ?? '');

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('bundles.title', 'like', "%{$term}%")
                ->orWhere('bundles.short_description', 'like', "%{$term}%")
                ->orWhere('bundles.description', 'like', "%{$term}%");
        });
    }

    /**
     * Check if the bundle is published.
     */
    public function isPublished(): bool
    {
        return $this->status === BundleStatus::PUBLISHED;
    }

    /**
     * Get bundle price converted to paise for Razorpay.
     */
    public function priceInPaise(): int
    {
        return (int) round(((float) $this->price) * 100);
    }

    /**
     * Formatted bundle price.
     */
    public function formattedPrice(): string
    {
        return '₹' . number_format((float) $this->price, 2);
    }

    /**
     * Sum of effective prices of all published courses in the bundle.
     */
    public function individualCoursesTotal(): float
    {
        $courses = $this->relationLoaded('publishedCourses')
            ? $this->publishedCourses
            : $this->publishedCourses()->get();

        return (float) $courses->sum(fn (Course $course) => $course->effectivePrice());
    }

    /**
     * Formatted total individual value.
     */
    public function formattedIndividualCoursesTotal(): string
    {
        return '₹' . number_format($this->individualCoursesTotal(), 2);
    }

    /**
     * Total monetary savings when purchasing the bundle vs individual courses.
     */
    public function savings(): float
    {
        $total = $this->individualCoursesTotal();
        $bundlePrice = (float) $this->price;

        return max(0.00, round($total - $bundlePrice, 2));
    }

    /**
     * Formatted monetary savings.
     */
    public function formattedSavings(): string
    {
        return '₹' . number_format($this->savings(), 2);
    }

    /**
     * Percentage savings when purchasing the bundle.
     */
    public function savingsPercentage(): int
    {
        $total = $this->individualCoursesTotal();
        if ($total <= 0) {
            return 0;
        }

        $savings = $this->savings();
        return (int) round(($savings / $total) * 100);
    }

    /**
     * Check if the given user already has active access to ALL published courses in this bundle.
     */
    public function hasUserAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $publishedCourses = $this->relationLoaded('publishedCourses')
            ? $this->publishedCourses
            : $this->publishedCourses()->get();

        if ($publishedCourses->isEmpty()) {
            return false;
        }

        $userEnrolledCourseIds = $user->enrollments()
            ->whereIn('status', [EnrollmentStatus::ACTIVE, EnrollmentStatus::COMPLETED])
            ->pluck('course_id')
            ->flip();

        foreach ($publishedCourses as $course) {
            if (! isset($userEnrolledCourseIds[$course->id])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count of courses in the bundle that the user already owns.
     */
    public function userOwnedCoursesCount(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $publishedCourseIds = $this->publishedCourses()->pluck('courses.id')->toArray();

        if (empty($publishedCourseIds)) {
            return 0;
        }

        return (int) $user->enrollments()
            ->whereIn('course_id', $publishedCourseIds)
            ->whereIn('status', [EnrollmentStatus::ACTIVE, EnrollmentStatus::COMPLETED])
            ->count();
    }

    /**
     * Resolve route binding for ID or slug.
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
     * Get the public URL for the bundle thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://')) {
            return $this->thumbnail;
        }

        return Storage::disk('public')->url($this->thumbnail);
    }

    /**
     * Get offers targeting this bundle.
     */
    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_products', 'product_id', 'offer_id')
            ->withPivotValue('product_type', 'bundle')
            ->withTimestamps();
    }

    /**
     * Get the active winning promotional offer for this bundle, if any.
     */
    public function currentOffer(): ?Offer
    {
        return app(\App\Services\PricingService::class)->getWinningOffer($this);
    }

    /**
     * Check if this bundle currently has an active promotional offer.
     */
    public function hasActiveOffer(): bool
    {
        return $this->currentOffer() !== null;
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
        return '₹' . number_format($this->finalPrice(), 2);
    }

    /**
     * CRM inquiries/leads interested in this bundle.
     */
    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
