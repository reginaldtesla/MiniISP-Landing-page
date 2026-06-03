<?php

use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\HotspotPurchaseService;
use App\Services\MikrotikApiService;
use App\Services\RadiusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('provision sets mikrotik synced when api disabled but radius sync runs', function () {
    config([
        'tesnet.per_purchase_hotspot' => true,
        'mikrotik.api.enabled' => false,
    ]);

    $this->mock(MikrotikApiService::class, function ($mock) {
        $mock->shouldReceive('isEnabled')->andReturn(false);
        $mock->shouldNotReceive('upsertHotspotUser');
    });

    $user = User::factory()->create();
    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => createProvisionTransaction($user)->id,
        'package_slug' => 'pkg',
        'package_name' => 'Pkg',
        'data_limit_mb' => 100,
        'speed_mbps' => 30,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
    ]);

    $service = app(HotspotPurchaseService::class);
    $purchase = $service->assignIdentity($purchase, $user);

    expect($service->provision($purchase, $user))->toBeTrue();
    expect($purchase->fresh()->mikrotik_synced_at)->not->toBeNull();
});

test('retire active purchases only targets active rows with mikrotik username', function () {
    config(['mikrotik.api.enabled' => false]);

    $this->mock(MikrotikApiService::class, function ($mock) {
        $mock->shouldReceive('isEnabled')->andReturn(false);
    });

    $this->mock(RadiusSyncService::class, function ($mock) {
        $mock->shouldReceive('removePurchaseUser')->once();
    });

    $user = User::factory()->create();
    $txn = createProvisionTransaction($user);

    PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => $txn->id,
        'package_slug' => 'a',
        'package_name' => 'A',
        'data_limit_mb' => 10,
        'speed_mbps' => 10,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'status' => 'active',
        'mikrotik_username' => 'tn-1',
        'mikrotik_password' => 'x',
    ]);

    app(HotspotPurchaseService::class)->retireActivePurchasesFor($user);
});

function createProvisionTransaction(User $user): \App\Models\Transaction
{
    return \App\Models\Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'pkg',
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'ref_pr_'.uniqid(),
        'status' => 'success',
    ]);
}
