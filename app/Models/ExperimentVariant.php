<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'name',
        'key',
        'description',
        'is_control',
        'allocation_percentage',
        'weight',
        'configuration',
        'config',
        'status',
    ];

    protected $casts = [
        'is_control' => 'boolean',
        'allocation_percentage' => 'integer',
        'weight' => 'integer',
        'configuration' => 'array',
        'config' => 'array',
    ];

    public function getWeightAttribute($value): int
    {
        return $value ?? $this->allocation_percentage ?? 50;
    }

    public function getConfigAttribute($value): ?array
    {
        $raw = $value ?? $this->configuration;
        if ($raw === null) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return (array) $raw;
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class, 'variant_id');
    }

    public function conversionEvents(): HasMany
    {
        return $this->hasMany(ConversionEvent::class, 'variant_id');
    }

    /**
     * Get safe configuration value with guaranteed fallback.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }
}
