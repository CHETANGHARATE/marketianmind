<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CREATED = 'created';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    /**
     * Get a human-readable display label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::AUTHORIZED => 'Authorized',
            self::CAPTURED => 'Captured / Success',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get Tailwind CSS badge styling classes for the status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::CREATED => 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/20',
            self::AUTHORIZED => 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20',
            self::CAPTURED => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
            self::FAILED => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20',
            self::REFUNDED => 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20',
        };
    }

    /**
     * Get an array of all payment status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}