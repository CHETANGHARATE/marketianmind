<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'admin_name',
    'action',
    'auditable_type',
    'auditable_id',
    'resource_label',
    'description',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
])]
class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the admin user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get Tailwind badge classes based on action type.
     */
    public function getActionBadgeClassesAttribute(): string
    {
        return match (strtolower($this->action)) {
            'created' => 'bg-emerald-500/10 text-emerald-400 ring-1 ring-inset ring-emerald-500/20',
            'updated' => 'bg-sky-500/10 text-sky-400 ring-1 ring-inset ring-sky-500/20',
            'published' => 'bg-amber-500/10 text-amber-400 ring-1 ring-inset ring-amber-500/20',
            'unpublished' => 'bg-purple-500/10 text-purple-400 ring-1 ring-inset ring-purple-500/20',
            'deleted' => 'bg-rose-500/10 text-rose-400 ring-1 ring-inset ring-rose-500/20',
            default => 'bg-slate-500/10 text-slate-400 ring-1 ring-inset ring-slate-500/20',
        };
    }

    /**
     * Get human-readable resource type label.
     */
    public function getResourceTypeLabelAttribute(): string
    {
        return match (class_basename($this->auditable_type)) {
            'Course' => 'Course',
            'CourseCategory' => 'Course Category',
            'CourseModule' => 'Curriculum Module',
            'Lesson' => 'Lesson',
            'User' => 'Student',
            'Enrollment' => 'Enrollment',
            'Order' => 'Order',
            'Certificate' => 'Certificate',
            default => class_basename($this->auditable_type),
        };
    }
}