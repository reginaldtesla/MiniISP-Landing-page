<?php

use App\Models\User;
use App\Services\LiveHotspotUsageService;

test('live usage endpoint requires authentication', function () {
    $this->get(route('portal.dashboard.live-usage'))
        ->assertRedirect(route('portal.login'));
});

test('live usage endpoint returns structured json', function () {
    $user = User::factory()->create([
        'phone_number' => '0240000001',
        'portal_session_version' => 1,
    ]);

    $this->mock(LiveHotspotUsageService::class, function ($mock) {
        $mock->shouldReceive('snapshot')->once()->andReturn([
            'ok' => true,
            'source' => 'mikrotik',
            'connected' => true,
            'api_enabled' => true,
            'session' => [
                'bytes_in_nice' => '10 MB',
                'bytes_out_nice' => '2 MB',
                'uptime_label' => '5m 0s',
            ],
            'plan' => [
                'has_active_plan' => true,
                'data_remaining_gb' => 4.5,
                'chart_stroke_offset' => 125.6,
            ],
            'polled_at' => now()->toIso8601String(),
        ]);
    });

    $this->actingAs($user)
        ->withSession(['portal_session_version' => 1])
        ->getJson(route('portal.dashboard.live-usage'))
        ->assertOk()
        ->assertJsonPath('connected', true)
        ->assertJsonPath('session.bytes_in_nice', '10 MB')
        ->assertJsonPath('plan.data_remaining_gb', 4.5);
});
