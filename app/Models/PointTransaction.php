<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'points',
    'event_type',
    'reference_type',
    'reference_id',
    'description',
])]
class PointTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    /**
     * Get the student who owns this point transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referenced model (Lesson, Course, Achievement, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
