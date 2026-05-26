<?php

namespace Database\Seeders;

use App\Models\DataPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DataPackageSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('packages.plans', []);
        $order = 0;

        foreach ($plans as $slug => $plan) {
            DataPackage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $plan['name'],
                    'data_label' => $plan['data'],
                    'data_limit_mb' => (int) $plan['data_limit_mb'],
                    'price' => $plan['price'],
                    'speed_mbps' => $plan['speed_mbps'],
                    'validity_days' => (int) ($plan['validity_days'] ?? 30),
                    'validity_type' => $plan['validity_type'] ?? 'days',
                    'is_active' => true,
                    'sort_order' => $order++,
                ]
            );
        }
    }
}
