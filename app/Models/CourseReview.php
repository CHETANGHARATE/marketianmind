<?php

namespace App\Models;

use App\Enums\CourseReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_id',
    'user_id',
    'rating',
    'review',
    'status',
    'admin_notes',
])]
class CourseReview extends Model
{
    use HasFactory;

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'approved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => CourseReviewStatus::class,
        ];
    }

    /**
     * Get the course that this review belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the student author of this review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to only approved reviews visible to the public.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CourseReviewStatus::APPROVED->value);
    }

    /**
     * Scope to hidden reviews.
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('status', CourseReviewStatus::HIDDEN->value);
    }

    public function isApproved(): bool
    {
        return $this->status === CourseReviewStatus::APPROVED;
    }

    public function isHidden(): bool
    {
        return $this->status === CourseReviewStatus::HIDDEN;
    }
}