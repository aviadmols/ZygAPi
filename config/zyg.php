<?php

return [
    'app_name' => 'Zyg',
    'version' => '1.0.0',
    
    'automation' => [
        'max_steps' => 50,
        'default_timeout' => 300,
        'dry_run_enabled' => true,
    ],
    
    'integrations' => [
        'shopify' => [
            'api_version' => '2024-01',
            'rate_limit_per_second' => 2,
        ],
        'recharge' => [
            'rate_limit_per_second' => 10,
        ],
    ],
    
    'chat' => [
        'max_tokens' => 4000,
        'temperature' => 0.7,
    ],
    
    'webhooks' => [
        'bypass_signature_verification' => env('WEBHOOK_BYPASS_SIGNATURE', false),
    ],
];
