<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';

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
