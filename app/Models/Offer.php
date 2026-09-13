<?php

namespace App\Models;

use App\Enums\OfferDiscountType;
use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'priority',
        'is_active',
        'allow_coupons',
        'usage_limit',
        'times_used',
        'badge_text',
    ];

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_type' => 'percentage',
        'priority' => 0,
        'is_active' => true,
        'allow_coupons' => false,
        'times_used' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => OfferDiscountType::class,
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'allow_coupons' => 'boolean',
            'usage_limit' => 'integer',
            'times_used' => 'integer',
        ];
    }

    /**
     * Route binding key.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Alias accessor for title.
     */
    public function getTitleAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    /**
     * Alias mutator for title.
     */
    public function setTitleAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            if (empty($offer->slug)) {
                $offer->slug = \Illuminate\Support\Str::slug($offer->name ?? $offer->title ?? 'offer-' . uniqid());
            }
        });
    }

    /**
     * Redirection / product pivot mappings.
     */
    public function offerProducts(): HasMany
    {
        return $this->hasMany(OfferProduct::class);
    }

    /**
     * Orders placed under this promotional offer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all courses linked to this offer.
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'offer_products', 'offer_id', 'product_id')
            ->withPivotValue('product_type', 'course')
            ->withTimestamps();
    }

    /**
     * Get all bundles linked to this offer.
     */
    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'offer_products', 'offer_id', 'product_id')
            ->withPivotValue('product_type', 'bundle')
            ->withTimestamps();
    }

    /**
     * Determine dynamic offer status based on admin flag, schedule, and usage.
     */
    public function status(): OfferStatus
    {
        if (! $this->is_active) {
            return OfferStatus::DISABLED;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return OfferStatus::SCHEDULED;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return OfferStatus::EXPIRED;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return OfferStatus::EXPIRED;
        }

        return OfferStatus::ACTIVE;
    }

    /**
     * Check if the offer is currently active and eligible to be applied.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->status() === OfferStatus::ACTIVE;
    }

    /**
     * Check if the offer has expired.
     */
    public function hasExpired(): bool
    {
        return $this->status() === OfferStatus::EXPIRED;
    }

    /**
     * Check if the offer is scheduled for the future.
     */
    public function isUpcoming(): bool
    {
        return $this->status() === OfferStatus::SCHEDULED;
    }

    /**
     * Check if the offer has reached its usage limit.
     */
    public function hasReachedLimit(): bool
    {
        return $this->usage_limit !== null && $this->times_used >= $this->usage_limit;
    }

    /**
     * Scope a query to only include active offers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include currently valid offers by schedule and limits.
     */
    public function scopeCurrentlyValid(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function (Builder $q) {
                $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit');
            });
    }

    /**
     * Scope a query to offers matching a specific product.
     */
    public function scopeForProduct(Builder $query, string $productType, int $productId): Builder
    {
        return $query->whereHas('offerProducts', function (Builder $q) use ($productType, $productId) {
            $q->where('product_type', $productType)
                ->where('product_id', $productId);
        });
    }

    /**
     * Calculate promotional discount in paise given a base amount in paise.
     */
    public function calculateDiscount(int $amountInPaise): int
    {
        if ($amountInPaise <= 0) {
            return 0;
        }

        if ($this->discount_type === OfferDiscountType::PERCENTAGE) {
            $discount = (int) round(($amountInPaise * (float) $this->discount_value) / 100);
            return min($discount, $amountInPaise);
        }

        // Fixed amount discount in Rupees converted to paise
        $fixedDiscountInPaise = (int) round(((float) $this->discount_value) * 100);
        return min($fixedDiscountInPaise, $amountInPaise);
    }

    /**
     * User-friendly discount display string.
     */
    public function formattedDiscount(): string
    {
        if ($this->discount_type === OfferDiscountType::PERCENTAGE) {
            $val = (float) $this->discount_value;
            return ((int) $val == $val ? (int) $val : number_format($val, 1)) . '% OFF';
        }

        return '₹' . number_format((float) $this->discount_value, 2) . ' OFF';
    }

    /**
     * Badge text for UI display.
     */
    public function displayBadge(): string
    {
        if (! empty($this->badge_text)) {
            return $this->badge_text;
        }

        return $this->formattedDiscount();
    }
}
