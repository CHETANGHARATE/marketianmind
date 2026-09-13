<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case INTERESTED = 'interested';
    case FOLLOW_UP = 'follow_up';
    case CONVERTED = 'converted';
    case NOT_INTERESTED = 'not_interested';
    case LOST = 'lost';
    case CLOSED = 'closed';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::CONTACTED => 'Contacted',
            self::QUALIFIED => 'Qualified',
            self::INTERESTED => 'Interested',
            self::FOLLOW_UP => 'Follow Up',
            self::CONVERTED => 'Converted',
            self::NOT_INTERESTED => 'Not Interested',
            self::LOST => 'Lost',
            self::CLOSED => 'Closed',
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
            self::QUALIFIED => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
            self::INTERESTED => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
            self::FOLLOW_UP => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            self::CONVERTED => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::NOT_INTERESTED => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
            self::LOST => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
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