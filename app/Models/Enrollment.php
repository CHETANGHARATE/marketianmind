<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'course_id',
    'status',
    'starts_at',
    'expires_at',
    'enrolled_at',
    'completed_at',
])]
class Enrollment extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that this enrollment belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the certificate associated with this enrollment.
     */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * Get all historical access periods for this enrollment.
     */
    public function accessPeriods(): HasMany
    {
        return $this->hasMany(CourseAccessPeriod::class)->orderBy('starts_at', 'desc');
    }

    /**
     * Get the latest access period for this enrollment.
     */
    public function latestAccessPeriod(): HasOne
    {
        return $this->hasOne(CourseAccessPeriod::class)->latestOfMany('starts_at');
    }

    /**
     * Scope a query to only include active enrollments.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EnrollmentStatus::ACTIVE->value);
    }

    /**
     * Scope a query to only include completed enrollments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', EnrollmentStatus::COMPLETED->value);
    }

    /**
     * Scope a query to enrollments with active learning access (finite unexpired or legacy lifetime).
     */
    public function scopeAccessActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('status', EnrollmentStatus::ACTIVE->value)
            ->where(function (Builder $q) use ($now) {
                $q->where(function (Builder $finite) use ($now) {
                    $finite->whereNotNull('starts_at')
                        ->whereNotNull('expires_at')
                        ->where('starts_at', '<=', $now)
                        ->where('expires_at', '>', $now);
                })->orWhere(function (Builder $lifetime) {
                    $lifetime->whereNull('starts_at')
                        ->whereNull('expires_at');
                });
            });
    }

    /**
     * Scope a query to enrollments expiring within the specified days (default 30).
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        $now = now();
        $threshold = now()->addDays($days);
        return $query->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->whereNotNull('starts_at')
            ->whereNotNull('expires_at')
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $threshold);
    }

    /**
     * Scope a query to enrollments with expired access.
     */
    public function scopeAccessExpired(Builder $query): Builder
    {
        $now = now();
        return $query->where(function (Builder $q) use ($now) {
            $q->where('status', EnrollmentStatus::EXPIRED->value)
                ->orWhere(function (Builder $sub) use ($now) {
                    $sub->whereNotNull('starts_at')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now);
                });
        });
    }

    /**
     * Scope a query to legacy lifetime enrollments.
     */
    public function scopeLegacyLifetime(Builder $query): Builder
    {
        return $query->whereNull('starts_at')->whereNull('expires_at');
    }

    /**
     * Scope a query to anomalous enrollments needing administrative review.
     */
    public function scopeAnomalousAccess(Builder $query): Builder
    {
        $now = now();
        return $query->where(function (Builder $q) use ($now) {
            // Partial null dates
            $q->where(function (Builder $sub) {
                $sub->whereNull('starts_at')->whereNotNull('expires_at');
            })->orWhere(function (Builder $sub) {
                $sub->whereNotNull('starts_at')->whereNull('expires_at');
            })
            // Inverted dates
            ->orWhere(function (Builder $sub) {
                $sub->whereNotNull('starts_at')
                    ->whereNotNull('expires_at')
                    ->whereColumn('starts_at', '>', 'expires_at');
            })
            // Active status with expired date
            ->orWhere(function (Builder $sub) use ($now) {
                $sub->where('status', EnrollmentStatus::ACTIVE->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', $now);
            })
            // Expired status with future date
            ->orWhere(function (Builder $sub) use ($now) {
                $sub->where('status', EnrollmentStatus::EXPIRED->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '>', $now);
            });
        });
    }


    /**
     * Check if the enrollment is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === EnrollmentStatus::ACTIVE;
    }

    /**
     * Check if the enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === EnrollmentStatus::COMPLETED;
    }

    /**
     * Mark the enrollment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => EnrollmentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Authoritative check: Does this enrollment currently grant active learning access?
     *
     * Evaluates:
     * - Status (ACTIVE and COMPLETED allowed; CANCELLED and EXPIRED denied)
     * - Date boundary: starts_at <= now < expires_at
     * - Legacy lifetime compatibility: if both starts_at and expires_at are NULL, allows access
     * - Partial NULL dates: denied safely
     *
     * Note: Read-only check; performs zero database mutations.
     */
    public function hasActiveAccess(): bool
    {
        // Cancelled or Expired status immediately denies access
        if (in_array($this->status, [EnrollmentStatus::CANCELLED, EnrollmentStatus::EXPIRED], true)) {
            return false;
        }

        // Only ACTIVE or COMPLETED enrollments may have valid learning access
        if (! in_array($this->status, [EnrollmentStatus::ACTIVE, EnrollmentStatus::COMPLETED], true)) {
            return false;
        }

        // Legacy lifetime compatibility:
        // If both starts_at and expires_at are NULL, temporarily allow access for active/completed enrollments
        if ($this->starts_at === null && $this->expires_at === null) {
            return true;
        }

        // Partial NULL dates: if only one date is NULL, fail safely and deny access
        if ($this->starts_at === null || $this->expires_at === null) {
            return false;
        }

        $now = now();

        // Exact boundary rule: starts_at <= now < expires_at
        // At exactly expires_at, access is considered expired
        return $this->starts_at->lte($now) && $now->lt($this->expires_at);
    }

    /**
     * Check if the enrollment access period has expired.
     * Note: Purely read-only; does NOT mutate database state.
     */
    public function isExpired(): bool
    {
        return $this->isAccessExpired();
    }

    /**
     * Check if the enrollment access period is expired or status is EXPIRED.
     * Note: Purely read-only; does NOT mutate database state.
     */
    public function isAccessExpired(): bool
    {
        if ($this->status === EnrollmentStatus::EXPIRED) {
            return true;
        }

        // Legacy lifetime enrollments (both NULL) are not considered expired
        if ($this->starts_at === null && $this->expires_at === null) {
            return false;
        }

        // Partial nulls fail safely (denied access, treated as expired/invalid)
        if ($this->starts_at === null || $this->expires_at === null) {
            return true;
        }

        // At exactly expires_at or after, access is expired
        return now()->gte($this->expires_at);
    }

    /**
     * Check if this enrollment represents a legacy lifetime enrollment with both dates NULL.
     */
    public function isLegacyLifetimeAccess(): bool
    {
        return $this->starts_at === null && $this->expires_at === null;
    }

    /**
     * Check if this enrollment has explicit access period timestamps.
     */
    public function hasAccessPeriod(): bool
    {
        return $this->starts_at !== null || $this->expires_at !== null;
    }

    /**
     * Get the current or most recent access period.
     */
    public function currentAccessPeriod(): ?CourseAccessPeriod
    {
        return $this->accessPeriods()
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->first()
            ?? $this->accessPeriods()->first();
    }

    /**
     * Check if active access is expiring soon within the given threshold (default: 30 days).
     */
    public function isExpiringSoon(int $thresholdDays = 30): bool
    {
        if (! $this->hasActiveAccess() || $this->expires_at === null || $this->isLegacyLifetimeAccess()) {
            return false;
        }

        $now = now();
        $diffSeconds = $now->diffInSeconds($this->expires_at, false);

        if ($diffSeconds <= 0) {
            return false;
        }

        $daysRemaining = (int) ceil($diffSeconds / 86400);

        return $daysRemaining <= $thresholdDays;
    }

    /**
     * Get the high-level access state: 'lifetime', 'active', 'expiring', or 'expired'.
     */
    public function getAccessState(): string
    {
        if ($this->isLegacyLifetimeAccess()) {
            return 'lifetime';
        }

        if ($this->isAccessExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon(30)) {
            return 'expiring';
        }

        if ($this->hasActiveAccess()) {
            return 'active';
        }

        return 'expired';
    }

    /**
     * Get the whole/ceiling number of remaining days of access, or null if expired or lifetime.
     */
    public function getRemainingDays(): ?int
    {
        if ($this->isLegacyLifetimeAccess() || $this->isAccessExpired() || $this->expires_at === null) {
            return null;
        }

        $now = now();
        $diffSeconds = $now->diffInSeconds($this->expires_at, false);

        if ($diffSeconds <= 0) {
            return null;
        }

        return (int) ceil($diffSeconds / 86400);
    }

    /**
     * Get human-readable remaining days string (e.g. "365 days remaining", "Access expired").
     * Never returns "0 days remaining" for expired access.
     */
    public function getRemainingDaysText(): string
    {
        if ($this->isLegacyLifetimeAccess()) {
            return 'Lifetime Access';
        }

        if ($this->isAccessExpired()) {
            return 'Access expired';
        }

        $days = $this->getRemainingDays();

        if ($days === null || $days <= 0) {
            return 'Access expired';
        }

        return $days === 1 ? '1 day remaining' : "{$days} days remaining";
    }

    /**
     * Get formatted expiration date or null for legacy lifetime.
     */
    public function getFormattedExpiryDate(string $format = 'M d, Y'): ?string
    {
        if ($this->isLegacyLifetimeAccess() || $this->expires_at === null) {
            return null;
        }

        return $this->expires_at->format($format);
    }

    /**
     * Get formatted start date or null if not set.
     */
    public function getFormattedStartDate(string $format = 'M d, Y'): ?string
    {
        if ($this->starts_at === null) {
            return null;
        }

        return $this->starts_at->format($format);
    }

    /**
     * Check if renewal action is available for this enrollment.
     */
    public function canRenew(): bool
    {
        if ($this->status === EnrollmentStatus::CANCELLED || $this->isLegacyLifetimeAccess()) {
            return false;
        }

        return $this->isAccessExpired() || $this->isExpiringSoon(30);
    }

    /**
     * Get contextual renewal action button label ("Renew Early" or "Renew Access").
     */
    public function getRenewalCtaLabel(): string
    {
        if ($this->isExpiringSoon(30) && ! $this->isAccessExpired()) {
            return 'Renew Early';
        }

        return 'Renew Access';
    }

    /**
     * Get UI badge metadata (label, styling classes, state).
     *
     * @return array{label: string, color: string, bg_class: string, state: string}
     */
    public function getAccessBadgeDetails(): array
    {
        $state = $this->getAccessState();

        return match ($state) {
            'lifetime' => [
                'label' => 'Lifetime Access',
                'color' => 'indigo',
                'bg_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'state' => 'lifetime',
            ],
            'expiring' => [
                'label' => 'Expires Soon',
                'color' => 'amber',
                'bg_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'state' => 'expiring',
            ],
            'expired' => [
                'label' => 'Access Expired',
                'color' => 'rose',
                'bg_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'state' => 'expired',
            ],
            default => [
                'label' => 'Access Active',
                'color' => 'emerald',
                'bg_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'state' => 'active',
            ],
        };
    }

    /**
     * Check whether this enrollment has anomalous or inconsistent access state.
     */
    public function isAnomalousAccess(): bool
    {
        // Partial null dates (one null, the other set)
        if (($this->starts_at === null && $this->expires_at !== null) || ($this->starts_at !== null && $this->expires_at === null)) {
            return true;
        }

        // Inverted dates
        if ($this->starts_at !== null && $this->expires_at !== null && $this->starts_at->gt($this->expires_at)) {
            return true;
        }

        // Status active but date expired
        if ($this->status === EnrollmentStatus::ACTIVE && $this->expires_at !== null && now()->gte($this->expires_at)) {
            return true;
        }

        // Status expired but date future
        if ($this->status === EnrollmentStatus::EXPIRED && $this->expires_at !== null && now()->lt($this->expires_at)) {
            return true;
        }

        return false;
    }

    /**
     * Get the authoritative administrative access lifecycle state.
     */
    public function getAdminAccessState(): string
    {
        if ($this->status === EnrollmentStatus::CANCELLED) {
            return 'cancelled';
        }

        if ($this->isAnomalousAccess()) {
            return 'anomalous';
        }

        if ($this->isLegacyLifetimeAccess()) {
            return 'lifetime';
        }

        if ($this->isAccessExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon(30)) {
            return 'expiring';
        }

        if ($this->hasActiveAccess()) {
            return 'active';
        }

        return 'expired';
    }

    /**
     * Get admin badge metadata with dark theme Tailwind CSS classes.
     *
     * @return array{label: string, classes: string, state: string}
     */
    public function getAdminAccessBadgeDetails(): array
    {
        $state = $this->getAdminAccessState();

        return match ($state) {
            'lifetime' => [
                'label' => 'Legacy Lifetime',
                'classes' => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30',
                'state' => 'lifetime',
            ],
            'expiring' => [
                'label' => 'Expiring Soon',
                'classes' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                'state' => 'expiring',
            ],
            'expired' => [
                'label' => 'Access Expired',
                'classes' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                'state' => 'expired',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'classes' => 'bg-slate-700/50 text-slate-400 border border-slate-600/30',
                'state' => 'cancelled',
            ],
            'anomalous' => [
                'label' => 'Needs Review',
                'classes' => 'bg-purple-500/10 text-purple-400 border border-purple-500/30',
                'state' => 'anomalous',
            ],
            default => [
                'label' => 'Access Active',
                'classes' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                'state' => 'active',
            ],
        };
    }
}