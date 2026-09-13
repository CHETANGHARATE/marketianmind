<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'provider',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
