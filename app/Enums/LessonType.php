<?php

namespace App\Enums;

enum LessonType: string
{
    case VIDEO = 'video';
    case TEXT = 'text';
    case PDF = 'pdf';

    /**
     * Get a human-readable display label for the lesson type.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIDEO => 'Video',
            self::TEXT => 'Text / Article',
            self::PDF => 'PDF Resource',
        };
    }

    /**
     * Get an array of all lesson type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
