<?php

namespace App\Enums;

enum WhatsAppMessageStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::QUEUED => 'Queued',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::READ => 'Read',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Badge classes for UI display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::QUEUED => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            self::SENT => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
            self::DELIVERED => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::READ => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
            self::FAILED => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            self::CANCELLED => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
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
