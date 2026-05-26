<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default administrator (DatabaseSeeder)
    |--------------------------------------------------------------------------
    |
    | Set these in .env, then run: php artisan db:seed
    | The seeder creates or updates the admin user to match these values.
    |
    */

    'name' => env('ADMIN_NAME', 'TesNet Admin'),

    'phone' => env('ADMIN_PHONE', '0550000001'),

    'password' => env('ADMIN_PASSWORD', 'admin1234'),

    'email' => env('ADMIN_EMAIL', 'admin@tesnet.local'),

    'device_limit' => (int) env('ADMIN_DEVICE_LIMIT', 5),

];
