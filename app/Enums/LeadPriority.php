<?php

namespace App\Enums;

enum LeadPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Tailwind CSS badge styling classes.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            self::MEDIUM => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            self::HIGH => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::URGENT => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        };
    }

    /**
     * Return all values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
