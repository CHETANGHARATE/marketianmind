<?php

namespace App\Enums;

enum OfferStatus: string
{
    case ACTIVE = 'active';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
    case DISABLED = 'disabled';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SCHEDULED => 'Scheduled',
            self::EXPIRED => 'Expired',
            self::DISABLED => 'Disabled',
        };
    }

    /**
     * Badge classes for UI display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
            self::SCHEDULED => 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200',
            self::EXPIRED => 'bg-amber-50 text-amber-700 ring-amber-600/20 border-amber-200',
            self::DISABLED => 'bg-slate-100 text-slate-700 ring-slate-600/20 border-slate-200',
        };
    }

    /**
     * Get all possible values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
