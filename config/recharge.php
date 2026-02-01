<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recharge Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Recharge API integration
    |
    */

    'api_url' => env('RECHARGE_API_URL', 'https://api.rechargeapps.com'),

    'rate_limit' => [
        'requests_per_second' => 2,
    ],
];
