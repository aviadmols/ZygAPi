<?php

return [
    'openrouter' => [
        'api_url' => 'https://openrouter.ai/api/v1',
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'anthropic/claude-3.5-sonnet'),
        'api_key' => env('OPENROUTER_API_KEY'),
        'timeout' => 60,
    ],
    
    'shopify' => [
        'api_version' => '2024-01',
        'webhook_secret_header' => 'X-Shopify-Hmac-Sha256',
    ],
    
    'recharge' => [
        'api_url' => 'https://api.rechargeapps.com',
        'webhook_secret_header' => 'X-Recharge-Hmac-Sha256',
    ],
];
