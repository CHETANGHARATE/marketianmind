<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'event_id',
    'event_type',
    'payload',
    'status',
    'processed_at',
])]
class RazorpayWebhookEvent extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'received',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed' && $this->processed_at !== null;
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }
}