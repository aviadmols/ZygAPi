<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Shopify API integration
    |
    */

    'api_version' => env('SHOPIFY_API_VERSION', '2024-10'),

    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET', ''),

    'rate_limit' => [
        'requests_per_second' => 2,
        'burst_limit' => 40,
    ],
];
