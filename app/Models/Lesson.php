<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_module_id',
    'title',
    'slug',
    'description',
    'lesson_type',
    'video_url',
    'content',
    'duration',
    'is_preview',
    'sort_order',
    'status',
])]
class Lesson extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'lesson_type' => 'video',
        'is_preview' => false,
        'sort_order' => 0,
        'status' => 'draft',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_type' => LessonType::class,
            'status' => LessonStatus::class,
            'is_preview' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the course module that this lesson belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /**
     * Alias for module relation.
     */
    public function courseModule(): BelongsTo
    {
        return $this->module();
    }

    /**
     * Scope a query to only include published lessons.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LessonStatus::PUBLISHED->value);
    }

    /**
     * Scope a query to only include preview lessons.
     */
    public function scopePreview(Builder $query): Builder
    {
        return $query->where('is_preview', true);
    }

    /**
     * Scope a query to order lessons by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Check if the lesson is a free preview.
     */
    public function isPreview(): bool
    {
        return $this->is_preview;
    }

    /**
     * Check if the lesson is published.
     */
    public function isPublished(): bool
    {
        return $this->status === LessonStatus::PUBLISHED;
    }

    /**
     * Check if this is a video lesson.
     */
    public function isVideo(): bool
    {
        return $this->lesson_type === LessonType::VIDEO;
    }

    /**
     * Check if this is a text lesson.
     */
    public function isText(): bool
    {
        return $this->lesson_type === LessonType::TEXT;
    }

    /**
     * Check if this is a PDF resource lesson.
     */
    public function isPdf(): bool
    {
        return $this->lesson_type === LessonType::PDF;
    }
}
