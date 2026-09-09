<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'course_category_id',
    'title',
    'slug',
    'short_description',
    'description',
    'thumbnail',
    'instructor_name',
    'price',
    'discount_price',
    'is_free',
    'status',
    'featured',
    'estimated_duration',
])]
class Course extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price' => 0.00,
        'is_free' => false,
        'status' => 'draft',
        'featured' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_free' => 'boolean',
            'status' => CourseStatus::class,
            'featured' => 'boolean',
        ];
    }

    /**
     * Get the category that this course belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    /**
     * Alias for category relation.
     */
    public function courseCategory(): BelongsTo
    {
        return $this->category();
    }

    /**
     * Get all modules for the course ordered by sort order.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'course_id')->orderBy('sort_order');
    }

    /**
     * Alias for modules relation.
     */
    public function courseModules(): HasMany
    {
        return $this->modules();
    }

    /**
     * Get all lessons through course modules.
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, CourseModule::class, 'course_id', 'course_module_id');
    }

    /**
     * Scope a query to only include published courses.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('courses.status', CourseStatus::PUBLISHED->value);
    }

    /**
     * Scope a query to only include featured courses.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('courses.featured', true);
    }

    /**
     * Scope a query to only include free courses.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('courses.is_free', true);
    }

    /**
     * Scope a query to only include paid courses.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('courses.is_free', false);
    }

    /**
     * Check if the course is published.
     */
    public function isPublished(): bool
    {
        return $this->status === CourseStatus::PUBLISHED;
    }

    /**
     * Check if the course is in draft mode.
     */
    public function isDraft(): bool
    {
        return $this->status === CourseStatus::DRAFT;
    }

    /**
     * Check if the course is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === CourseStatus::ARCHIVED;
    }

    /**
     * Determine if this course has a valid discount price.
     */
    public function hasDiscount(): bool
    {
        return ! $this->is_free
            && $this->discount_price !== null
            && (float) $this->discount_price < (float) $this->price;
    }

    /**
     * Get the effective purchase price for the course.
     */
    public function effectivePrice(): float
    {
        if ($this->is_free) {
            return 0.00;
        }

        if ($this->hasDiscount()) {
            return (float) $this->discount_price;
        }

        return (float) $this->price;
    }

    /**
     * Get the effective purchase price in paise (integer).
     */
    public function effectivePriceInPaise(): int
    {
        return (int) round($this->effectivePrice() * 100);
    }

    /**
     * Get the full URL for the course thumbnail.
     */
    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://')) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }

    /**
     * Get all enrollments for this course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all orders placed for this course.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all users enrolled in this course.
     */
    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Check if a specific user is enrolled in this course.
     */
    public function isEnrolledBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', [EnrollmentStatus::ACTIVE->value, EnrollmentStatus::COMPLETED->value])
            ->exists();
    }

    /**
     * Get learning progress metrics for a specific user.
     *
     * @return array{total: int, completed: int, percentage: int, is_completed: bool}
     */
    public function progressFor(?User $user): array
    {
        if (! $user) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'is_completed' => false,
            ];
        }

        $publishedLessonIds = Lesson::query()
            ->where('status', LessonStatus::PUBLISHED->value)
            ->whereHas('module', function (Builder $query) {
                $query->where('course_id', $this->id);
            })
            ->pluck('id');

        $total = $publishedLessonIds->count();
        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'is_completed' => false,
            ];
        }

        $completed = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $publishedLessonIds)
            ->where('completed', true)
            ->count();

        $percentage = (int) round(($completed / $total) * 100);

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => min(100, $percentage),
            'is_completed' => $completed >= $total && $total > 0,
        ];
    }

    /**
     * Find the next incomplete lesson for a user, or the first lesson.
     */
    public function nextLessonFor(?User $user): ?Lesson
    {
        $modules = $this->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => function ($query) {
                $query->where('status', LessonStatus::PUBLISHED->value)
                    ->orderBy('sort_order');
            }])
            ->get();

        if ($modules->isEmpty()) {
            return null;
        }

        if (! $user) {
            return $modules->first()->lessons->first();
        }

        $completedLessonIds = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->flip();

        foreach ($modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (! isset($completedLessonIds[$lesson->id])) {
                    return $lesson;
                }
            }
        }

        return $modules->first()->lessons->first();
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

