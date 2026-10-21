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

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        // The Dataset ID is also the Pixel ID used by the browser and the
        // Conversions API endpoint. The access token is intentionally kept
        // server-side and must never be exposed through Vite or Blade.
        'tracking_enabled' => env('META_TRACKING_ENABLED', true),
        'dataset_id' => env('META_DATASET_ID'),
        'require_consent' => env('META_REQUIRE_CONSENT', true),
        'conversions_api' => [
            'enabled' => env('META_CONVERSIONS_API_ENABLED', true),
            'access_token' => env('META_CONVERSIONS_API_ACCESS_TOKEN'),
            'graph_api_version' => env('META_GRAPH_API_VERSION', 'v26.0'),
            // Set only while using Meta Events Manager's Test Events tool.
            'test_event_code' => env('META_TEST_EVENT_CODE'),
        ],
    ],

    'whatsapp' => [
        'otp_template' => env('WHATSAPP_OTP_TEMPLATE_NAME', 'merza_otp'),
        'otp_template_language' => env('WHATSAPP_OTP_TEMPLATE_LANGUAGE', 'en'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'sabpaisa' => [
        'api_key' => env('SABPAISA_API_KEY'),
        'secret_key' => env('SABPAISA_SECRET_KEY'),
        'webhook_secret' => env('SABPAISA_WEBHOOK_SECRET'),
        'merchant_id' => env('SABPAISA_MERCHANT_ID'),
        'base_url' => env('SABPAISA_BASE_URL', 'https://staging-sb-merchant-api.sabpaisa.in'),
        'fallback_email' => env('SABPAISA_FALLBACK_EMAIL') ?: env('MAIL_FROM_ADDRESS'),
    ],

];
