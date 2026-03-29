<?php

declare(strict_types=1);

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le service HttpClient
    | Utilisé avec App\Services\Http\HttpClientInterface
    |
    | Exemple d'utilisation:
    | $httpClient->withBaseUri(config('services.api.base_uri'))
    |             ->withProxy(config('services.api.proxy'))
    |
    */

    'api' => [
        // URL de base de l'API externe
        'base_uri' => env('API_BASE_URI', 'https://api.example.com'),

        // Credentials OAuth
        'client_id' => env('API_CLIENT_ID'),
        'client_secret' => env('API_CLIENT_SECRET'),

        // Configuration du proxy (optionnel)
        // Format: http://user:password@proxy.company.com:8080
        // ou simplement: http://proxy.company.com:8080
        'proxy' => env('API_PROXY', null),

        // Timeout en secondes
        'timeout' => env('API_TIMEOUT', 30),

        // Timeout de connexion en secondes
        'connect_timeout' => env('API_CONNECT_TIMEOUT', 10),

        // Nombre de tentatives de retry
        'max_retries' => env('API_MAX_RETRIES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multiple API Configurations
    |--------------------------------------------------------------------------
    |
    | Si vous avez plusieurs APIs externes à gérer
    |
    */

    'external_apis' => [
        'crm' => [
            'base_uri' => env('CRM_API_BASE_URI'),
            'client_id' => env('CRM_API_CLIENT_ID'),
            'client_secret' => env('CRM_API_CLIENT_SECRET'),
            'proxy' => env('CRM_API_PROXY'),
        ],

        'payment' => [
            'base_uri' => env('PAYMENT_API_BASE_URI'),
            'api_key' => env('PAYMENT_API_KEY'),
            'proxy' => null, // Pas de proxy pour l'API de paiement
        ],

        'analytics' => [
            'base_uri' => env('ANALYTICS_API_BASE_URI'),
            'token' => env('ANALYTICS_API_TOKEN'),
        ],
    ],

];
