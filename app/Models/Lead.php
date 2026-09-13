<?php

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'phone_normalized',
        'whatsapp_opt_in',
        'whatsapp_opted_in_at',
        'whatsapp_opted_out_at',
        'whatsapp_consent_source',
        'company_name',
        'job_title',
        'city',
        'state',
        'country',
        'course_id',
        'bundle_id',
        'source',
        'subject',
        'message',
        'status',
        'priority',
        'assigned_to',
        'notes',
        'next_follow_up_at',
        'follow_up_status',
        'last_contacted_at',
        'converted_at',
        'converted_user_id',
        'ip_address',
    ];

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
        'priority' => 'medium',
        'country' => 'India',
        'source' => 'website',
        'whatsapp_opt_in' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => LeadStatus::class,
        'priority' => LeadPriority::class,
        'next_follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
        'whatsapp_opt_in' => 'boolean',
        'whatsapp_opted_in_at' => 'datetime',
        'whatsapp_opted_out_at' => 'datetime',
    ];

    /**
     * Target course inquiry, if any.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Target bundle inquiry, if any.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /**
     * Internal admin/staff user assigned to manage this lead.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Registered student user this lead converted into.
     */
    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    /**
     * Historical internal CRM notes.
     */
    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    /**
     * Alias for leadNotes relationship.
     */
    public function notes(): HasMany
    {
        return $this->leadNotes();
    }

    /**
     * Historical lead activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
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
     * Scope query by priority string or enum.
     */
    public function scopeWithPriority(Builder $query, LeadPriority|string $priority): Builder
    {
        $val = $priority instanceof LeadPriority ? $priority->value : $priority;
        return $query->where('priority', $val);
    }

    /**
     * Scope query to leads assigned to a specific user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope query to overdue follow-ups.
     */
    public function scopeOverdueFollowUps(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->where(function ($q) {
                $q->whereNull('follow_up_status')
                  ->orWhere('follow_up_status', '!=', 'completed');
            })
            ->whereNotIn('status', [LeadStatus::CONVERTED->value, LeadStatus::LOST->value, LeadStatus::CLOSED->value]);
    }

    /**
     * Scope query to follow-ups scheduled for today.
     */
    public function scopeDueTodayFollowUps(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')
            ->whereDate('next_follow_up_at', today())
            ->where(function ($q) {
                $q->whereNull('follow_up_status')
                  ->orWhere('follow_up_status', '!=', 'completed');
            })
            ->whereNotIn('status', [LeadStatus::CONVERTED->value, LeadStatus::LOST->value, LeadStatus::CLOSED->value]);
    }

    /**
     * Scope query to upcoming scheduled follow-ups.
     */
    public function scopeUpcomingFollowUps(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('follow_up_status')
                  ->orWhere('follow_up_status', '!=', 'completed');
            });
    }

    /**
     * Search leads across name, email, phone, company, and source.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim($term ?? '');

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('company_name', 'like', "%{$term}%")
              ->orWhere('source', 'like', "%{$term}%")
              ->orWhere('subject', 'like', "%{$term}%");
        });
    }

    /**
     * Filter leads by query parameters.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status']) && in_array($filters['status'], LeadStatus::values(), true)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority']) && in_array($filters['priority'], LeadPriority::values(), true)) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (! empty($filters['bundle_id'])) {
            $query->where('bundle_id', $filters['bundle_id']);
        }

        if (! empty($filters['follow_up'])) {
            if ($filters['follow_up'] === 'today') {
                $query->dueTodayFollowUps();
            } elseif ($filters['follow_up'] === 'overdue') {
                $query->overdueFollowUps();
            } elseif ($filters['follow_up'] === 'upcoming') {
                $query->upcomingFollowUps();
            }
        }

        return $query;
    }

    /**
     * Check if the lead's email corresponds to an existing registered user/student.
     */
    public function matchedUser(): ?User
    {
        return User::where('email', $this->email)->first();
    }

    /**
     * Check if this lead is already converted.
     */
    public function isConverted(): bool
    {
        return $this->status === LeadStatus::CONVERTED || ! is_null($this->converted_user_id);
    }

    /**
     * Check if this lead has an overdue follow-up.
     */
    public function isOverdue(): bool
    {
        return $this->next_follow_up_at !== null
            && $this->next_follow_up_at->isPast()
            && $this->follow_up_status !== 'completed'
            && ! in_array($this->status, [LeadStatus::CONVERTED, LeadStatus::LOST, LeadStatus::CLOSED], true);
    }

    /**
     * Check if this lead's follow-up is scheduled for today.
     */
    public function isDueToday(): bool
    {
        return $this->next_follow_up_at !== null
            && $this->next_follow_up_at->isToday()
            && $this->follow_up_status !== 'completed'
            && ! in_array($this->status, [LeadStatus::CONVERTED, LeadStatus::LOST, LeadStatus::CLOSED], true);
    }

    /**
     * Record a historical activity entry for this lead.
     */
    public function recordActivity(string $type, string $description, ?array $properties = null, ?User $actor = null): LeadActivity
    {
        $userId = $actor ? $actor->id : auth()->id();

        return $this->activities()->create([
            'user_id' => $userId,
            'activity_type' => $type,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    /**
     * Add an internal note to this lead.
     */
    public function addNote(string $content, ?User $author = null): LeadNote
    {
        $userId = $author ? $author->id : auth()->id();

        $note = $this->notes()->create([
            'user_id' => $userId,
            'content' => $content,
        ]);

        $this->recordActivity('note_added', 'Added an internal note', ['note_id' => $note->id], $author);

        return $note;
    }

    /**
     * Associated WhatsApp messages.
     */
    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    /**
     * Check if this lead has opted in to WhatsApp marketing messages.
     */
    public function hasWhatsAppOptIn(): bool
    {
        return (bool) $this->whatsapp_opt_in;
    }

    /**
     * Record WhatsApp marketing opt-in.
     */
    public function recordWhatsAppOptIn(string $source = 'lead_capture'): void
    {
        $this->update([
            'whatsapp_opt_in' => true,
            'whatsapp_opted_in_at' => now(),
            'whatsapp_consent_source' => $source,
        ]);
    }

    /**
     * Record WhatsApp marketing opt-out.
     */
    public function recordWhatsAppOptOut(): void
    {
        $this->update([
            'whatsapp_opt_in' => false,
            'whatsapp_opted_out_at' => now(),
        ]);
    }
}
