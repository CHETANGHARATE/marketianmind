<?php

namespace App\Models;

use App\Enums\CouponDiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'discount_type',
    'discount_value',
    'min_order_amount',
    'max_discount_amount',
    'starts_at',
    'expires_at',
    'usage_limit',
    'per_user_limit',
    'times_used',
    'is_active',
    'course_id',
])]
class Coupon extends Model
{
    use HasFactory;

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_type' => 'percentage',
        'per_user_limit' => 1,
        'times_used' => 0,
        'is_active' => true,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit' => 'integer',
            'per_user_limit' => 'integer',
            'times_used' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Normalize coupon code to uppercase before setting.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Redemptions of this coupon.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Orders associated with this coupon.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Course restricted to, or null if applicable to all courses.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if coupon is restricted to a specific course.
     */
    public function isCourseSpecific(): bool
    {
        return ! is_null($this->course_id);
    }

    /**
     * Scope to active coupons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to currently valid date coupons.
     */
    public function scopeCurrentlyValid(Builder $query): Builder
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Check whether the coupon has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }

    /**
     * Check whether coupon start date is in the future.
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && now()->lt($this->starts_at);
    }

    /**
     * Check whether global usage limit has been reached.
     */
    public function hasReachedGlobalLimit(): bool
    {
        return $this->usage_limit !== null && $this->times_used >= $this->usage_limit;
    }

    /**
     * Check whether a specific user has reached their usage limit.
     */
    public function hasUserReachedLimit(User $user): bool
    {
        $userCount = $this->usages()->where('user_id', $user->id)->count();
        return $userCount >= $this->per_user_limit;
    }

    /**
     * Calculate discount in paise given an order base amount in paise.
     */
    public function calculateDiscount(int $amountInPaise): int
    {
        if ($amountInPaise <= 0) {
            return 0;
        }

        if ($this->discount_type === CouponDiscountType::PERCENTAGE) {
            $discount = (int) round(($amountInPaise * $this->discount_value) / 100);

            if ($this->max_discount_amount !== null && $this->max_discount_amount > 0) {
                $discount = min($discount, $this->max_discount_amount);
            }

            return min($discount, $amountInPaise);
        }

        // Fixed amount discount (already in paise)
        return min($this->discount_value, $amountInPaise);
    }

    /**
     * User-friendly discount display string.
     */
    public function formattedDiscount(): string
    {
        if ($this->discount_type === CouponDiscountType::PERCENTAGE) {
            return $this->discount_value . '% OFF';
        }

        return '₹' . number_format($this->discount_value / 100, 2) . ' OFF';
    }

    /**
     * Formatted minimum order amount in rupees.
     */
    public function formattedMinOrder(): ?string
    {
        return $this->min_order_amount ? '₹' . number_format($this->min_order_amount / 100, 2) : null;
    }

    /**
     * Formatted maximum discount amount in rupees.
     */
    public function formattedMaxDiscount(): ?string
    {
        return $this->max_discount_amount ? '₹' . number_format($this->max_discount_amount / 100, 2) : null;
    }
}