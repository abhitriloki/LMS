<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for authentication attempts
    |
    */
    'auth_rate_limit' => [
        'max_attempts' => env('AUTH_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('AUTH_DECAY_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Configure password complexity requirements
    |
    */
    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configure allowed file types and sizes
    |
    */
    'file_upload' => [
        'allowed_extensions' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'videos' => ['mp4', 'webm', 'ogg', 'mov'],
            'audio' => ['mp3', 'wav', 'ogg'],
        ],
        'max_file_size' => env('MAX_FILE_SIZE', 102400), // 100MB in KB
        'scan_for_viruses' => env('SCAN_UPLOADS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Configure CSP directives
    |
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'report_uri' => env('CSP_REPORT_URI', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings
    |
    */
    'session' => [
        'timeout_minutes' => env('SESSION_TIMEOUT', 120),
        'regenerate_on_login' => true,
        'invalidate_on_logout' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    |
    | Configure IP-based access control
    |
    */
    'ip_control' => [
        'enabled' => env('IP_CONTROL_ENABLED', false),
        'whitelist' => explode(',', env('IP_WHITELIST', '')),
        'blacklist' => explode(',', env('IP_BLACKLIST', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configure 2FA settings
    |
    */
    '2fa' => [
        'enabled' => env('2FA_ENABLED', false),
        'required_for_admins' => env('2FA_REQUIRED_ADMINS', false),
    ],
];
