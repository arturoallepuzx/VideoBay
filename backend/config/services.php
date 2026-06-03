<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tmdb' => [
        'read_access_token' => env('TMDB_READ_ACCESS_TOKEN', ''),
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),
        'default_language' => env('TMDB_DEFAULT_LANGUAGE', 'es-ES'),
        'cache' => [
            'search_ttl_seconds' => (int) env('TMDB_SEARCH_CACHE_TTL', 3600),
            'detail_ttl_seconds' => (int) env('TMDB_DETAIL_CACHE_TTL', 86400),
            'recommendations_ttl_seconds' => (int) env('TMDB_RECOMMENDATIONS_CACHE_TTL', 604800),
            'person_ttl_seconds' => (int) env('TMDB_PERSON_CACHE_TTL', 86400),
        ],
    ],

    'barcode' => [
        'api_key' => env('BARCODE_API_KEY', ''),
        'base_url' => env('BARCODE_BASE_URL', 'https://api.upcdatabase.org'),
    ],

    'opensubtitles' => [
        'api_key' => env('OPENSUBTITLES_API_KEY', ''),
        'base_url' => env('OPENSUBTITLES_BASE_URL', 'https://api.opensubtitles.com/api/v1'),
        'user_agent' => env('OPENSUBTITLES_USER_AGENT', 'VideoBay'),
        'username' => env('OPENSUBTITLES_USERNAME', ''),
        'password' => env('OPENSUBTITLES_PASSWORD', ''),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

];
