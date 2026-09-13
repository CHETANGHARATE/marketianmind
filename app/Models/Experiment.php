<?php

namespace App\Models;

use App\Enums\ExperimentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'status',
        'target',
        'target_audience',
        'target_id',
        'traffic_percentage',
        'start_at',
        'end_at',
        'started_at',
        'ended_at',
        'primary_metric',
        'winning_variant_id',
        'created_by',
    ];

    protected $casts = [
        'status' => ExperimentStatus::class,
        'traffic_percentage' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class);
    }

    public function controlVariant(): HasOne
    {
        return $this->hasOne(ExperimentVariant::class)->where('is_control', true);
    }

    public function winningVariant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'winning_variant_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ExperimentStatus::DRAFT;
    }

    public function isRunning(): bool
    {
        return $this->status === ExperimentStatus::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === ExperimentStatus::PAUSED;
    }

    public function isCompleted(): bool
    {
        return $this->status === ExperimentStatus::COMPLETED;
    }

    public function isArchived(): bool
    {
        return $this->status === ExperimentStatus::ARCHIVED;
    }

    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class);
    }

    public function conversionEvents(): HasMany
    {
        return $this->hasMany(ConversionEvent::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ConversionEvent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        if ($this->status !== ExperimentStatus::ACTIVE) {
            return false;
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        return true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ExperimentStatus::ACTIVE->value)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }

    public function scopeByTarget(Builder $query, string $target, ?int $targetId = null): Builder
    {
        return $query->where('target', $target)
            ->where(function ($q) use ($targetId) {
                if ($targetId !== null) {
                    $q->where('target_id', $targetId)->orWhereNull('target_id');
                } else {
                    $q->whereNull('target_id');
                }
            });
    }

    /**
     * Validate that allocations total <= 100 and control variant exists.
     */
    public function validateAllocations(): bool
    {
        $variants = $this->variants;
        if ($variants->isEmpty()) {
            return false;
        }

        $hasControl = $variants->contains(fn($v) => $v->is_control);
        if (!$hasControl) {
            return false;
        }

        $totalAllocation = $variants->sum('allocation_percentage');
        return $totalAllocation <= 100;
    }

    /**
     * Calculate descriptive experiment results per variant.
     */
    public function calculateResults(): array
    {
        $metric = $this->primary_metric;
        $variants = $this->variants()->withCount('exposures')->get();

        $results = [];
        $controlRate = 0.0;

        foreach ($variants as $variant) {
            $exposures = $variant->exposures_count;

            $conversions = ConversionEvent::where('experiment_id', $this->id)
                ->where('variant_id', $variant->id)
                ->where('event_name', $metric)
                ->count();

            $rate = $exposures > 0 ? round(($conversions / $exposures) * 100, 2) : 0.0;

            if ($variant->is_control) {
                $controlRate = $rate;
            }

            $results[$variant->id] = [
                'variant' => $variant,
                'exposures' => $exposures,
                'conversions' => $conversions,
                'conversion_rate' => $rate,
                'difference_pct' => 0.0,
                'comparison_label' => 'Baseline',
            ];
        }

        // Compare variants to control
        foreach ($results as $id => &$item) {
            if ($item['variant']->is_control) {
                continue;
            }

            if ($controlRate > 0) {
                $diff = round((($item['conversion_rate'] - $controlRate) / $controlRate) * 100, 1);
                $item['difference_pct'] = $diff;
                if ($diff > 0) {
                    $item['comparison_label'] = "+{$diff}% (Currently higher conversion)";
                } elseif ($diff < 0) {
                    $item['comparison_label'] = "{$diff}% (Currently lower conversion)";
                } else {
                    $item['comparison_label'] = "Same as control";
                }
            } else {
                if ($item['conversion_rate'] > 0) {
                    $item['comparison_label'] = "Currently higher conversion";
                } else {
                    $item['comparison_label'] = "No conversions recorded";
                }
            }
        }

        return $results;
    }
}
