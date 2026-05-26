<?php

return [

    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),

    /*
    | Paystack initialize requires a valid-looking email. Do not use .local domains.
    | For production, set a domain you control (e.g. billing.yourhostel.com).
    */
    'customer_email_domain' => env('PAYSTACK_CUSTOMER_EMAIL_DOMAIN', 'example.com'),

    'callback_route' => 'portal.payments.callback',
    'webhook_route' => 'portal.payments.webhook',

];
