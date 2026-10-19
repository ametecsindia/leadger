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

    'facebook' => [
        'client_id' => env('FACEBOOK_ID'),
        'client_secret' => env('FACEBOOK_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/facebook/callback',
    ],
    'twitter' => [
        'client_id' => env('TWITTER_ID'),
        'client_secret' => env('TWITTER_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/twitter/callback',
    ],
    'google' => [
        'client_id' => env('GOOGLE_ID'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/google/callback',
    ],
    'linkedin' => [
        'client_id' => env('LINKEDIN_ID'),
        'client_secret' => env('LINKEDIN_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/linkedin/callback',
    ],
    'github' => [
        'client_id' => env('GITHUB_ID'),
        'client_secret' => env('GITHUB_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/github/callback',
    ],
    'bitbucket' => [
        'client_id' => env('BITBUCKET_ID'),
        'client_secret' => env('BITBUCKET_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/bitbucket/callback',
    ],
    'firebase'=>[
        'key_path'=>public_path(env('FIREBASE_CREDENTIALS')),
    ],
    'sarvam' => [
        // Each company uses its own key (Configuration -> Profile -> License Info). This global key is used only
        // when SARVAM_ALLOW_GLOBAL_KEY=true (off by default, so no company is billed on another's key).
        'api_key' => env('SARVAM_API_KEY'),
        'allow_global_key' => (bool)env('SARVAM_ALLOW_GLOBAL_KEY', false),
        'base_url' => env('SARVAM_BASE_URL', 'https://api.sarvam.ai'),
        'stt_model' => env('SARVAM_STT_MODEL', 'saaras:v3'),
        // saaras:v3 output: translit = spoken words in English letters (Telugu stays Telugu, not translated)
        'stt_mode' => env('SARVAM_STT_MODE', 'translit'),
    ],
    'telephony' => [
        // the provider's shared Secret-Key (same value as the recording webhook and the incoming_data pull)
        'secret' => env('TELEPHONY_WEBHOOK_SECRET', 'ffcab6c009f568588842b54f15de6efb'),
        // Inbound Calls: optional comma-separated IP allow-list for the webhook (empty = any IP with the secret)
        'inbound_ips' => env('INBOUND_CALLS_WEBHOOK_IPS', ''),
        'incoming_url' => env('TELEPHONY_INCOMING_URL', 'http://obd.ametecsindia.com/admin/incoming_data/'),
    ],
];
