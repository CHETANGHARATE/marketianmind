<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'status'])]
class CourseCategory extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
        ];
    }

    /**
     * Get courses belonging to this category.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'course_category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::ACTIVE->value);
    }

    /**
     * Check if the category is active.
     */
    public function isActive(): bool
    {
        return $this->status === CategoryStatus::ACTIVE;
    }
}
