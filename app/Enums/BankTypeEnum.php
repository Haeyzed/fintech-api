<?php

namespace App\Enums;

enum BankTypeEnum: string
{
    case COMMERCIAL = 'commercial';
    case MICROFINANCE = 'microfinance';
    case SAVINGS = 'savings';
    case NUBAN = 'nuban';

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
