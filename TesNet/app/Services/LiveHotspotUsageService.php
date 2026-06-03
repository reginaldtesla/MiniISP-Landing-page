<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use App\Support\BytesFormat;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use App\Support\PackageValidity;

class LiveHotspotUsageService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
    ) {}

    /**
     * Live hotspot usage from the MikroTik router (primary) plus plan quota for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $activePackage = PackageUsage::activePurchaseForDisplay($user);
        $usageUser = $activePackage
            ? HotspotIdentity::usageUsernameFor($user, $activePackage)
            : null;

        $usernames = $this->lookupUsernames($user, $activePackage);
        $radacctSession = $this->activeRadacctSession($usernames);

        $apiEnabled = $this->mikrotik->isEnabled();
        $session = $this->liveSessionFromRouter($usageUser);
        $routerMessage = null;

        if ($session === null && $apiEnabled && $usageUser) {
            $routerMessage = 'Could not read counters from the MikroTik router. Check API host, user, password, and firewall.';
        } elseif ($session === null && ! $apiEnabled) {
            $routerMessage = 'MikroTik API is disabled. Set MIKROTIK_API_ENABLED=true on the portal server.';
        }

        if ($session === null) {
            $session = $this->liveSessionFromRadacct($radacctSession);
        }

        $connected = ($session['on_active_session'] ?? false)
            || $radacctSession !== null;

        $source = $session['source'] ?? 'unavailable';

        $plan = $this->planStats($user, $activePackage, $usageUser, $apiEnabled && $usageUser !== null);

        return [
            'ok' => true,
            'source' => $source,
            'connected' => $connected,
            'api_enabled' => $apiEnabled,
            'router_message' => $routerMessage,
            'session' => $session,
            'plan' => $plan,
            'polled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function lookupUsernames(User $user, ?PackagePurchase $activePackage): array
    {
        $usernames = $user->phone_number
            ? PackageUsage::usernameVariantsFor($user->phone_number)
            : [];

        if ($activePackage?->mikrotik_username) {
            $usernames[] = $activePackage->mikrotik_username;
        }

        return array_values(array_unique($usernames));
    }

    protected function activeRadacctSession(array $usernames): ?RadAcct
    {
        if ($usernames === []) {
            return null;
        }

        return RadAcct::query()
            ->active()
            ->whereIn('username', $usernames)
            ->orderByDesc('acctstarttime')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function liveSessionFromRouter(?string $usageUser): ?array
    {
        if (! $usageUser || ! $this->mikrotik->isEnabled()) {
            return null;
        }

        $counters = $this->mikrotik->routerHotspotCounters($usageUser);

        if ($counters === null) {
            return null;
        }

        return $this->formatSessionPayload([
            'bytes_in' => $counters['bytes_in'],
            'bytes_out' => $counters['bytes_out'],
            'limit_bytes' => $counters['limit_bytes'],
            'uptime_seconds' => $counters['uptime_seconds'] ?? null,
            'uptime_label' => $counters['uptime_label'] ?? '—',
            'on_active_session' => $counters['on_active_session'],
        ], 'mikrotik');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function liveSessionFromRadacct(?RadAcct $session): ?array
    {
        if ($session === null) {
            return null;
        }

        $bytesIn = (int) ($session->acctinputoctets ?? 0);
        $bytesOut = (int) ($session->acctoutputoctets ?? 0);

        return $this->formatSessionPayload([
            'bytes_in' => $bytesIn,
            'bytes_out' => $bytesOut,
            'limit_bytes' => 0,
            'uptime_seconds' => (int) ($session->acctsessiontime ?? 0),
            'uptime_label' => BytesFormat::formatDurationSeconds((int) ($session->acctsessiontime ?? 0)),
            'on_active_session' => true,
        ], 'radacct');
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function formatSessionPayload(array $raw, string $source): array
    {
        $bytesIn = (int) ($raw['bytes_in'] ?? 0);
        $bytesOut = (int) ($raw['bytes_out'] ?? 0);
        $totalUsed = $bytesIn + $bytesOut;
        $limitBytes = (int) ($raw['limit_bytes'] ?? 0);
        $remainBytes = $limitBytes > 0 ? max(0, $limitBytes - $totalUsed) : null;

        $percentUsed = $limitBytes > 0
            ? min(100, (int) round(($totalUsed / $limitBytes) * 100))
            : null;

        $uptimeSeconds = $raw['uptime_seconds'] ?? BytesFormat::parseRouterUptimeToSeconds($raw['uptime'] ?? null);
        $uptimeLabel = $raw['uptime_label'] ?? BytesFormat::formatDurationSeconds(
            is_int($uptimeSeconds) ? $uptimeSeconds : null
        );

        return [
            'source' => $source,
            'on_active_session' => (bool) ($raw['on_active_session'] ?? false),
            'bytes_in' => $bytesIn,
            'bytes_out' => $bytesOut,
            'bytes_total' => $totalUsed,
            'bytes_in_nice' => BytesFormat::nice($bytesIn),
            'bytes_out_nice' => BytesFormat::nice($bytesOut),
            'bytes_total_nice' => BytesFormat::nice($totalUsed),
            'limit_bytes' => $limitBytes > 0 ? $limitBytes : null,
            'limit_nice' => $limitBytes > 0 ? BytesFormat::nice($limitBytes) : null,
            'remain_bytes' => $remainBytes,
            'remain_nice' => $remainBytes !== null ? BytesFormat::nice($remainBytes) : null,
            'percent_used' => $percentUsed,
            'uptime_seconds' => $uptimeSeconds,
            'uptime_label' => $uptimeLabel,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function planStats(
        User $user,
        ?PackagePurchase $activePackage,
        ?string $usageUser,
        bool $syncFromRouter,
    ): array {
        if ($activePackage === null) {
            return [
                'has_active_plan' => false,
                'is_unlimited' => false,
                'data_remaining_gb' => null,
                'total_plan_gb' => null,
                'percent_remaining' => 0,
                'chart_stroke_offset' => 251.2,
            ];
        }

        if ($syncFromRouter && $usageUser) {
            PackageUsage::ingestPeakActiveSessionBytes($user, $user->phone_number ?? '');
            PackageUsage::refreshConsumption($activePackage, $usageUser);
            $activePackage->refresh();
        }

        $isUnlimited = PackageValidity::isUnlimited($activePackage);

        if ($isUnlimited) {
            return [
                'has_active_plan' => true,
                'is_unlimited' => true,
                'data_remaining_gb' => 'Unlimited',
                'total_plan_gb' => 'Unlimited',
                'percent_remaining' => 100,
                'chart_stroke_offset' => 0,
            ];
        }

        $dataLimitBytes = PackageUsage::dataLimitBytesFor($activePackage);
        $bytesRemaining = $this->planBytesRemaining($activePackage, $usageUser);
        $percentRemaining = $dataLimitBytes > 0
            ? min(100, max(0, (($bytesRemaining ?? 0) / $dataLimitBytes) * 100))
            : 0;

        $chartCircumference = 251.2;

        return [
            'has_active_plan' => true,
            'is_unlimited' => false,
            'data_remaining_gb' => round(($bytesRemaining ?? 0) / 1073741824, 1),
            'total_plan_gb' => $dataLimitBytes > 0
                ? round($dataLimitBytes / 1073741824, 2)
                : 0,
            'bytes_remaining' => $bytesRemaining,
            'bytes_remaining_nice' => BytesFormat::nice((int) ($bytesRemaining ?? 0)),
            'percent_remaining' => $percentRemaining,
            'chart_stroke_offset' => $chartCircumference * (1 - $percentRemaining / 100),
        ];
    }

    protected function planBytesRemaining(PackagePurchase $purchase, ?string $usageUser): ?int
    {
        return PackageUsage::bytesRemainingWithRouter($purchase, $usageUser);
    }
}
