<?php

namespace App\Utils;

use Sqids\Sqids;

final class Sqid
{
    /**
     * Encode an integer as a sqid.
     *
     * @param int $id The integer to encode
     * @return string The encoded sqid
     */
    public static function encode(int $id): string
    {
        return resolve(Sqids::class)->encode([$id]);
    }

    /**
     * Decode a sqid string into an integer.
     *
     * @param string $sqid The sqid to decode
     * @return int|null The decoded integer, or null if invalid
     */
    public static function decode(string $sqid): ?int
    {
        return resolve(Sqids::class)->decode($sqid)[0] ?? null;
    }
}
