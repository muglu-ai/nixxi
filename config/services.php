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

    'idfy' => [
        'account_id' => env('IDFY_ACCOUNT_ID', '038bb27f4ff8/cef38574-71bd-45b9-9faf-e1041c23ad46'),
        'api_key' => env('IDFY_API_KEY', 'edd2efad-0f6d-4b60-95a7-fd6be3672cbe'),
        'base_url' => env('IDFY_BASE_URL', 'https://eve.idfy.com'),
    ],

    'payu' => [
        'merchant_id' => env('PAYU_MERCHANT_ID', '8847461'),
        'merchant_key' => env('PAYU_MERCHANT_KEY', 'iaH0zp'),
        'salt' => env('PAYU_SALT', 'YSEB0ghJuWV69ZttwxW7fv1F9XXHEosC'),
        'test_url' => env('PAYU_TEST_URL', 'https://test.payu.in/_payment'),
        'live_url' => env('PAYU_LIVE_URL', 'https://secure.payu.in/_payment'),
        'mode' => env('PAYU_MODE', 'test'),
        'service_provider' => env('PAYU_SERVICE_PROVIDER', 'payu_paisa'),
    ],

];
