<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'description',
    'icon',
    'badge_color',
    'requirement_type',
    'requirement_value',
    'points',
    'is_active',
    'sort_order',
])]
class Achievement extends Model
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
            'requirement_value' => 'integer',
            'points' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get all user achievements pivot records.
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get all users who earned this achievement.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    /**
     * Check if a specific user has unlocked this achievement.
     */
    public function isUnlockedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->users()->where('users.id', $user->id)->exists();
    }
}
