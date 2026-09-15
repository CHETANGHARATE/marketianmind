<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable([
    'enrollment_id',
    'order_id',
    'period_type',
    'starts_at',
    'expires_at',
])]
class CourseAccessPeriod extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'period_type' => 'initial',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the enrollment that this access period belongs to.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the order that generated this access period, if any.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user associated with this access period through enrollment.
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'user_id');
    }

    /**
     * Get the course associated with this access period through enrollment.
     */
    public function course(): HasOneThrough
    {
        return $this->hasOneThrough(Course::class, Enrollment::class, 'id', 'id', 'enrollment_id', 'course_id');
    }

    /**
     * Scope a query to only include currently active access periods.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('starts_at', '<=', $now)->where('expires_at', '>', $now);
    }

    /**
     * Scope a query to only include expired access periods.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if this access period is currently active.
     */
    public function isActive(): bool
    {
        return now()->between($this->starts_at, $this->expires_at);
    }

    /**
     * Check if this access period has expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if this is an initial purchase period.
     */
    public function isInitial(): bool
    {
        return $this->period_type === 'initial';
    }

    /**
     * Check if this is a renewal period.
     */
    public function isRenewal(): bool
    {
        return $this->period_type === 'renewal';
    }

    /**
     * Check if this is an admin-granted period.
     */
    public function isAdminGrant(): bool
    {
        return $this->period_type === 'admin_grant';
    }

    /**
     * Get the preceding access period for the same enrollment.
     */
    public function getPreviousPeriod(): ?CourseAccessPeriod
    {
        return static::where('enrollment_id', $this->enrollment_id)
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Check if this renewal period was purchased early (before preceding access expired).
     */
    public function isEarlyRenewal(): bool
    {
        if (! $this->isRenewal()) {
            return false;
        }

        $prev = $this->getPreviousPeriod();
        if (! $prev || ! $prev->expires_at) {
            return $this->starts_at && $this->created_at && $this->starts_at->gt($this->created_at->copy()->addMinutes(5));
        }

        return ($this->created_at && $this->created_at->lt($prev->expires_at))
            || ($this->starts_at && $this->starts_at->equalTo($prev->expires_at));
    }

    /**
     * Check if this renewal period was purchased post-expiry.
     */
    public function isPostExpiryRenewal(): bool
    {
        return $this->isRenewal() && ! $this->isEarlyRenewal();
    }

    /**
     * Get the number of days between prior period expiry and this renewal.
     */
    public function getLeadOrDelayDays(): int
    {
        $prev = $this->getPreviousPeriod();
        if (! $prev || ! $prev->expires_at || ! $this->created_at) {
            return 0;
        }

        return (int) abs($this->created_at->diffInDays($prev->expires_at));
    }
}
