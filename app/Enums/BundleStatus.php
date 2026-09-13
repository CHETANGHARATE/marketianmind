<?php

namespace App\Enums;

enum BundleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get a human-readable display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get Tailwind badge color class.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::PUBLISHED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::ARCHIVED => 'bg-slate-100 text-slate-700 border-slate-200',
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
