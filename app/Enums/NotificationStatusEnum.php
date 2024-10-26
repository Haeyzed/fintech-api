<?php

namespace App\Enums;

enum NotificationStatusEnum: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
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
