<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

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
