<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'key'          => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'model'        => env('OPENAI_MODEL', 'gpt-4o'),
        'max_tokens'   => env('OPENAI_MAX_TOKENS', 4000),
        'temperature'  => env('OPENAI_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SP-API
    |--------------------------------------------------------------------------
    */
    'amazon' => [
        'sp_api' => [
            'client_id'      => env('AMAZON_SP_API_CLIENT_ID'),
            'client_secret'  => env('AMAZON_SP_API_CLIENT_SECRET'),
            'refresh_token'  => env('AMAZON_SP_API_REFRESH_TOKEN'),
            'marketplace_id' => env('AMAZON_SP_API_MARKETPLACE_ID', 'ATVPDKIKX0DER'),
            'region'         => env('AMAZON_SP_API_REGION', 'us-east-1'),
            'aws_access_key' => env('AMAZON_SP_API_AWS_ACCESS_KEY'),
            'aws_secret_key' => env('AMAZON_SP_API_AWS_SECRET_KEY'),
            'role_arn'       => env('AMAZON_SP_API_ROLE_ARN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay
    |--------------------------------------------------------------------------
    */
    'razorpay' => [
        'key_id'         => env('RAZORPAY_KEY_ID'),
        'key_secret'     => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scraper Settings
    |--------------------------------------------------------------------------
    */
    'scraper' => [
        'timeout'      => env('SCRAPER_TIMEOUT', 30),
        'retry_times'  => env('SCRAPER_RETRY_TIMES', 3),
        'user_agent'   => env('SCRAPER_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),

        // Playwright sidecar (preferred — see scraper-service/scrape.js)
        'driver'              => env('SCRAPER_DRIVER', 'playwright'), // 'playwright' or 'http'
        'node_binary'         => env('SCRAPER_NODE_BINARY', 'node'),
        'playwright_script'   => env('SCRAPER_PLAYWRIGHT_SCRIPT', base_path('scraper-service/scrape.js')),
        'playwright_timeout'  => env('SCRAPER_PLAYWRIGHT_TIMEOUT', 60),
    ],

];
