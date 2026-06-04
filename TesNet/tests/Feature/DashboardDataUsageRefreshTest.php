<?php

use App\Http\Middleware\EnsureSinglePortalSession;
use App\Models\PackagePurchase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard data usage endpoint returns json for active plan', function () {
    $this->withoutMiddleware(EnsureSinglePortalSession::class);

    config(['mikrotik.api.enabled' => false]);

    $user = User::factory()->create([
        'phone_number' => '233500000099',
        'is_admin' => false,
    ]);

    PackagePurchase::query()->create([
        'user_id' => $user->id,
        'transaction_id' => Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => 'test',
            'amount' => 5,
            'currency' => 'GHS',
            'amount_pesewas' => 500,
            'paystack_reference' => 'dash_'.uniqid(),
            'status' => 'success',
        ])->id,
        'package_slug' => 'test',
        'package_name' => 'Test 1GB',
        'data_limit_mb' => 1024,
        'bytes_consumed' => 100 * 1048576,
        'speed_mbps' => 10,
        'validity_type' => 'until_finished',
        'activated_at' => now()->subHour(),
        'expires_at' => null,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('portal.dashboard.data-usage'));

    $response->assertOk();

    $payload = $response->json();

    expect($payload['has_active_plan'])->toBeTrue()
        ->and($payload['percent_remaining'])->toBe(90)
        ->and($payload['total_plan_label'])->toBe('1 GB');
});
