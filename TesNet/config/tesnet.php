<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin access
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs/CIDRs allowed to reach /admin/* (empty = allow all).
    | Example: 192.168.88.0/24,127.0.0.1
    |
    */

    'admin_allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALLOWED_IPS', ''))
    ))),

    'admin_min_password_length' => (int) env('ADMIN_MIN_PASSWORD_LENGTH', 12),

    'admin_idle_logout_minutes' => (int) env('ADMIN_IDLE_LOGOUT_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Login rate limiting (per IP)
    |--------------------------------------------------------------------------
    */

    'login_rate_limit' => (int) env('LOGIN_RATE_LIMIT', 10),
    'login_rate_decay_minutes' => (int) env('LOGIN_RATE_DECAY_MINUTES', 1),

    'register_rate_limit' => (int) env('REGISTER_RATE_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Database backups
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'enabled' => env('TESNET_BACKUP_ENABLED', true),
        'path' => env('TESNET_BACKUP_PATH', storage_path('backups')),
        'retain_days' => (int) env('TESNET_BACKUP_RETAIN_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring thresholds
    |--------------------------------------------------------------------------
    */

    'monitor' => [
        'disk_free_percent_min' => (int) env('TESNET_DISK_FREE_PERCENT_MIN', 10),
        'radius_stale_hours' => (int) env('TESNET_RADIUS_STALE_HOURS', 6),
    ],

];
