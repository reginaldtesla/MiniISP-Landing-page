<?php

use App\Models\DataPackage;
use App\Models\ManualPaymentRequest;
use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\MikrotikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'tesnet.per_purchase_hotspot' => true,
        'mikrotik.api.enabled' => false,
    ]);
});

test('admin can approve pending manual package payment', function () {
    $admin = User::factory()->create([
        'phone_number' => '233500000001',
        'is_admin' => true,
    ]);

    $student = User::factory()->create(['phone_number' => '233551234567']);

    $package = DataPackage::query()->create([
        'slug' => 'admin-manual',
        'name' => 'Admin Manual',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 5,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $manual = ManualPaymentRequest::query()->create([
        'user_id' => $student->id,
        'type' => 'package',
        'status' => 'pending',
        'package_slug' => $package->slug,
        'amount' => 5,
        'amount_pesewas' => 500,
        'payment_method' => 'momo',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.manual-payments.approve', $manual), [
        'admin_note' => 'MoMo confirmed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $manual->refresh();

    expect($manual->status)->toBe('approved')
        ->and($manual->transaction_id)->not->toBeNull()
        ->and(PackagePurchase::query()->where('user_id', $student->id)->where('status', 'active')->exists())->toBeTrue();
});

test('admin approve shows error when package missing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $student = User::factory()->create();

    $manual = ManualPaymentRequest::query()->create([
        'user_id' => $student->id,
        'type' => 'package',
        'status' => 'pending',
        'package_slug' => null,
        'amount' => 5,
        'amount_pesewas' => 500,
        'payment_method' => 'momo',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.manual-payments.approve', $manual));

    $response->assertRedirect();
    $response->assertSessionHasErrors('request');
    expect($manual->fresh()->status)->toBe('pending');
});

test('admin can approve custom data request with missing metadata', function () {
    DataPackage::query()->create([
        'slug' => 'calc-a',
        'name' => 'A',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 1,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'is_special_offer' => false,
        'sort_order' => 1,
    ]);

    DataPackage::query()->create([
        'slug' => 'calc-b',
        'name' => 'B',
        'data_label' => '10 GB',
        'data_limit_mb' => 10240,
        'price' => 10,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'is_special_offer' => false,
        'sort_order' => 2,
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $student = User::factory()->create();

    $manual = ManualPaymentRequest::query()->create([
        'user_id' => $student->id,
        'type' => 'custom_data',
        'status' => 'pending',
        'amount' => 5,
        'amount_pesewas' => 500,
        'payment_method' => 'momo',
        'metadata' => null,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.manual-payments.approve', $manual));

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect($manual->fresh()->status)->toBe('approved');
});
