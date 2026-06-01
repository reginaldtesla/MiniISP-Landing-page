<?php

namespace App\Support;

use App\Models\PortalSetting;
use Illuminate\Support\Facades\Cache;

class PortalStatus
{
    protected const CACHE_KEY = 'portal_status_v1';

    public static function current(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addSeconds(10), function (): array {
            $s = PortalSetting::current();

            return [
                'outage_enabled' => (bool) $s->outage_enabled,
                'outage_message' => (string) ($s->outage_message ?? ''),
                'block_purchases' => (bool) $s->block_purchases,
                'block_connect' => (bool) $s->block_connect,
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function outageEnabled(): bool
    {
        return (bool) (self::current()['outage_enabled'] ?? false);
    }

    public static function shouldBlockPurchases(): bool
    {
        $c = self::current();

        return (bool) (($c['outage_enabled'] ?? false) && ($c['block_purchases'] ?? false));
    }

    public static function shouldBlockConnect(): bool
    {
        $c = self::current();

        return (bool) (($c['outage_enabled'] ?? false) && ($c['block_connect'] ?? false));
    }
}

