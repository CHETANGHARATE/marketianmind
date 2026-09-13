<?php

namespace App\Enums;

enum WhatsAppTemplateCategory: string
{
    case MARKETING = 'marketing';
    case UTILITY = 'utility';
    case AUTHENTICATION = 'authentication';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::MARKETING => 'Marketing',
            self::UTILITY => 'Utility',
            self::AUTHENTICATION => 'Authentication',
        };
    }

    /**
     * Badge classes for UI display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::MARKETING => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
            self::UTILITY => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            self::AUTHENTICATION => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
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
