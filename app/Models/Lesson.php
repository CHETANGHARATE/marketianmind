<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_module_id',
    'title',
    'slug',
    'description',
    'lesson_type',
    'video_url',
    'pdf_url',
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

    /**
     * Get the full URL for the lesson PDF document.
     */
    public function pdfUrl(): ?string
    {
        if (! $this->pdf_url) {
            return null;
        }

        if (str_starts_with($this->pdf_url, 'http://') || str_starts_with($this->pdf_url, 'https://')) {
            return $this->pdf_url;
        }

        return asset('storage/' . $this->pdf_url);
    }

    /**
     * Get all progress records for this lesson.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Check if a specific user has completed this lesson.
     */
    public function isCompletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->lessonProgress()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Get safe embed or direct URL for video lessons.
     */
    public function embedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        $url = trim($this->video_url);

        // YouTube format: youtube.com/watch?v=ID or youtu.be/ID
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
        }

        // Vimeo format: vimeo.com/ID
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)/i', $url, $matches)) {
            $vimeoId = end($matches);
            return 'https://player.vimeo.com/video/' . $vimeoId;
        }

        return $url;
    }

    /**
     * Check if the video URL is an embeddable iframe (YouTube or Vimeo).
     */
    public function isIframeVideo(): bool
    {
        $embed = $this->embedUrl();
        if (! $embed) {
            return false;
        }

        return str_contains($embed, 'youtube.com/embed') || str_contains($embed, 'player.vimeo.com/video');
    }

    /**
     * Get the previous published lesson in the course curriculum.
     */
    public function previousLesson(): ?self
    {
        $course = $this->module?->course;
        if (! $course) {
            return null;
        }

        $orderedLessons = self::query()
            ->where('lessons.status', LessonStatus::PUBLISHED->value)
            ->whereHas('module', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->join('course_modules', 'lessons.course_module_id', '=', 'course_modules.id')
            ->orderBy('course_modules.sort_order', 'asc')
            ->orderBy('lessons.sort_order', 'asc')
            ->orderBy('lessons.id', 'asc')
            ->select('lessons.*')
            ->get();

        $currentIndex = $orderedLessons->search(fn ($item) => $item->id === $this->id);

        if ($currentIndex !== false && $currentIndex > 0) {
            return $orderedLessons->get($currentIndex - 1);
        }

        return null;
    }

    /**
     * Get the next published lesson in the course curriculum.
     */
    public function nextLesson(): ?self
    {
        $course = $this->module?->course;
        if (! $course) {
            return null;
        }

        $orderedLessons = self::query()
            ->where('lessons.status', LessonStatus::PUBLISHED->value)
            ->whereHas('module', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->join('course_modules', 'lessons.course_module_id', '=', 'course_modules.id')
            ->orderBy('course_modules.sort_order', 'asc')
            ->orderBy('lessons.sort_order', 'asc')
            ->orderBy('lessons.id', 'asc')
            ->select('lessons.*')
            ->get();

        $currentIndex = $orderedLessons->search(fn ($item) => $item->id === $this->id);

        if ($currentIndex !== false && $currentIndex < $orderedLessons->count() - 1) {
            return $orderedLessons->get($currentIndex + 1);
        }

        return null;
    }

    /**
     * Retrieve the model for a bound value (supports ID or slug).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        return is_numeric($value)
            ? $this->where('id', $value)->first()
            : $this->where('slug', $value)->first();
    }
}

