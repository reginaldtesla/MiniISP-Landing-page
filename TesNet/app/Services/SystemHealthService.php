<?php

namespace App\Services;

use App\Models\RadAcct;
use App\Support\SessionConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
        protected PaystackService $paystack,
    ) {}

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public function checks(): array
    {
        return [
            $this->checkDatabase(),
            $this->checkRadiusTables(),
            $this->checkRadiusAccounting(),
            $this->checkMikrotikApi(),
            $this->checkDiskSpace(),
            $this->checkPaystackConfigured(),
            $this->checkOfflineAssets(),
            $this->checkPortalSession(),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkPortalSession(): array
    {
        $diag = SessionConfig::diagnostics();

        if ($diag['issues'] !== []) {
            return $this->result(
                'portal_session',
                'Portal session (419 risk)',
                'fail',
                implode(' ', $diag['issues'])
            );
        }

        return $this->result(
            'portal_session',
            'Portal session (419 risk)',
            'ok',
            'APP_URL and session cookie settings look OK for captive portal.'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $ok = DB::selectOne('SELECT 1 AS ok');

            return $this->result('database', 'Database connection', $ok ? 'ok' : 'fail', 'MariaDB/MySQL reachable.');
        } catch (\Throwable $e) {
            return $this->result('database', 'Database connection', 'fail', $e->getMessage());
        }
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkRadiusTables(): array
    {
        $tables = ['radcheck', 'radreply', 'radacct'];
        $missing = array_filter($tables, fn ($t) => ! Schema::hasTable($t));

        if ($missing !== []) {
            return $this->result(
                'radius_tables',
                'FreeRADIUS SQL tables',
                'fail',
                'Missing: '.implode(', ', $missing).'. Run migrations.'
            );
        }

        return $this->result('radius_tables', 'FreeRADIUS SQL tables', 'ok', 'radcheck, radreply, radacct present.');
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkRadiusAccounting(): array
    {
        if (! Schema::hasTable('radacct')) {
            return $this->result('radius_accounting', 'RADIUS accounting', 'warn', 'radacct table missing.');
        }

        if (! Schema::hasColumn('radacct', 'framedipv6address')) {
            return $this->result(
                'radius_accounting',
                'RADIUS accounting',
                'fail',
                'radacct is missing framedipv6address (and related IPv6 columns). Run php artisan migrate — FreeRADIUS accounting INSERT/UPDATE will fail until fixed.'
            );
        }

        $latest = RadAcct::query()->max('acctupdatetime');
        $staleHours = config('tesnet.monitor.radius_stale_hours', 6);

        if ($latest === null) {
            return $this->result(
                'radius_accounting',
                'RADIUS accounting',
                'warn',
                'No accounting rows yet. Connect a test user and confirm MikroTik sends interim updates.'
            );
        }

        $latestAt = \Illuminate\Support\Carbon::parse($latest);
        $hoursAgo = $latestAt->diffInHours(now());

        if ($hoursAgo > $staleHours) {
            return $this->result(
                'radius_accounting',
                'RADIUS accounting',
                'warn',
                "Last radacct update {$hoursAgo}h ago ({$latestAt->format('M j, H:i')}). Check MikroTik accounting + FreeRADIUS SQL."
            );
        }

        return $this->result(
            'radius_accounting',
            'RADIUS accounting',
            'ok',
            'Last update '.$latestAt->format('M j, H:i').' ('.$hoursAgo.'h ago).'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkMikrotikApi(): array
    {
        if (! $this->mikrotik->isEnabled()) {
            return $this->result(
                'mikrotik_api',
                'MikroTik API (disconnect)',
                'warn',
                'MIKROTIK_API_ENABLED=false. Admin can still mark sessions stopped in DB; router kick requires API.'
            );
        }

        $test = $this->mikrotik->testConnection();

        return $this->result(
            'mikrotik_api',
            'MikroTik API (disconnect)',
            ($test['ok'] ?? false) ? 'ok' : 'fail',
            $test['message'] ?? 'MikroTik API test failed.'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkDiskSpace(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return $this->result('disk', 'Disk space', 'warn', 'Could not read disk stats for '.$path);
        }

        $freePercent = (int) round(($free / $total) * 100);
        $min = config('tesnet.monitor.disk_free_percent_min', 10);

        if ($freePercent < $min) {
            return $this->result(
                'disk',
                'Disk space',
                'fail',
                "Only {$freePercent}% free on storage volume. Free space or expand disk (min {$min}% recommended)."
            );
        }

        return $this->result(
            'disk',
            'Disk space',
            'ok',
            "{$freePercent}% free (".round($free / 1073741824, 1).' GB available).'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkPaystackConfigured(): array
    {
        $test = $this->paystack->testConnection();

        if (! ($test['configured'] ?? false)) {
            return $this->result(
                'paystack',
                'Paystack API',
                'warn',
                $test['message'] ?? 'Paystack not configured.'
            );
        }

        return $this->result(
            'paystack',
            'Paystack API',
            ($test['ok'] ?? false) ? 'ok' : 'fail',
            $test['message'] ?? 'Paystack API test failed.'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function checkOfflineAssets(): array
    {
        $ready = \App\Support\PortalAssets::bundleReady();

        return $this->result(
            'offline_assets',
            'Offline portal assets',
            $ready ? 'ok' : 'warn',
            $ready ? 'public/assets/portal bundle present.' : 'Run: npm run build:offline'
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected function result(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }

    public function hasFailures(): bool
    {
        return collect($this->checks())->contains(fn ($c) => $c['status'] === 'fail');
    }
}
