<?php

use App\Models\User;
use App\Services\SingleDeviceGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stale portal session is rejected on dashboard', function () {
    $user = User::factory()->create(['portal_session_version' => 5]);

    $this->actingAs($user)
        ->withSession(['portal_session_version' => 2])
        ->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));

    expect(auth()->check())->toBeFalse();
});

test('matching portal session version allows dashboard', function () {
    $user = User::factory()->create(['portal_session_version' => 3]);

    $this->actingAs($user)
        ->withSession(['portal_session_version' => 3])
        ->get(route('portal.dashboard'))
        ->assertOk();
});

test('login binds portal session version', function () {
    $this->mock(SingleDeviceGuard::class, function ($mock) {
        $mock->shouldReceive('disconnectOtherHotspotSessions')->once();
        $mock->shouldReceive('bindPortalSession')->once()->andReturn(7);
    });

    $user = User::factory()->create([
        'phone_number' => '233551111222',
        'password' => 'secret12',
        'portal_session_version' => 0,
    ]);

    $response = $this->post(route('portal.login'), [
        'phone_number' => '0551111222',
        'password' => 'secret12',
    ]);

    $response->assertRedirect();
    expect(session('portal_session_version'))->toBe(7);
});
