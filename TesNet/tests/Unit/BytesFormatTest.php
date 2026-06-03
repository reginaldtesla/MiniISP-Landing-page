<?php

use App\Support\BytesFormat;

test('formats bytes into human readable units', function () {
    expect(BytesFormat::nice(512))->toBe('512 B');
    expect(BytesFormat::nice(1536))->toBe('1.5 KB');
    expect(BytesFormat::nice(1048576))->toBe('1 MB');
});

test('parses router uptime strings', function () {
    expect(BytesFormat::parseRouterUptimeToSeconds('1h2m3s'))->toBe(3723);
    expect(BytesFormat::formatDurationSeconds(3723))->toBe('1h 2m 3s');
});
