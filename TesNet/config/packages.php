<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default package seed data (managed in Admin → Packages after migrate)
    |--------------------------------------------------------------------------
    |
    | Imported by DataPackageSeeder into the data_packages table.
    | amount_pesewas: Paystack subunit — GH¢3.50 => 350 pesewas.
    |
    */

    'plans' => [
        'quick-surf' => [
            'name' => 'Quick Surf',
            'data' => '1GB',
            'data_limit_mb' => 1024,
            'price' => 3.50,
            'amount_pesewas' => 350,
            'speed_mbps' => 5,
            'validity_days' => 30,
        ],
        'student-choice' => [
            'name' => 'Student Choice',
            'data' => '3GB',
            'data_limit_mb' => 3072,
            'price' => 9.00,
            'amount_pesewas' => 900,
            'speed_mbps' => 10,
            'validity_days' => 30,
        ],
        'big-bundle' => [
            'name' => 'Big Bundle',
            'data' => '7GB',
            'data_limit_mb' => 7168,
            'price' => 18.00,
            'amount_pesewas' => 1800,
            'speed_mbps' => 15,
            'validity_days' => 30,
        ],
        'heavy-user' => [
            'name' => 'Heavy User',
            'data' => '15GB',
            'data_limit_mb' => 15360,
            'price' => 35.00,
            'amount_pesewas' => 3500,
            'speed_mbps' => 20,
            'validity_days' => 30,
        ],
        'hostel-legend' => [
            'name' => 'Hostel Legend',
            'data' => '45GB',
            'data_limit_mb' => 46080,
            'price' => 95.00,
            'amount_pesewas' => 9500,
            'speed_mbps' => null,
            'validity_days' => 30,
        ],
    ],

];
