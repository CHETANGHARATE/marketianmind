<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'course_id',
    'type',
    'channel',
    'metadata',
    'sent_at',
])]
class EngagementLog extends Model
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
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the student associated with this engagement log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course associated with this engagement log.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
