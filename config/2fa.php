<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 2FA Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your 2FA settings.
    |
    */

    'enabled' => env('2FA_ENABLED', true),

    'issuer' => env('2FA_ISSUER', config('app.name')),

    'digits' => env('2FA_DIGITS', 6),

    'seconds' => env('2FA_SECONDS', 30),

    'window' => env('2FA_WINDOW', 1),

    'recovery_codes' => [
        'count' => env('2FA_RECOVERY_CODES_COUNT', 8),
        'length' => env('2FA_RECOVERY_CODES_LENGTH', 10),
    ],
];
