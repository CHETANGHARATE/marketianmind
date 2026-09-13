<?php

namespace App\Enums;

enum ExperimentStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';

    public const RUNNING = self::ACTIVE;

    public function badgeClass(): string
    {
        return $this->badgeClasses();
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::COMPLETED => 'Completed',
            self::ARCHIVED => 'Archived',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            self::ACTIVE => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::PAUSED => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::COMPLETED => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            self::ARCHIVED => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        };
    }
}
