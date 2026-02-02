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

    /*
    |--------------------------------------------------------------------------
    | API Rate Limit (per store)
    |--------------------------------------------------------------------------
    | Shopify REST: Standard 2 req/s, Advanced 4, Plus 20. The app throttles
    | all Shopify API calls so we never exceed this, even with many workers.
    */
    'rate_limit' => [
        'requests_per_second' => (int) env('SHOPIFY_RATE_LIMIT_PER_SECOND', 2),
        'burst_limit' => 40,
    ],
];
