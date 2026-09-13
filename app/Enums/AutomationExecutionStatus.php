<?php

namespace App\Enums;

enum AutomationExecutionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SENT => 'Sent',
            self::SKIPPED => 'Skipped',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Badge classes for UI display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 ring-amber-600/20 border-amber-200',
            self::PROCESSING => 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200',
            self::SENT => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
            self::SKIPPED => 'bg-slate-100 text-slate-700 ring-slate-600/20 border-slate-200',
            self::FAILED => 'bg-rose-50 text-rose-700 ring-rose-600/20 border-rose-200',
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
