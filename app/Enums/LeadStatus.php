<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case CONVERTED = 'converted';
    case CLOSED = 'closed';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Inquiry',
            self::CONTACTED => 'Contacted',
            self::CONVERTED => 'Converted to Student',
            self::CLOSED => 'Closed / Inactive',
        };
    }

    /**
     * Tailwind CSS badge styling classes.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::NEW => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::CONTACTED => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            self::CONVERTED => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::CLOSED => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
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