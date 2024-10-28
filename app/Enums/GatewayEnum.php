<?php

namespace App\Enums;

enum GatewayEnum: string
{
    case NULL = '';
    case FLUTTERWAVE = 'flutterwave';
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';
    case EMANDATE = 'emandate';
    case IBAN = 'ibank';
    case DIGITAL_BANK_MANDATE = 'digitalbankmandate';

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
