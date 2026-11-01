<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'social' => [
        'facebook_app_id' => env('FACEBOOK_APP_ID'),
        'twitter' => env('TWITTER_HANDLE'),
        // Profile URLs: icons are only shown for the ones that are set
        'urls' => [
            'facebook' => env('SOCIAL_FACEBOOK'),
            'instagram' => env('SOCIAL_INSTAGRAM'),
            'x' => env('SOCIAL_X'),
            'pinterest' => env('SOCIAL_PINTEREST'),
            'youtube' => env('SOCIAL_YOUTUBE'),
            'tiktok' => env('SOCIAL_TIKTOK'),
        ],
    ],

    'tasty' => [
        'key' => env('RAPIDAPI_KEY'),
        'host' => env('RAPIDAPI_TASTY_HOST', 'tasty.p.rapidapi.com'),
        'feed_token' => env('RECIPES_FEED_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
