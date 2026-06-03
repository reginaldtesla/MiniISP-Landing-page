<?php

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use App\Services\HotspotPurchaseService;
use App\Services\MikrotikApiService;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['tesnet.per_purchase_hotspot' => true]);

    $this->mock(MikrotikApiService::class, function ($mock) {
        $mock->shouldReceive('isEnabled')->andReturn(false);
    });
});

test('consolidate active purchases keeps newest and supersedes older', function () {
    $user = User::factory()->create();

    $olderTxn = Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'old',
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'old_'.uniqid(),
        'status' => 'success',
    ]);

    $older = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => $olderTxn->id,
        'package_slug' => 'old',
        'package_name' => 'Old',
        'data_limit_mb' => 100,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subDay(),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-10',
        'mikrotik_password' => 'a',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    $newerTxn = Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'new',
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'new_'.uniqid(),
        'status' => 'success',
    ]);

    $newer = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => $newerTxn->id,
        'package_slug' => 'new',
        'package_name' => 'New',
        'data_limit_mb' => 200,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
        'bytes_consumed' => 0,
        'mikrotik_username' => 'tn-11',
        'mikrotik_password' => 'b',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    $kept = PackageUsage::consolidateActivePurchases($user);

    expect($kept?->id)->toBe($newer->id)
        ->and($older->fresh()->status)->toBe('superseded')
        ->and(PackagePurchase::query()->where('user_id', $user->id)->where('status', 'active')->count())->toBe(1);
});

test('per purchase radacct usage ignores phone sessions', function () {
    $user = User::factory()->create(['phone_number' => '233551234567']);

    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => 'pkg',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'rad_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'pkg',
        'package_name' => 'Pkg',
        'data_limit_mb' => 1024,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
        'bytes_consumed' => 0,
        'mikrotik_username' => 'tn-99',
        'mikrotik_password' => 'secret',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    RadAcct::query()->create([
        'acctuniqueid' => 'phone-'.uniqid(),
        'username' => '233551234567',
        'acctstarttime' => now(),
        'acctinputoctets' => 900000000,
        'acctoutputoctets' => 100000000,
    ]);

    RadAcct::query()->create([
        'acctuniqueid' => 'tn-'.uniqid(),
        'username' => 'tn-99',
        'acctstarttime' => now(),
        'acctinputoctets' => 1000,
        'acctoutputoctets' => 500,
    ]);

    $usageUser = HotspotIdentity::usageUsernameFor($user, $purchase);

    PackageUsage::refreshConsumption($purchase, $usageUser);

    expect($purchase->fresh()->bytes_consumed)->toBeLessThan(10000);
});

test('model a blocks phone radius hotspot login on provision', function () {
    $user = User::factory()->create(['phone_number' => '233559998877']);

    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => 'pkg',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'prov_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'pkg',
        'package_name' => 'Pkg',
        'data_limit_mb' => 512,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-50',
        'mikrotik_password' => 'hotspot-pass',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    app(HotspotPurchaseService::class)->provision($purchase, $user);

    expect(\App\Models\RadCheck::query()
        ->where('username', $user->phone_number)
        ->where('attribute', 'Auth-Type')
        ->where('value', 'Reject')
        ->exists())->toBeTrue();
});
