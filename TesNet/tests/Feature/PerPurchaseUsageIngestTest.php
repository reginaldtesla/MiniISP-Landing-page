<?php

use App\Models\PackagePurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MikrotikApiService;
use App\Support\PackageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('per-purchase plan ingests mikrotik usage after provision sync', function () {
    config(['tesnet.per_purchase_hotspot' => true, 'mikrotik.api.enabled' => true]);

    $user = User::factory()->create(['phone_number' => '233547552348']);

    $limitBytes = (int) (0.29 * 1073741824);

    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'custom_data',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'ing_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'custom',
        'package_name' => 'Custom · 0.29 GB',
        'data_limit_mb' => 1,
        'data_limit_bytes' => $limitBytes,
        'bytes_consumed' => 0,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subMinutes(10),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-8',
        'mikrotik_password' => 'secret',
        'mikrotik_profile' => 'tesnet-custom',
        'mikrotik_synced_at' => now()->subMinutes(9),
    ]);

    $usedBytes = 228 * 1048576;

    $this->mock(MikrotikApiService::class, function ($mock) use ($usedBytes, $limitBytes) {
        $mock->shouldReceive('isEnabled')->andReturn(true);
        $mock->shouldReceive('hotspotDataUsageForUser')
            ->with('tn-8')
            ->andReturn(['used' => $usedBytes, 'limit' => $limitBytes]);
    });

    PackageUsage::reconcileActivePurchaseWithRouter($user);

    expect($purchase->fresh()->bytes_consumed)->toBeGreaterThan(200 * 1048576);
});
