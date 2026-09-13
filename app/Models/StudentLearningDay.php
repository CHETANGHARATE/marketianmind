<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'activity_date',
    'activity_type',
])]
class StudentLearningDay extends Model
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
            'activity_date' => 'string',
        ];
    }

    /**
     * Get the student who owns this learning day.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
