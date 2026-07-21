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

    'line' => [
        'channel_id' => env('LINE_CHANNEL_ID'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'liff_id' => env('LIFF_ID'),
        'official_account_id' => env('LINE_OFFICIAL_ACCOUNT_ID'),
    ],

    'zengin_transfer' => [
        'consignor_code' => env('ZENGIN_CONSIGNOR_CODE'),
        'consignor_name' => env('ZENGIN_CONSIGNOR_NAME', 'TSUNAGU'),
        'source_bank_code' => env('ZENGIN_SOURCE_BANK_CODE'),
        'source_branch_code' => env('ZENGIN_SOURCE_BRANCH_CODE'),
        'source_account_type' => env('ZENGIN_SOURCE_ACCOUNT_TYPE', '1'),
        'source_account_no' => env('ZENGIN_SOURCE_ACCOUNT_NO'),
    ],

];
