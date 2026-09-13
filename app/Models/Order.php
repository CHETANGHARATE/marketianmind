<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'course_id',
    'bundle_id',
    'offer_id',
    'offer_discount_amount',
    'coupon_id',
    'coupon_code',
    'order_number',
    'razorpay_order_id',
    'original_amount',
    'discount_amount',
    'amount',
    'currency',
    'status',
    'paid_at',
    'expires_at',
    'metadata',
])]
class Order extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'INR',
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'amount' => 'integer',
            'offer_discount_amount' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user who placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course purchased in this order.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the bundle purchased in this order, if applicable.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Check if this order is for a bundle.
     */
    public function isBundleOrder(): bool
    {
        return ! is_null($this->bundle_id);
    }

    /**
     * Check if this order is for a single course.
     */
    public function isCourseOrder(): bool
    {
        return ! is_null($this->course_id) && is_null($this->bundle_id);
    }

    /**
     * Get the human-readable product title for this order.
     */
    public function productTitle(): string
    {
        if ($this->isBundleOrder()) {
            return $this->bundle?->title ?? 'Course Bundle';
        }

        return $this->course?->title ?? 'Course';
    }

    /**
     * Get the product type label for display.
     */
    public function productTypeLabel(): string
    {
        return $this->isBundleOrder() ? 'Course Bundle' : 'Single Course';
    }

    /**
     * Get all payment records associated with this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING->value);
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PAID->value);
    }

    /**
     * Scope a query to only include failed orders.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::FAILED->value);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === OrderStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === OrderStatus::REFUNDED;
    }

    /**
     * Mark this order as paid.
     */
    public function markPaid(?\DateTimeInterface $paidAt = null): void
    {
        $this->update([
            'status' => OrderStatus::PAID,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Mark this order as failed.
     */
    public function markFailed(): void
    {
        $this->update([
            'status' => OrderStatus::FAILED,
        ]);
    }

    /**
     * Mark this order as cancelled.
     */
    public function markCancelled(): void
    {
        $this->update([
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    /**
     * Get the amount converted from paise to INR Rupees.
     */
    public function amountInRupees(): float
    {
        return round($this->amount / 100, 2);
    }

    /**
     * Get the formatted currency string for display.
     */
    public function formattedAmount(): string
    {
        return '₹' . number_format($this->amountInRupees(), 2);
    }
    /**
     * Get the coupon applied to this order, if any.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Check if a coupon is applied.
     */
    public function hasCoupon(): bool
    {
        return ! empty($this->coupon_code) || ! is_null($this->coupon_id);
    }

    /**
     * Get original amount before coupon discount in INR Rupees.
     */
    public function originalAmountInRupees(): float
    {
        $orig = $this->original_amount ?? $this->amount;
        return round($orig / 100, 2);
    }

    /**
     * Formatted original amount before discount.
     */
    public function formattedOriginalAmount(): string
    {
        return '₹' . number_format($this->originalAmountInRupees(), 2);
    }

    /**
     * Get discount amount in INR Rupees.
     */
    public function discountAmountInRupees(): float
    {
        return round(($this->discount_amount ?? 0) / 100, 2);
    }

    /**
     * Formatted discount amount.
     */
    public function formattedDiscountAmount(): string
    {
        return '₹' . number_format($this->discountAmountInRupees(), 2);
    }

    /**
     * Get the promotional offer applied to this order, if any.
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Check if a promotional offer is applied.
     */
    public function hasOffer(): bool
    {
        return ! is_null($this->offer_id) || ($this->offer_discount_amount ?? 0) > 0;
    }

    /**
     * Get promotional offer discount amount in INR Rupees.
     */
    public function offerDiscountAmountInRupees(): float
    {
        return round(($this->offer_discount_amount ?? 0) / 100, 2);
    }

    /**
     * Formatted offer discount amount.
     */
    public function formattedOfferDiscountAmount(): string
    {
        return '₹' . number_format($this->offerDiscountAmountInRupees(), 2);
    }

    /**
     * Get total combined savings (offer discount + coupon discount) in INR Rupees.
     */
    public function totalSavingsInRupees(): float
    {
        $offerDisc = $this->offer_discount_amount ?? 0;
        $couponDisc = $this->discount_amount ?? 0;
        return round(($offerDisc + $couponDisc) / 100, 2);
    }

    /**
     * Formatted total savings.
     */
    public function formattedTotalSavings(): string
    {
        return '₹' . number_format($this->totalSavingsInRupees(), 2);
    }
}