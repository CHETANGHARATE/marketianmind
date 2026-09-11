<?php

namespace App\Models;

use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'status',
        'order_id',
        'converted_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'converted_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', ReferralStatus::CONVERTED->value);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', ReferralStatus::REGISTERED->value);
    }

    public function isConverted(): bool
    {
        return $this->status === ReferralStatus::CONVERTED;
    }

    public function markConverted(Order $order): void
    {
        $this->update([
            'status' => ReferralStatus::CONVERTED,
            'order_id' => $order->id,
            'converted_at' => now(),
        ]);
    }
}