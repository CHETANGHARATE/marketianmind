<?php

namespace App\Models;

use App\Enums\WhatsAppMessageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'user_id',
        'lead_id',
        'phone_number',
        'phone_normalized',
        'direction',
        'message_type',
        'template_id',
        'template_name',
        'template_language',
        'provider_message_id',
        'status',
        'idempotency_key',
        'is_marketing',
        'error_code',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => WhatsAppMessageStatus::class,
        'metadata' => 'array',
        'is_marketing' => 'boolean',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function getSuccessAttribute(): bool
    {
        return in_array($this->status, [
            WhatsAppMessageStatus::SENT,
            WhatsAppMessageStatus::DELIVERED,
            WhatsAppMessageStatus::READ,
        ], true);
    }

    public function getErrorAttribute(): ?string
    {
        return $this->error_message;
    }

    public function getMessageAttribute(): self
    {
        return $this;
    }

    /**
     * Associated user/student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Associated lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Associated WhatsApp template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }

    /**
     * Get the human recipient entity (Lead or User).
     */
    public function getRecipient(): Lead|User|null
    {
        if ($this->lead_id) {
            return $this->lead;
        }

        if ($this->user_id) {
            return $this->user;
        }

        return null;
    }

    /**
     * Scope outbound messages.
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope inbound messages.
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', 'inbound');
    }
}
