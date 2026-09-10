<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'course_id',
    'enrollment_id',
    'certificate_number',
    'course_title',
    'student_name',
    'instructor_name',
    'course_completion_date',
    'issued_at',
    'metadata',
])]
class Certificate extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'course_completion_date' => 'datetime',
            'issued_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the student that owns this certificate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course for which this certificate was issued.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the enrollment record tied to this certificate.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Generate a robust, collision-free, human-readable certificate number.
     * Format: MM-{YYYY}-{8_CHAR_ALPHANUMERIC}
     */
    public static function generateCertificateNumber(): string
    {
        do {
            $number = 'MM-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (static::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Idempotently issue a certificate for an eligible completed course.
     */
    public static function issueFor(User $user, Course $course, Enrollment $enrollment): ?self
    {
        // Check if certificate was already issued for this user and course
        $existing = static::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Server-side verification: Course must be genuinely completed
        $progress = $course->progressFor($user);
        if (! $progress['is_completed']) {
            return null;
        }

        // Verification: Enrollment must belong to user and course
        if ((int) $enrollment->user_id !== (int) $user->id || (int) $enrollment->course_id !== (int) $course->id) {
            return null;
        }

        return DB::transaction(function () use ($user, $course, $enrollment, $progress) {
            // Lock and double-check to prevent concurrent race conditions
            $existing = static::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return static::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'certificate_number' => static::generateCertificateNumber(),
                'course_title' => $course->title,
                'student_name' => $user->name,
                'instructor_name' => $course->instructor_name,
                'course_completion_date' => $enrollment->completed_at ?? now(),
                'issued_at' => now(),
                'metadata' => [
                    'total_lessons' => $progress['total'],
                    'duration' => $course->estimated_duration,
                ],
            ]);
        });
    }

    /**
     * Check if a specific user owns this certificate.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }
}