<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    /**
     * Get a human-readable display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Get Tailwind CSS badge classes for this status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
            self::COMPLETED => 'bg-blue-500/10 text-blue-400 border border-blue-500/30',
            self::CANCELLED => 'bg-slate-500/10 text-slate-400 border border-slate-500/30',
            self::EXPIRED => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
        };
    }

    /**
     * Get an array of all status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
