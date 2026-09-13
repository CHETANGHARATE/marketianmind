<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingUnsubscribe extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'reason',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Check if an email address is unsubscribed from marketing communications.
     */
    public static function isUnsubscribed(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return static::where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Record an email as unsubscribed.
     */
    public static function recordUnsubscribe(string $email, ?string $reason = null): self
    {
        $normalized = strtolower(trim($email));

        return static::firstOrCreate(
            ['email' => $normalized],
            [
                'reason' => $reason,
                'unsubscribed_at' => now(),
            ]
        );
    }
}
