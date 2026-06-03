<?php

use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\HotspotPurchaseService;
use App\Support\PackageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mark depleted retires mikrotik user even when per purchase flag is off', function () {
    config(['tesnet.per_purchase_hotspot' => false]);

    $retireCalled = false;

    $this->mock(HotspotPurchaseService::class, function ($mock) use (&$retireCalled) {
        $mock->shouldReceive('retire')
            ->once()
            ->andReturnUsing(function () use (&$retireCalled) {
                $retireCalled = true;
            });
    });

    $user = User::factory()->create();
    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => createMarkDepletedTransaction($user)->id,
        'package_slug' => 'test',
        'package_name' => 'Test',
        'data_limit_mb' => 100,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-7',
        'mikrotik_password' => 'secret-pass',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    PackageUsage::markDepleted($purchase);

    expect($retireCalled)->toBeTrue();
    expect($purchase->fresh()->status)->toBe('depleted');
});

test('mark depleted is idempotent', function () {
    $this->mock(HotspotPurchaseService::class, function ($mock) {
        $mock->shouldReceive('retire')->never();
    });

    $user = User::factory()->create();
    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => createMarkDepletedTransaction($user)->id,
        'package_slug' => 'test',
        'package_name' => 'Test',
        'data_limit_mb' => 100,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'depleted',
        'mikrotik_username' => 'tn-8',
    ]);

    PackageUsage::markDepleted($purchase);

    expect($purchase->fresh()->status)->toBe('depleted');
});

function createMarkDepletedTransaction(User $user): \App\Models\Transaction
{
    return \App\Models\Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'test',
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'ref_md_'.uniqid(),
        'status' => 'success',
    ]);
}
