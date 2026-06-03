<?php

use App\Models\DataPackage;
use App\Models\PackagePurchase;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Transaction;
use App\Models\User;
use App\Http\Middleware\EnsureSinglePortalSession;
use App\Services\MikrotikApiService;
use App\Services\PackageQuotaService;
use App\Services\PaymentFulfillmentService;
use App\Services\SingleDeviceGuard;
use App\Support\HotspotIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'tesnet.per_purchase_hotspot' => true,
        'mikrotik.api.enabled' => true,
        'mikrotik.api.password' => 'test-secret',
    ]);

    $this->mock(MikrotikApiService::class, function ($mock) {
        $mock->shouldReceive('isEnabled')->andReturn(true);
        $mock->shouldReceive('upsertHotspotUser')->andReturn(true);
        $mock->shouldReceive('disconnectHotspotUser')->andReturn(true);
        $mock->shouldReceive('setHotspotUserDisabled')->andReturn(true);
        $mock->shouldReceive('removeHotspotUser')->andReturnNull();
    });
});

test('payment fulfillment creates per purchase mikrotik identity and radius rows', function () {
    $user = User::factory()->create(['phone_number' => '233551234567']);

    $package = DataPackage::query()->create([
        'slug' => 'test-1cedi',
        'name' => '1 Cedi Test',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 1,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $transaction = Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => $package->slug,
        'amount' => 1,
        'currency' => 'GHS',
        'amount_pesewas' => 100,
        'paystack_reference' => 'pay_'.uniqid(),
        'status' => 'pending',
    ]);

    app(PaymentFulfillmentService::class)->fulfill($transaction, ['channel' => 'card']);

    $purchase = PackagePurchase::query()->where('user_id', $user->id)->latest('id')->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->status)->toBe('active')
        ->and($purchase->mikrotik_username)->toBe('tn-'.$purchase->id)
        ->and($purchase->mikrotik_profile)->toBe('tesnet-pkg')
        ->and($purchase->mikrotik_synced_at)->not->toBeNull()
        ->and($purchase->hotspotLoginPassword())->not->toBeEmpty()
        ->and(HotspotIdentity::usesPerPurchase($purchase))->toBeTrue();

    expect(RadCheck::query()->where('username', $purchase->mikrotik_username)->where('attribute', 'Cleartext-Password')->exists())->toBeTrue();

    expect(RadReply::query()
        ->where('username', $purchase->mikrotik_username)
        ->where('attribute', 'Mikrotik-Total-Limit')
        ->value('value'))->toBe((string) (1024 * 1048576));
});

test('new payment supersedes prior active purchase', function () {
    $user = User::factory()->create();

    $old = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => 'old',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'old_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'old',
        'package_name' => 'Old',
        'data_limit_mb' => 100,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subDay(),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-1',
        'mikrotik_password' => 'old-pass',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    DataPackage::query()->create([
        'slug' => 'new-pkg',
        'name' => 'New',
        'data_label' => '2 GB',
        'data_limit_mb' => 2048,
        'price' => 2,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $txn = Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'package',
        'package_slug' => 'new-pkg',
        'amount' => 2,
        'currency' => 'GHS',
        'amount_pesewas' => 200,
        'paystack_reference' => 'new_'.uniqid(),
        'status' => 'pending',
    ]);

    app(PaymentFulfillmentService::class)->fulfill($txn, []);

    expect($old->fresh()->status)->toBe('superseded');
    expect(PackagePurchase::query()->where('user_id', $user->id)->where('status', 'active')->count())->toBe(1);
});

test('connect wifi uses tn username for model a purchase', function () {
    $this->withoutMiddleware(EnsureSinglePortalSession::class);

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
            'paystack_reference' => 'c_'.uniqid(),
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
        'bytes_consumed' => 0,
        'mikrotik_username' => 'tn-500',
        'mikrotik_password' => 'hotspot-only-secret',
        'mikrotik_profile' => 'tesnet-pkg',
        'mikrotik_synced_at' => now(),
    ]);

    $this->mock(SingleDeviceGuard::class, function ($mock) {
        $mock->shouldReceive('disconnectOtherHotspotSessions')->andReturnNull();
    });

    $purchase = PackagePurchase::query()->whereKey($purchase->id)->first();

    $this->mock(PackageQuotaService::class, function ($mock) use ($purchase) {
        $mock->shouldReceive('syncForUser')->andReturn($purchase);
    });

    $response = $this->actingAs($user)
        ->withSession(['portal_wifi_password' => Crypt::encryptString('portal-pass')])
        ->post(route('portal.connect-wifi'));

    expect($response->status())->toBe(200)
        ->and($response->getContent())->toContain('tn-500')
        ->and($response->getContent())->toContain('hotspot-only-secret');
});
