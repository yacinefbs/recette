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

    'facebook' => [
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'page_token' => env('FACEBOOK_PAGE_TOKEN'),
        'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v21.0'),
        'page_url' => env('FACEBOOK_PAGE_URL', 'https://www.facebook.com/profile.php?id=1418868981309476'),
    ],

    'adsense' => [
        // Ad unit IDs (create them in AdSense); a slot renders nothing until its ID is set.
        'slots' => [
            'after_search' => env('ADSENSE_SLOT_AFTER_SEARCH'),
            'after_results' => env('ADSENSE_SLOT_AFTER_RESULTS'),
            'guide' => env('ADSENSE_SLOT_GUIDE'),
        ],
    ],

    'adsterra' => [
        'smartlink' => 'https://araplhn.org/4/b0f8fcde3e47c50b13231d916fc6d51b',
        'popunder' => 'https://abscloud.org/1/2c75d7e9139c3b0c6c543c9f182f6511',
        'native_banner' => [
            'src' => 'https://bauval.org/21/369a58ad8cfdf0a84ff51651b76d395b',
            'container_id' => 'container-369a58ad8cfdf0a84ff51651b76d395b',
        ],
        'social_bar' => 'https://bauval.org/14/ed99a98b1b24489c7100d620b03db333',
        'banners' => [
            '320x50' => ['key' => 'e5410955d34c5324f74a598138fc7a6a', 'width' => 320, 'height' => 50],
            '728x90' => ['key' => '664f39179c9c9438a444d8fbbe0bf8af', 'width' => 728, 'height' => 90],
            '160x300' => ['key' => '87145d5362ccc16d8738d9ff3b58440e', 'width' => 160, 'height' => 300],
            '160x600' => ['key' => '60274566898069ef08a3c67c755f4abb', 'width' => 160, 'height' => 600],
            '468x60' => ['key' => '37034b38cde760a9412cb5d31dc4642f', 'width' => 468, 'height' => 60],
            '300x250' => ['key' => '25dd9fc655827593059beb1f8c4d7c96', 'width' => 300, 'height' => 250],
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
