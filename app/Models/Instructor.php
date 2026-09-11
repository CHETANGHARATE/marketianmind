<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'slug',
    'title',
    'bio',
    'avatar',
    'website_url',
    'linkedin_url',
    'twitter_url',
    'is_active',
])]
class Instructor extends Model
{
    use HasFactory;

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Courses taught by this instructor.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Scope a query to only include active instructors.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the instructor avatar URL or fallback to UI avatar.
     */
    public function avatarUrl(): string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . $this->avatar);
        }

        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=4F46E5&background=EEF2FF&font-size=0.45&bold=true";
    }

    /**
     * Extract 2-letter initials from instructor name.
     */
    public function initials(): string
    {
        $words = explode(' ', trim($this->name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_substr($word, 0, 1);
        }
        return strtoupper($initials) ?: 'IN';
    }

    /**
     * Get count of published courses taught by this instructor.
     */
    public function publishedCoursesCount(): int
    {
        return $this->courses()->published()->count();
    }

    /**
     * Delete stored avatar file if it exists.
     */
    public function deleteStoredAvatar(): void
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            Storage::disk('public')->delete($this->avatar);
        }
    }
}