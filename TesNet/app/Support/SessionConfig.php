<?php

namespace App\Support;

use Illuminate\Http\Request;

class SessionConfig
{
    /**
     * Fix session cookies for HTTP captive portal (192.168.88.x) and invalid .env values.
     */
    public static function applyForHttpRequest(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $request = app(Request::class);

        self::normalizeDomain();

        if (! $request->isSecure()) {
            config([
                'session.secure' => false,
                'session.partitioned' => false,
            ]);
        }

        $sameSite = config('session.same_site');

        if ($sameSite === 'null' || $sameSite === '') {
            config(['session.same_site' => null]);
        }
    }

    protected static function normalizeDomain(): void
    {
        $domain = config('session.domain');

        if ($domain === null || $domain === '' || $domain === 'null') {
            config(['session.domain' => null]);

            return;
        }

        if (filter_var($domain, FILTER_VALIDATE_IP) || str_contains((string) $domain, ':')) {
            config(['session.domain' => null]);
        }
    }

    public static function diagnostics(): array
    {
        $configCached = is_file(base_path('bootstrap/cache/config.php'));
        $appUrl = (string) config('app.url');
        $sessionsTable = \Illuminate\Support\Facades\Schema::hasTable('sessions');

        $issues = [];

        if ($configCached) {
            $issues[] = 'Config is cached (bootstrap/cache/config.php). Run: php artisan config:clear';
        }

        if (! str_starts_with($appUrl, 'http://192.168.88.') && ! str_starts_with($appUrl, 'http://localhost')) {
            $issues[] = "APP_URL is {$appUrl} — for LAN captive portal use http://192.168.88.2";
        }

        if (config('session.secure') && str_starts_with($appUrl, 'http://')) {
            $issues[] = 'SESSION_SECURE_COOKIE=true with HTTP APP_URL — browsers will not store session (419). Set SESSION_SECURE_COOKIE=false';
        }

        $domain = config('session.domain');

        if ($domain && (filter_var($domain, FILTER_VALIDATE_IP) || $domain === 'null')) {
            $issues[] = 'SESSION_DOMAIN must be empty — do not set an IP address as cookie domain';
        }

        if (config('session.driver') === 'database' && ! $sessionsTable) {
            $issues[] = 'SESSION_DRIVER=database but sessions table missing — run php artisan migrate';
        }

        return [
            'config_cached' => $configCached,
            'app_url' => $appUrl,
            'session_driver' => config('session.driver'),
            'session_secure' => (bool) config('session.secure'),
            'session_domain' => $domain,
            'session_same_site' => config('session.same_site'),
            'session_encrypt' => (bool) config('session.encrypt'),
            'sessions_table' => $sessionsTable,
            'issues' => $issues,
        ];
    }
}
