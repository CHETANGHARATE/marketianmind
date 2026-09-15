<?php

namespace App\Enums;

enum CoursePurchaseType: string
{
    case INITIAL_PURCHASE = 'initial_purchase';
    case RENEWAL = 'renewal';
    case NOT_ELIGIBLE = 'not_eligible';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL_PURCHASE => 'Initial Purchase',
            self::RENEWAL => 'Course Renewal',
            self::NOT_ELIGIBLE => 'Not Eligible',
        };
    }
}