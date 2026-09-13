<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentExposure extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'user_id',
        'anonymous_id',
        'visitor_hash',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
