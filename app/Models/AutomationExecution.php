<?php

namespace App\Models;

use App\Enums\AutomationExecutionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'recipient_type',
        'recipient_id',
        'trigger_event',
        'reference_id',
        'channel',
        'whatsapp_message_id',
        'scheduled_at',
        'executed_at',
        'status',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'status' => AutomationExecutionStatus::class,
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * The automation rule.
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'automation_id');
    }

    /**
     * Associated WhatsApp message if channel is whatsapp.
     */
    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    /**
     * Resolve the recipient entity (Lead or User).
     */
    public function getRecipient(): Lead|User|null
    {
        if ($this->recipient_type === 'lead') {
            return Lead::find($this->recipient_id);
        }

        if ($this->recipient_type === 'user') {
            return User::find($this->recipient_id);
        }

        return null;
    }

    /**
     * Scope query to executions due for processing.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', AutomationExecutionStatus::PENDING->value)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope query to pending executions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AutomationExecutionStatus::PENDING->value);
    }

    /**
     * Scope query to sent executions.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', AutomationExecutionStatus::SENT->value);
    }
}
