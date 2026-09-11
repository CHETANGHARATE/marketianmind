<?php

namespace App\Enums;

enum CourseReviewStatus: string
{
    case APPROVED = 'approved';
    case HIDDEN = 'hidden';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::HIDDEN => 'Hidden',
        };
    }
}