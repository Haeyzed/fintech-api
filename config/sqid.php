<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sqid Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for configuring the Sqid utility used for ID obfuscation.
    |
    */

    // The salt used for generating sqids. Change this to a random string.
    'salt' => env('SQID_SALT', 'your-secret-salt'),

    // The minimum length of generated sqids.
    'min_length' => env('SQID_MIN_LENGTH', 10),

    // The alphabet used for generating sqids.
    'alphabet' => env('SQID_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
];