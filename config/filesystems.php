<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks, are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'ftp' => [
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),

            // Optional FTP Settings...
            // 'port' => env('FTP_PORT', 21),
            // 'root' => env('FTP_ROOT'),
            // 'passive' => true,
            // 'ssl' => true,
            // 'timeout' => 30,
        ],

        'sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),

            // Settings for basic authentication...
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),

            // Settings for SSH key-based authentication with encryption password...
            'privateKey' => env('SFTP_PRIVATE_KEY'),
            'passphrase' => env('SFTP_PASSPHRASE'),

            // Settings for file / directory permissions...
            'visibility' => 'private', // `private` = 0600, `public` = 0644
            'directory_visibility' => 'private', // `private` = 0700, `public` = 0755

            // Optional SFTP Settings...
            // 'hostFingerprint' => env('SFTP_HOST_FINGERPRINT'),
            // 'maxTries' => 4,
            // 'passphrase' => env('SFTP_PASSPHRASE'),
            // 'port' => env('SFTP_PORT', 22),
            // 'root' => env('SFTP_ROOT', ''),
            // 'timeout' => 30,
            // 'useAgent' => true,
        ],

        'dropbox' => [
            'driver' => 'dropbox',
            'key' => env('DROPBOX_APP_KEY'),
            'secret' => env('DROPBOX_APP_SECRET'),
            'authorization_token' => env('DROPBOX_AUTH_TOKEN'),
        ],

        'gcs' => [
            'driver' => 'gcs',
            'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
            'key_file' => [], // optional: Array of data that substitutes the .json file (see below)
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'), // optional: is included in key file
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''), // optional: /default/path/to/apply/in/bucket
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
            'api_endpoint' => env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT', null), // set storageClient apiEndpoint
            'visibility' => 'public', // optional: public|private
            'visibility_handler' => null, // optional: set to \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class to enable uniform bucket level access
            'metadata' => ['cacheControl' => 'public,max-age=86400'], // optional: default metadata
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Storage Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage provider that will be used by
    | the application. You may specify any of the storage providers supported
    | by the application. This setting can be overridden by setting the
    | DEFAULT_STORAGE_PROVIDER environment variable.
    |
    | Supported: "local", "s3", "google", "cloudinary", "ftp", "sftp"
    |
    */
    'default_storage_provider' => env('DEFAULT_STORAGE_PROVIDER', 'cloudinary'),

    'watermark_enabled' => env('WATERMARK_ENABLED', false),
    'watermark_path' => env('WATERMARK_PATH', public_path('images/watermark.png')),
];
