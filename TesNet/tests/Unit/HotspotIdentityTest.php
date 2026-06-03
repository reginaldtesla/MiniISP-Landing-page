<?php

use App\Models\PackagePurchase;
use App\Models\User;
use App\Support\HotspotIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('username for purchase follows tn id pattern', function () {
    $user = User::factory()->create();
    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => createTestTransaction($user)->id,
        'package_slug' => 'test-1cedi',
        'package_name' => 'Test 1 Cedi',
        'data_limit_mb' => 1024,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now(),
        'expires_at' => null,
        'status' => 'active',
    ]);

    expect(HotspotIdentity::usernameForPurchase($purchase))->toBe('tn-'.$purchase->id);
});

test('uses per purchase when enabled and mikrotik username set', function () {
    config(['tesnet.per_purchase_hotspot' => true]);

    $purchase = new PackagePurchase([
        'mikrotik_username' => 'tn-99',
    ]);

    expect(HotspotIdentity::usesPerPurchase($purchase))->toBeTrue();
});

test('falls back to phone when legacy purchase has no mikrotik username', function () {
    config(['tesnet.per_purchase_hotspot' => true]);

    $user = User::factory()->create(['phone_number' => '233551234567']);
    $purchase = new PackagePurchase(['mikrotik_username' => null]);

    expect(HotspotIdentity::usageUsernameFor($user, $purchase))->toBe('233551234567');
});

test('usage username is tn id for model a purchase', function () {
    config(['tesnet.per_purchase_hotspot' => true]);

    $user = User::factory()->create();
    $purchase = new PackagePurchase(['mikrotik_username' => 'tn-42']);

    expect(HotspotIdentity::usageUsernameFor($user, $purchase))->toBe('tn-42');
});

function createTestTransaction(User $user): \App\Models\Transaction
{
    return \App\Models\Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'test-1cedi',
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'ref_'.uniqid(),
        'status' => 'pending',
    ]);
}
