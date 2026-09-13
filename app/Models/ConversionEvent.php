<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_name',
        'user_id',
        'lead_id',
        'session_id',
        'anonymous_id',
        'course_id',
        'bundle_id',
        'experiment_id',
        'variant_id',
        'url_path',
        'referrer',
        'metadata',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class);
    }

    public function scopeNamed(Builder $query, string $eventName): Builder
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeDateRange(Builder $query, $start, $end): Builder
    {
        if ($start) {
            $query->where('occurred_at', '>=', $start);
        }
        if ($end) {
            $query->where('occurred_at', '<=', $end);
        }
        return $query;
    }
}
