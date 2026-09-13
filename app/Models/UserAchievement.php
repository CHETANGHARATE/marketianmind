<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'achievement_id',
    'earned_at',
])]
class UserAchievement extends Model
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
            'earned_at' => 'datetime',
        ];
    }

    /**
     * Get the student who earned this achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement definition.
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
