<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            self::PUBLISHED => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::ARCHIVED => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        };
    }
}
