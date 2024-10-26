<?php

namespace App\Enums;

enum AccountTypeEnum: string
{
    case SAVINGS = 'savings';
    case BUSINESS = 'business';
    case CURRENT = 'current';

    /**
     * Get all enum values as an array.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
