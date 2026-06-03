<?php

return [

    'login_url' => env('MIKROTIK_LOGIN_URL', 'http://192.168.88.1/login'),

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
