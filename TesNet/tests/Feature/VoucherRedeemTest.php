<?php

use App\Models\DataPackage;
use App\Models\PackagePurchase;
use App\Models\PackageVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'tesnet.per_purchase_hotspot' => false,
        'mikrotik.api.enabled' => false,
    ]);
});

test('admin can create voucher code for a package', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $package = DataPackage::query()->create([
        'slug' => 'voucher-pkg',
        'name' => 'Voucher Package',
        'data_label' => '2 GB',
        'data_limit_mb' => 2048,
        'price' => 5,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.vouchers.store'), [
        'package_slug' => $package->slug,
        'quantity' => 1,
        'admin_note' => 'Cash at desk',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $voucher = PackageVoucher::query()->first();

    expect($voucher)->not->toBeNull()
        ->and($voucher->status)->toBe('available')
        ->and($voucher->package_slug)->toBe('voucher-pkg')
        ->and($voucher->code)->toMatch('/^TES-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

test('student can redeem voucher code on dashboard', function () {
    $student = User::factory()->create(['phone_number' => '233551234567', 'portal_session_version' => 1]);

    DataPackage::query()->create([
        'slug' => 'redeem-pkg',
        'name' => 'Redeem Package',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 5,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $voucher = PackageVoucher::query()->create([
        'code' => 'TES-TEST-CODE',
        'package_slug' => 'redeem-pkg',
        'package_name' => 'Redeem Package',
        'amount' => 5,
        'amount_pesewas' => 500,
        'status' => 'available',
    ]);

    $response = $this->actingAs($student)
        ->withSession(['portal_session_version' => 1])
        ->post(route('portal.vouchers.redeem'), [
        'code' => 'TES-TEST-CODE',
    ]);

    $response->assertRedirect(route('portal.dashboard'));
    $response->assertSessionHas('status');

    $voucher->refresh();

    expect($voucher->status)->toBe('redeemed')
        ->and($voucher->redeemed_by)->toBe($student->id)
        ->and($voucher->transaction_id)->not->toBeNull()
        ->and(PackagePurchase::query()->where('user_id', $student->id)->where('status', 'active')->exists())->toBeTrue();
});

test('redeem fails for invalid or used code', function () {
    $student = User::factory()->create(['portal_session_version' => 1]);

    $response = $this->actingAs($student)
        ->withSession(['portal_session_version' => 1])
        ->from(route('portal.dashboard'))
        ->post(route('portal.vouchers.redeem'), [
        'code' => 'TES-NOPE-0000',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('code');
});

test('admin can revoke unused voucher', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $voucher = PackageVoucher::query()->create([
        'code' => 'TES-REVK-CODE',
        'package_slug' => 'x',
        'package_name' => 'X',
        'amount' => 1,
        'amount_pesewas' => 100,
        'status' => 'available',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.vouchers.revoke', $voucher));

    $response->assertRedirect();
    expect($voucher->fresh()->status)->toBe('revoked');
});
