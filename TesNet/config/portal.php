<?php

return [

    'support' => [
        'phone' => env('SUPPORT_PHONE', ''),
        'email' => env('SUPPORT_EMAIL', ''),
        'hours' => env('SUPPORT_HOURS', 'Monday–Friday, 8:00 AM – 5:00 PM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline portal assets (fonts, icons, CSS, JS)
    |--------------------------------------------------------------------------
    |
    | When true, the app serves bundled files from public/assets/portal/
    | (built with npm run build:offline). Required for hotspot users who
    | only have walled-garden access to your server — no CDN or Vite dev server.
    |
    */
    'use_offline_assets' => env('PORTAL_USE_OFFLINE_ASSETS', true),

];
