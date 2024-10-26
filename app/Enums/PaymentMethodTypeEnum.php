<?php

namespace App\Enums;

enum PaymentMethodTypeEnum: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';

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
