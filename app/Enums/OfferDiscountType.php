<?php

namespace App\Enums;

enum OfferDiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage (%)',
            self::FIXED => 'Fixed Amount (₹)',
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
