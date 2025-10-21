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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI API integration including API keys, rate limits,
    | and cost tracking settings.
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'default_model' => env('OPENAI_MODEL', 'gpt-4'),
        
        // Rate limits per minute
        'rate_limits' => [
            'text-generation' => env('OPENAI_RATE_LIMIT_TEXT', 60),
            'text-analysis' => env('OPENAI_RATE_LIMIT_ANALYSIS', 60),
            'image-generation' => env('OPENAI_RATE_LIMIT_IMAGE', 10),
            'audio-transcription' => env('OPENAI_RATE_LIMIT_AUDIO', 30),
            'text-to-speech' => env('OPENAI_RATE_LIMIT_TTS', 30),
        ],
        
        // Cost per 1K tokens (in USD)
        'costs' => [
            'gpt-4' => [
                'input' => 0.03,
                'output' => 0.06,
            ],
            'gpt-4-turbo' => [
                'input' => 0.01,
                'output' => 0.03,
            ],
            'gpt-3.5-turbo' => [
                'input' => 0.0015,
                'output' => 0.002,
            ],
            'dall-e-3' => 0.040, // per image
            'whisper-1' => 0.006, // per minute
            'tts-1' => 0.015, // per 1M characters
            'tts-1-hd' => 0.030, // per 1M characters
        ],
        
        // Enable/disable fallback service
        'use_fallback' => env('OPENAI_USE_FALLBACK', true),
        
        // Cache settings
        'cache_enabled' => env('OPENAI_CACHE_ENABLED', true),
        'cache_ttl' => env('OPENAI_CACHE_TTL', 3600), // 1 hour
    ],

];
