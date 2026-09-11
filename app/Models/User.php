<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'referral_code'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Determine if the user has an administrator role.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Determine if the user has a student role.
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }

    /**
     * Determine if the user has the specified role.
     */
    public function hasRole(string|UserRole $role): bool
    {
        if ($role instanceof UserRole) {
            return $this->role === $role;
        }

        return $this->role?->value === $role || $this->role === $role;
    }

    /**
     * Get the default dashboard route name for the user based on role.
     */
    public function dashboardRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'student.dashboard';
    }

    /**
     * Get the default dashboard URL for the user based on role.
     */
    public function dashboardUrl(): string
    {
        return route($this->dashboardRoute());
    }

    /**
     * Get all enrollments for the user.
     */
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all enrolled courses for the user.
     */
    public function enrolledCourses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * Get all lesson progress records for the user.
     */
    public function lessonProgress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Check if the user is enrolled in a specific course.
     */
    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', [\App\Enums\EnrollmentStatus::ACTIVE->value, \App\Enums\EnrollmentStatus::COMPLETED->value])
            ->exists();
    }

    /**
     * Get all orders placed by this user.
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all payments made by this user.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all certificates issued to this user.
     */
    public function certificates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Certificate::class);
    }
    /**
     * Get all course reviews submitted by this user.
     */
    public function courseReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseReview::class);
    }
    /**
     * Get all wishlist records for the user.
     */
    public function wishlists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get all courses saved in the user's wishlist.
     */
    public function wishlistCourses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'wishlists')
            ->withTimestamps();
    }

    /**
     * Check if a specific course is in the user's wishlist.
     */
    public function hasInWishlist(Course $course): bool
    {
        return $this->wishlists()->where('course_id', $course->id)->exists();
    }

    /**
     * Add a course to the user's wishlist.
     */
    public function addToWishlist(Course $course): Wishlist
    {
        return $this->wishlists()->firstOrCreate(['course_id' => $course->id]);
    }

    /**
     * Remove a course from the user's wishlist.
     */
    public function removeFromWishlist(Course $course): bool
    {
        return (bool) $this->wishlists()->where('course_id', $course->id)->delete();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateUniqueReferralCode();
            }
        });
    }

    /**
     * Generate a collision-resistant referral code.
     */
    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = 'MM' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get or generate the referral code for this user.
     */
    public function getReferralCode(): string
    {
        if (empty($this->referral_code)) {
            $this->referral_code = static::generateUniqueReferralCode();
            $this->saveQuietly();
        }

        return $this->referral_code;
    }

    /**
     * Get the user's public referral URL.
     */
    public function referralUrl(): string
    {
        return route('referral.capture', ['code' => $this->getReferralCode()]);
    }

    /**
     * Referrals where this user was the referrer.
     */
    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Referral record where this user was the referred student.
     */
    public function referralAttribution(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }
}