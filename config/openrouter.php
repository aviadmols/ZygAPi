<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenRouter AI API integration
    |
    */

    'api_key' => env('OPENROUTER_API_KEY', ''),

    'api_url' => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1'),

    'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'anthropic/claude-3.5-sonnet'),

    'timeout' => env('OPENROUTER_TIMEOUT', 120),
];
