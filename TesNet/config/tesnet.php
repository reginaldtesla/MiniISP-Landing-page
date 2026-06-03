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
        'path' => env('TESNET_BACKUP_PATH') ?: storage_path('backups'),
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

    /*
    |--------------------------------------------------------------------------
    | Package quota sync (MikroTik API + RADIUS writes)
    |--------------------------------------------------------------------------
    |
    | Cooldown prevents repeat full syncs when using Connect repeatedly.
    |
    */

    'quota_sync_cooldown_seconds' => (int) env('TESNET_QUOTA_SYNC_COOLDOWN', 45),

    /*
    | Minutes after purchase activation to ignore router byte counters for legacy
    | phone hotspot logins only. Per-purchase tn-{id} users use mikrotik_synced_at.
    */

    'purchase_provisioning_minutes' => max(1, (int) env('TESNET_PURCHASE_PROVISIONING_MINUTES', 3)),

    'portal_connected_cache_seconds' => (int) env('TESNET_PORTAL_CONNECTED_CACHE', 15),

    /*
    |--------------------------------------------------------------------------
    | Per-purchase MikroTik hotspot users (Model A)
    |--------------------------------------------------------------------------
    |
    | Each payment gets a hidden tn-{purchase_id} hotspot user with its own
    | byte counter. Students still log into the portal with their phone number.
    |
    */

    'per_purchase_hotspot' => env('TESNET_PER_PURCHASE_HOTSPOT', true),

    /*
    |--------------------------------------------------------------------------
    | One account, one device (students)
    |--------------------------------------------------------------------------
    |
    | Each student phone number is a single account. device_limit and RADIUS
    | Simultaneous-Use are capped at this value (default 1). New portal logins
    | invalidate other browsers; Connect kicks other hotspot sessions.
    |
    */

    'student_device_limit' => max(1, (int) env('TESNET_STUDENT_DEVICE_LIMIT', 1)),

    'hotspot_shared_users' => max(1, (int) env('TESNET_HOTSPOT_SHARED_USERS', 1)),

    'hotspot_cleanup_days' => (int) env('TESNET_HOTSPOT_CLEANUP_DAYS', 30),

    'hotspot_profiles' => [
        'package' => env('TESNET_HOTSPOT_PROFILE_PACKAGE', 'tesnet-pkg'),
        'custom' => env('TESNET_HOTSPOT_PROFILE_CUSTOM', 'tesnet-custom'),
    ],

];
