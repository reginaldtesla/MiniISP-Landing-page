<?php

use App\Models\DataPackage;
use App\Models\ManualPaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('student can submit manual package payment request', function () {
    $user = User::factory()->create(['phone_number' => '233551234567', 'portal_session_version' => 1]);

    $package = DataPackage::query()->create([
        'slug' => 'manual-test',
        'name' => 'Manual Test',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 5,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['portal_session_version' => 1])
        ->post(route('portal.manual-payments.store'), [
        'type' => 'package',
        'package' => $package->slug,
        'amount' => 5,
        'payment_method' => 'momo',
        'provider' => 'MTN',
        'reference' => '1234567890',
    ]);

    $response->assertRedirect(route('portal.packages'));
    $response->assertSessionHas('status');

    $request = ManualPaymentRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->user_id)->toBe($user->id)
        ->and($request->type)->toBe('package')
        ->and($request->status)->toBe('pending')
        ->and($request->package_slug)->toBe($package->slug)
        ->and($request->amount_pesewas)->toBe(500);
});

test('student can submit manual custom data payment request', function () {
    DataPackage::query()->create([
        'slug' => 'calc-low',
        'name' => 'Low',
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
        'slug' => 'calc-high',
        'name' => 'High',
        'data_label' => '10 GB',
        'data_limit_mb' => 10240,
        'price' => 10,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'is_special_offer' => false,
        'sort_order' => 2,
    ]);

    $user = User::factory()->create(['phone_number' => '233559876543', 'portal_session_version' => 1]);

    $response = $this->actingAs($user)
        ->withSession(['portal_session_version' => 1])
        ->post(route('portal.manual-payments.store'), [
        'type' => 'custom_data',
        'amount' => 5,
        'payment_method' => 'momo',
    ]);

    $response->assertRedirect(route('portal.packages'));

    $request = ManualPaymentRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->type)->toBe('custom_data')
        ->and($request->metadata)->toBeArray()
        ->and($request->metadata['data_limit_bytes'] ?? 0)->toBeGreaterThan(0)
        ->and($request->metadata['data_label'] ?? '')->not->toBe('');
});

test('manual payment request can store proof upload', function () {
    Storage::fake('local');

    $user = User::factory()->create(['phone_number' => '233551111111', 'portal_session_version' => 1]);

    $package = DataPackage::query()->create([
        'slug' => 'proof-test',
        'name' => 'Proof Test',
        'data_label' => '1 GB',
        'data_limit_mb' => 1024,
        'price' => 2,
        'speed_mbps' => 60,
        'validity_type' => 'until_finished',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['portal_session_version' => 1])
        ->post(route('portal.manual-payments.store'), [
        'type' => 'package',
        'package' => $package->slug,
        'amount' => 2,
        'payment_method' => 'momo',
        'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('portal.packages'));

    $request = ManualPaymentRequest::query()->first();

    expect($request->proof_path)->not->toBeNull();
    Storage::disk('local')->assertExists($request->proof_path);
});
