<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'course_id',
        'source',
        'subject',
        'message',
        'status',
        'notes',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => LeadStatus::class,
    ];

    /**
     * Belongs-to relationship with Course (optional inquiry target).
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope query to new leads only.
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', LeadStatus::NEW->value);
    }

    /**
     * Scope query by status string or enum.
     */
    public function scopeWithStatus(Builder $query, LeadStatus|string $status): Builder
    {
        $val = $status instanceof LeadStatus ? $status->value : $status;
        return $query->where('status', $val);
    }

    /**
     * Check if the lead's email corresponds to an existing registered user/student.
     */
    public function matchedUser(): ?User
    {
        return User::where('email', $this->email)->first();
    }
}