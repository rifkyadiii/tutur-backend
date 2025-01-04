<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],

    'authentication' => [
        'firebase_uid_as_token_identifier' => true,
    ],

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    'dynamic_links' => [
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
    ],

    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),
    ],

    'cloud_messaging' => [
        //
    ],

    'cloud_functions' => [
        'location' => env('FIREBASE_CLOUD_FUNCTIONS_LOCATION', 'us-central1'),
    ],

    'remote_config' => [
        //
    ],
];
