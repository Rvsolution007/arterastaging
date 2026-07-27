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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URL', '/auth/google/callback'),
    ],

    'firebase' => [
        // The Firebase Authentication project that issues mobile ID tokens.
        'project_id' => env('FIREBASE_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT_ID', '')),
    ],

    'google_play' => [
        'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME', 'com.arterapixel.pro'),
        'service_account_json' => env('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON', ''),
    ],

    'runpod' => [
        'api_key' => env('RUNPOD_API_KEY'),
        'flux_endpoint_id' => env('RUNPOD_FLUX_ENDPOINT_ID'),
        'wan_endpoint_id' => env('RUNPOD_WAN_ENDPOINT_ID'),
    ],

];
