<?php

namespace App\Enums;

enum CouponDiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    /**
     * Get a user-friendly label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage (%)',
            self::FIXED => 'Fixed Amount (₹)',
        };
    }
}