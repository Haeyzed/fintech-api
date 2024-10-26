<?php

namespace App\Enums;

enum SocialProviderEnum: string
{
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';
    case TWITTER = 'twitter';
    case GITHUB = 'github';

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
