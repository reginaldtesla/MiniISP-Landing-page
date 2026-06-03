<?php

use App\Http\Middleware\EnsureSinglePortalSession;
use App\Models\PackagePurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MikrotikApiService;
use App\Services\PackageQuotaService;
use App\Services\SingleDeviceGuard;
use App\Support\PackageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('connect is blocked when router reports quota exhausted', function () {
    $this->withoutMiddleware(EnsureSinglePortalSession::class);

    config(['tesnet.per_purchase_hotspot' => true, 'mikrotik.api.enabled' => true]);

    $user = User::factory()->create(['phone_number' => '233547552348']);

    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'custom_data',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'ex_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'custom',
        'package_name' => 'Custom · 0.29 GB',
        'data_limit_mb' => 1,
        'data_limit_bytes' => (int) (0.29 * 1073741824),
        'bytes_consumed' => 0,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subHour(),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-700',
        'mikrotik_password' => 'secret',
        'mikrotik_profile' => 'tesnet-custom',
        'mikrotik_synced_at' => now(),
    ]);

    $this->mock(SingleDeviceGuard::class, function ($mock) {
        $mock->shouldReceive('disconnectOtherHotspotSessions')->andReturnNull();
    });

    $this->mock(PackageQuotaService::class, function ($mock) {
        $mock->shouldReceive('syncForUser')->once()->andReturn(null);
    });

    $response = $this->actingAs($user)
        ->withSession(['portal_wifi_password' => Crypt::encryptString('portal-pass')])
        ->post(route('portal.connect-wifi'));

    $response->assertRedirect(route('portal.packages'));
});

test('reconcile marks depleted when router quota is exhausted', function () {
    config(['tesnet.per_purchase_hotspot' => true, 'mikrotik.api.enabled' => true]);

    $user = User::factory()->create(['phone_number' => '233500000001']);

    $purchase = PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => 'pkg',
            'amount' => 1,
            'currency' => 'GHS',
            'amount_pesewas' => 100,
            'paystack_reference' => 'rec_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'pkg',
        'package_name' => 'Test',
        'data_limit_mb' => 512,
        'data_limit_bytes' => 512 * 1048576,
        'bytes_consumed' => 0,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subHours(2),
        'expires_at' => null,
        'status' => 'active',
        'mikrotik_username' => 'tn-701',
        'mikrotik_password' => 'x',
        'mikrotik_profile' => 'tesnet-pkg',
    ]);

    $this->mock(MikrotikApiService::class, function ($mock) {
        $mock->shouldReceive('isEnabled')->andReturn(true);
        $mock->shouldReceive('hotspotDataUsageForUser')->andReturn([
            'used' => 600 * 1048576,
            'limit' => 512 * 1048576,
        ]);
        $mock->shouldReceive('disconnectHotspotUser')->andReturn(true);
        $mock->shouldReceive('setHotspotUserDisabled')->andReturn(true);
    });

    $result = PackageUsage::reconcileActivePurchaseWithRouter($user);

    expect($result)->toBeNull();
    expect($purchase->fresh()->status)->toBe('depleted');
});
