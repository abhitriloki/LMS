<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable audit logging globally
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep audit logs before automatic cleanup
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Events to Log
    |--------------------------------------------------------------------------
    |
    | Specify which events should be logged
    |
    */
    'events' => [
        'login' => true,
        'logout' => true,
        'failed_login' => true,
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'viewed' => false, // Can generate a lot of logs
        'permission_changed' => true,
        'security_events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models to Audit
    |--------------------------------------------------------------------------
    |
    | Specify which models should be audited
    | Use '*' to audit all models with the Auditable trait
    |
    */
    'models' => [
        'User' => true,
        'Course' => true,
        'Assessment' => true,
        'Certificate' => true,
        'Enrollment' => true,
        'Department' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should never be logged
    |
    */
    'sensitive_fields' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Anonymization
    |--------------------------------------------------------------------------
    |
    | Anonymize IP addresses for privacy compliance (GDPR)
    |
    */
    'anonymize_ip' => env('AUDIT_ANONYMIZE_IP', false),
];
