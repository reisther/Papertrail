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

    'google' => [
        'application_name' => env('GOOGLE_APPLICATION_NAME', 'PaperTrail MS'),
        'credentials_json' => env('GOOGLE_CREDENTIALS_JSON'),
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', 'storage/app/google-credentials.json') === 'storage/app/google-credentials.json' ? 
            storage_path('app/google-credentials.json') : 
            (str_starts_with(env('GOOGLE_CREDENTIALS_PATH'), '/') || str_contains(env('GOOGLE_CREDENTIALS_PATH'), ':\\') ? 
                env('GOOGLE_CREDENTIALS_PATH') : 
                base_path(env('GOOGLE_CREDENTIALS_PATH'))
            ),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

];
