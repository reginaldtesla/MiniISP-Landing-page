<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $phone = '233'.fake()->numerify('#########');

        return [
            'name' => $phone,
            'email' => $phone.'@tesnet.local',
            'phone_number' => PhoneNumber::normalize('0'.substr($phone, 3)),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'device_limit' => 1,
            'is_admin' => false,
            'wallet_balance' => 0,
            'remember_token' => Str::random(10),
        ];
    }
}
