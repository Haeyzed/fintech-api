<?php

namespace App\Enums;

enum StorageProviderEnum: string
{
    case LOCAL = 'local';
    case S3 = 's3';
    case GOOGLE = 'google';
    case CLOUDINARY = 'cloudinary';
    case FTP = 'ftp';
    case SFTP = 'sftp';
    case DROPBOX = 'dropbox';

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
