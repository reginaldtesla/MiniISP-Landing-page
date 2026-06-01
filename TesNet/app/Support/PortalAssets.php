<?php

namespace App\Support;

class PortalAssets
{
    public const OFFLINE_DIR = 'assets/portal';

    public static function useOffline(): bool
    {
        if (! config('portal.use_offline_assets', true)) {
            return false;
        }

        return file_exists(public_path(self::OFFLINE_DIR.'/portal.css'));
    }

    public static function offlinePath(string $filename): string
    {
        return public_path(self::OFFLINE_DIR.'/'.$filename);
    }

    public static function assetUrl(string $filename): ?string
    {
        if (! file_exists(self::offlinePath($filename))) {
            return null;
        }

        return asset(self::OFFLINE_DIR.'/'.$filename);
    }

    /**
     * Vite entry path for optional page scripts (dev fallback only).
     */
    public static function viteEntry(string $offlineFilename): ?string
    {
        return match ($offlineFilename) {
            'portal-announcements.js' => 'resources/js/portal-announcements.js',
            'portal-custom-calculator.js' => 'resources/js/portal-custom-calculator.js',
            'portal-plan-countdown.js' => 'resources/js/portal-plan-countdown.js',
            default => null,
        };
    }

    public static function bundleReady(): bool
    {
        return file_exists(self::offlinePath('portal.css'))
            && file_exists(self::offlinePath('portal-theme.js'));
    }
}
