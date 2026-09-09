<?php

namespace App\Enums;

enum UserRole: string
{
    case STUDENT = 'student';
    case ADMIN = 'admin';

    /**
     * Get a human-readable display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Student',
            self::ADMIN => 'Administrator',
        };
    }

    /**
     * Get an array of all role values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}