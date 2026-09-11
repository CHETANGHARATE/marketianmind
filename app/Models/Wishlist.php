<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'course_id',
])]
class Wishlist extends Model
{
    use HasFactory;

    /**
     * Get the student who saved this course.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the saved course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}