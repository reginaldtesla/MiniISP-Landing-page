<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPhone = PhoneNumber::normalize(config('admin.phone'));

        User::query()->updateOrCreate(
            ['phone_number' => $adminPhone],
            [
                'name' => config('admin.name'),
                'email' => config('admin.email'),
                'password' => Hash::make(config('admin.password')),
                'device_limit' => config('admin.device_limit'),
                'is_admin' => true,
                'wallet_balance' => 0,
            ]
        );

        $this->call(DataPackageSeeder::class);
    }
}
