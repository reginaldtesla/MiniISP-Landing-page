<?php

use App\Models\User;
use App\Services\SingleDeviceGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('student device limit is forced to one on save', function () {
    config(['tesnet.student_device_limit' => 1]);

    $user = User::factory()->create(['device_limit' => 5]);
    $user->device_limit = 9;
    $user->save();

    expect($user->fresh()->device_limit)->toBe(1);
});

test('bind portal session increments version', function () {
    $user = User::factory()->create(['portal_session_version' => 2]);

    $version = app(SingleDeviceGuard::class)->bindPortalSession($user);

    expect($version)->toBe(3)
        ->and($user->fresh()->portal_session_version)->toBe(3);
});

test('admin users may keep higher device limit', function () {
    config(['tesnet.student_device_limit' => 1]);

    $admin = User::factory()->create([
        'is_admin' => true,
        'device_limit' => 5,
    ]);
    $admin->device_limit = 7;
    $admin->save();

    expect($admin->fresh()->device_limit)->toBe(7);
});
