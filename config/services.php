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

    /*
    |--------------------------------------------------------------------------
    | External API Configuration (Pour CronApiService)
    |--------------------------------------------------------------------------
    |
    | Configuration pour les appels API externes via CRON.
    | Utilisé par App\Services\ExternalApi\CronApiService
    |
    | Variables d'environnement requises:
    | - EXTERNAL_API_BASE_URI : URL de base de l'API (ex: https://api.external.com)
    | - EXTERNAL_API_CLIENT_ID : Client ID OAuth
    | - EXTERNAL_API_CLIENT_SECRET : Client Secret OAuth
    | - EXTERNAL_API_PROXY : (Optionnel) URL du proxy (ex: http://proxy.company.com:8080)
    |
    */
    'external_api' => [
        'base_uri' => env('EXTERNAL_API_BASE_URI'),
        'client_id' => env('EXTERNAL_API_CLIENT_ID'),
        'client_secret' => env('EXTERNAL_API_CLIENT_SECRET'),
        'proxy' => env('EXTERNAL_API_PROXY', null),
    ],

];
