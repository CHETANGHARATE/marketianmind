<?php

namespace App\Enums;

enum AutomationStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Badge classes for UI display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 ring-slate-600/20 border-slate-200',
            self::ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
            self::PAUSED => 'bg-amber-50 text-amber-700 ring-amber-600/20 border-amber-200',
            self::ARCHIVED => 'bg-rose-50 text-rose-700 ring-rose-600/20 border-rose-200',
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
