<?php

return [

    'login_url' => env('MIKROTIK_LOGIN_URL', 'http://192.168.88.1/login'),

    /*
    | After MikroTik accepts hotspot login, redirect here instead of the router
    | status page. Must match APP_URL students use (e.g. http://192.168.88.2).
    */
    'post_login_url' => env('MIKROTIK_POST_LOGIN_URL') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/portal/dashboard',

    'api' => [
        'enabled' => env('MIKROTIK_API_ENABLED', false),
        'host' => env('MIKROTIK_API_HOST', '192.168.88.1'),
        'port' => (int) env('MIKROTIK_API_PORT', 8728),
        'user' => env('MIKROTIK_API_USER', 'admin'),
        'password' => env('MIKROTIK_API_PASSWORD', ''),
        'ssl' => env('MIKROTIK_API_SSL', false),
        'timeout' => (int) env('MIKROTIK_API_TIMEOUT', 2),
    ],

];
