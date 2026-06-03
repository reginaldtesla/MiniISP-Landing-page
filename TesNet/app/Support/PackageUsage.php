<?php

namespace App\Support;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use App\Services\HotspotPurchaseService;
use App\Services\MikrotikApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PackageUsage
{
    protected static ?bool $hasBytesConsumedColumn = null;

    protected static ?bool $hasLastRadiusLimitColumn = null;

    protected static bool $queryMikrotik = true;

    /**
     * Fast path for portal pages: radacct + DB only (no router API calls).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function forFastPortalDisplay(callable $callback): mixed
    {
        $previous = self::$queryMikrotik;
        self::$queryMikrotik = false;

        try {
            return $callback();
        } finally {
            self::$queryMikrotik = $previous;
        }
    }

    public static function activePurchaseForDisplay(User $user): ?PackagePurchase
    {
        return self::forFastPortalDisplay(fn () => self::activePurchaseFor($user));
    }

    public static function bytesRemainingForDisplay(PackagePurchase $purchase, ?string $phoneNumber): ?int
    {
        return self::forFastPortalDisplay(fn () => self::bytesRemaining($purchase, $phoneNumber));
    }

    public static function bytesRemainingWithRouter(PackagePurchase $purchase, ?string $phoneNumber): ?int
    {
        return self::withMikrotikQueries(fn () => self::bytesRemaining($purchase, $phoneNumber));
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withMikrotikQueries(callable $callback): mixed
    {
        $previous = self::$queryMikrotik;
        self::$queryMikrotik = true;

        try {
            return $callback();
        } finally {
            self::$queryMikrotik = $previous;
        }
    }

    public static function bytesUsed(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        if (self::tracksBytesConsumed()) {
            self::refreshConsumption($purchase, $phoneNumber);

            return (int) $purchase->bytes_consumed;
        }

        return self::measureBytesUsed($purchase, $phoneNumber);
    }

    public static function refreshConsumption(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        self::accountForCompletedSessionChunk($purchase, $phoneNumber);
        self::markExhaustedIfRouterQuotaHit($purchase, $phoneNumber);

        $measured = self::measureBytesUsed($purchase, $phoneNumber);

        if (! self::tracksBytesConsumed()) {
            return $measured;
        }

        try {
            if ($measured > (int) $purchase->bytes_consumed) {
                $purchase->update(['bytes_consumed' => $measured]);
                $purchase->bytes_consumed = $measured;
            }
        } catch (Throwable $exception) {
            Log::warning('Could not persist bytes_consumed', [
                'purchase_id' => $purchase->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return max($measured, (int) $purchase->bytes_consumed);
    }

    public static function syncConsumptionForUser(User $user, bool $includeMikrotik = true): void
    {
        $phone = $user->phone_number;

        if (! $phone) {
            return;
        }

        $purchase = self::consolidateActivePurchases($user);

        if (! $purchase) {
            return;
        }

        if ($includeMikrotik) {
            self::ingestPeakActiveSessionBytes($user, $phone, $purchase);
        }

        $usageUser = HotspotIdentity::usageUsername($purchase, $phone) ?? $phone;

        self::refreshConsumption($purchase, $usageUser);

        if (self::isQuotaExhausted($purchase, $usageUser)) {
            self::markDepleted($purchase);
        }
    }

    /**
     * Keep a single active purchase; retire and supersede stale actives (fixes duplicate quota / Connect).
     */
    public static function consolidateActivePurchases(User $user): ?PackagePurchase
    {
        $actives = PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->get();

        if ($actives->isEmpty()) {
            return null;
        }

        if ($actives->count() === 1) {
            return $actives->first();
        }

        $keeper = $actives->first();
        $hotspot = app(HotspotPurchaseService::class);

        foreach ($actives->skip(1) as $stale) {
            if (filled($stale->mikrotik_username)) {
                $hotspot->retire($stale, removeFromRouter: true);
            }

            $stale->update(['status' => 'superseded']);
        }

        Log::warning('Consolidated duplicate active package purchases', [
            'user_id' => $user->id,
            'kept_purchase_id' => $keeper->id,
            'superseded_count' => $actives->count() - 1,
        ]);

        return $keeper->fresh();
    }

    public static function markDepleted(PackagePurchase $purchase): void
    {
        if ($purchase->status === 'depleted') {
            return;
        }

        $purchase->update(['status' => 'depleted', 'last_radius_limit_bytes' => 0]);
        $purchase->status = 'depleted';

        if (filled($purchase->mikrotik_username)) {
            app(HotspotPurchaseService::class)->retire($purchase);
        }
    }

    public static function ingestPeakActiveSessionBytes(
        User $user,
        string $phoneNumber,
        ?PackagePurchase $purchase = null,
    ): void {
        if (! self::$queryMikrotik) {
            return;
        }

        $purchase ??= PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('activated_at')
            ->first();

        if (! $purchase) {
            return;
        }

        $usageUser = HotspotIdentity::usageUsername($purchase, $phoneNumber) ?? $phoneNumber;

        try {
            $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($usageUser);
            $peak = $usage['used'] ?? 0;

            foreach (RadAcct::query()->active()->whereIn('username', self::usernameLookupVariants($usageUser))->get() as $session) {
                $peak = max($peak, $session->totalBytes());
            }

            if ($peak < 1) {
                return;
            }

            $radSum = self::bytesFromRadAcct($purchase, $usageUser);

            if (self::isFreshPurchase($purchase) && $radSum < 1) {
                $peak = 0;
            }

            $measured = max((int) $purchase->bytes_consumed, $peak, $radSum);

            if (self::tracksBytesConsumed() && $measured > (int) $purchase->bytes_consumed) {
                $purchase->update(['bytes_consumed' => $measured]);
                $purchase->bytes_consumed = $measured;
            }
        } catch (Throwable $exception) {
            Log::warning('Could not ingest active session bytes', ['error' => $exception->getMessage()]);
        }
    }

    public static function measureBytesUsed(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        if (! $phoneNumber) {
            return (int) ($purchase->bytes_consumed ?? 0);
        }

        $stored = self::tracksBytesConsumed() ? (int) $purchase->bytes_consumed : 0;

        return max($stored, self::bytesFromRadAcct($purchase, $phoneNumber));
    }

    public static function isFreshPurchase(PackagePurchase $purchase): bool
    {
        if ((int) $purchase->bytes_consumed > 0) {
            return false;
        }

        $activatedAt = $purchase->activated_at;

        return $activatedAt !== null && $activatedAt->isAfter(now()->subMinutes(20));
    }

    /**
     * MikroTik applies Mikrotik-Total-Limit per hotspot session. When that session ends,
     * radacct often misses the last chunk — credit the last RADIUS cap if the user is offline.
     */
    public static function accountForCompletedSessionChunk(PackagePurchase $purchase, ?string $phoneNumber): void
    {
        if (! $phoneNumber || ! self::tracksBytesConsumed() || ! self::tracksLastRadiusLimitColumn()) {
            return;
        }

        $lastChunk = (int) ($purchase->last_radius_limit_bytes ?? 0);

        if ($lastChunk < 1 || self::userHasActiveSession($phoneNumber)) {
            return;
        }

        $limit = self::dataLimitBytesFor($purchase);

        if ($limit < 1) {
            return;
        }

        $radSum = self::bytesFromRadAcct($purchase, $phoneNumber);
        $newConsumed = min($limit, max((int) $purchase->bytes_consumed, $radSum + $lastChunk));

        if ($newConsumed <= (int) $purchase->bytes_consumed) {
            return;
        }

        $updates = [
            'bytes_consumed' => $newConsumed,
            'last_radius_limit_bytes' => 0,
        ];

        if ($newConsumed >= $limit) {
            $purchase->update($updates);
            $purchase->bytes_consumed = $newConsumed;
            $purchase->last_radius_limit_bytes = 0;
            self::markDepleted($purchase);

            return;
        }

        $purchase->update($updates);
        $purchase->bytes_consumed = $newConsumed;
        $purchase->last_radius_limit_bytes = 0;
    }

    public static function markExhaustedIfRouterQuotaHit(PackagePurchase $purchase, ?string $phoneNumber): void
    {
        if (! $phoneNumber || ! self::$queryMikrotik || PackageValidity::isUnlimited($purchase)) {
            return;
        }

        if (self::isFreshPurchase($purchase)) {
            return;
        }

        $limit = self::dataLimitBytesFor($purchase);

        if ($limit < 1) {
            return;
        }

        try {
            $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);
        } catch (Throwable) {
            return;
        }

        if ($usage === null || $usage['limit'] < 1 || $usage['used'] < $usage['limit']) {
            return;
        }

        $expectedCap = (int) ($purchase->last_radius_limit_bytes ?? 0);

        if ($expectedCap < 1) {
            $expectedCap = $limit;
        }

        $tolerance = max(1048576, (int) ($expectedCap * 0.2));

        if (abs($usage['limit'] - $expectedCap) > $tolerance) {
            return;
        }

        $purchase->update([
            'bytes_consumed' => $limit,
            'last_radius_limit_bytes' => 0,
        ]);
        $purchase->bytes_consumed = $limit;
        $purchase->last_radius_limit_bytes = 0;
        self::markDepleted($purchase);
    }

    public static function userHasActiveSession(?string $phoneNumber): bool
    {
        if (! $phoneNumber) {
            return false;
        }

        $variants = self::usernameLookupVariants($phoneNumber);

        if (RadAcct::query()->active()->whereIn('username', $variants)->exists()) {
            return true;
        }

        if (self::$queryMikrotik) {
            try {
                return app(MikrotikApiService::class)->peakActiveSessionBytes($phoneNumber) > 0;
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }

    public static function recordLastRadiusLimitBytes(PackagePurchase $purchase, int $limitBytes): void
    {
        if (! self::tracksLastRadiusLimitColumn() || $limitBytes < 1) {
            return;
        }

        $purchase->update(['last_radius_limit_bytes' => $limitBytes]);
        $purchase->last_radius_limit_bytes = $limitBytes;
    }

    public static function isQuotaExhausted(PackagePurchase $purchase, ?string $phoneNumber): bool
    {
        if (PackageValidity::isUnlimited($purchase)) {
            return false;
        }

        $limit = self::dataLimitBytesFor($purchase);

        if ($limit < 1) {
            return false;
        }

        return self::measureBytesUsed($purchase, $phoneNumber) >= $limit;
    }

    public static function dataLimitBytesFor(PackagePurchase $purchase): int
    {
        if (PackageValidity::isUnlimited($purchase)) {
            return 0;
        }

        if ($purchase->data_limit_bytes) {
            return (int) $purchase->data_limit_bytes;
        }

        return (int) $purchase->data_limit_mb * 1048576;
    }

    public static function bytesRemaining(PackagePurchase $purchase, ?string $phoneNumber): ?int
    {
        if (PackageValidity::isUnlimited($purchase)) {
            return null;
        }

        $limitBytes = self::dataLimitBytesFor($purchase);
        $used = self::bytesUsed($purchase, $phoneNumber);
        $remaining = max(0, $limitBytes - $used);

        if (self::$queryMikrotik) {
            $routerRemaining = self::bytesRemainingFromRouter($phoneNumber, $limitBytes, $purchase);

            if ($routerRemaining !== null) {
                $remaining = min($remaining, $routerRemaining);
            }
        }

        return $remaining;
    }

    public static function hasDataRemaining(PackagePurchase $purchase, ?string $phoneNumber): bool
    {
        if (PackageValidity::isUnlimited($purchase)) {
            return true;
        }

        return (self::bytesRemaining($purchase, $phoneNumber) ?? 0) > 0;
    }

    public static function activePurchaseFor(User $user): ?PackagePurchase
    {
        try {
            return self::resolveActivePurchase($user);
        } catch (Throwable $exception) {
            Log::error('activePurchaseFor failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected static function resolveActivePurchase(User $user): ?PackagePurchase
    {
        $phone = $user->phone_number;

        self::consolidateActivePurchases($user);

        $candidates = PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('activated_at')
            ->get();

        foreach ($candidates as $purchase) {
            if ($purchase->expires_at !== null && $purchase->expires_at->isPast()) {
                $purchase->update(['status' => 'expired']);

                continue;
            }

            $usageUser = HotspotIdentity::usageUsername($purchase, $phone) ?? $phone;

            self::refreshConsumption($purchase, $usageUser);

            if (self::isQuotaExhausted($purchase, $usageUser) || ! self::hasDataRemaining($purchase, $usageUser)) {
                self::markDepleted($purchase);

                continue;
            }

            return $purchase;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function usernameVariantsFor(string $phoneNumber): array
    {
        return self::usernameLookupVariants($phoneNumber);
    }

    protected static function bytesFromRadAcct(PackagePurchase $purchase, string $phoneNumber): int
    {
        try {
            $activatedAt = $purchase->activated_at?->copy()->subSecond() ?? now()->subYear();
            $total = 0;

            $usernames = HotspotIdentity::usesPerPurchase($purchase)
                ? array_filter([$purchase->mikrotik_username])
                : self::usernameLookupVariants($phoneNumber);

            foreach ($usernames as $username) {
                if (! $username) {
                    continue;
                }

                $total = max($total, (int) RadAcct::query()
                    ->where('username', $username)
                    ->where('acctstarttime', '>=', $activatedAt)
                    ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)')));
            }

            return $total;
        } catch (Throwable $exception) {
            Log::warning('radacct usage lookup failed', ['error' => $exception->getMessage()]);

            return 0;
        }
    }

    protected static function bytesRemainingFromRouter(
        ?string $phoneNumber,
        int $purchaseLimitBytes,
        ?PackagePurchase $purchase = null,
    ): ?int {
        if (! $phoneNumber || ! self::$queryMikrotik || ($purchase && self::isFreshPurchase($purchase))) {
            return null;
        }

        if ($purchase && HotspotIdentity::usesPerPurchase($purchase)) {
            $phoneNumber = $purchase->mikrotik_username ?? $phoneNumber;

            if (! str_starts_with($phoneNumber, 'tn-')) {
                return null;
            }
        }

        try {
            $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);
        } catch (Throwable $exception) {
            return null;
        }

        if ($usage === null) {
            return null;
        }

        if ($usage['limit'] > 0 && $usage['used'] >= $usage['limit']) {
            return 0;
        }

        $routerLimit = $usage['limit'] > 0 ? $usage['limit'] : $purchaseLimitBytes;

        if ($routerLimit < 1) {
            return null;
        }

        return max(0, $routerLimit - $usage['used']);
    }

    protected static function tracksBytesConsumed(): bool
    {
        if (self::$hasBytesConsumedColumn === null) {
            self::$hasBytesConsumedColumn = Schema::hasColumn('package_purchases', 'bytes_consumed');
        }

        return self::$hasBytesConsumedColumn;
    }

    protected static function tracksLastRadiusLimitColumn(): bool
    {
        if (self::$hasLastRadiusLimitColumn === null) {
            self::$hasLastRadiusLimitColumn = Schema::hasColumn('package_purchases', 'last_radius_limit_bytes');
        }

        return self::$hasLastRadiusLimitColumn;
    }

    /**
     * @return array<int, string>
     */
    protected static function usernameLookupVariants(string $phoneNumber): array
    {
        if (str_starts_with($phoneNumber, 'tn-')) {
            return [$phoneNumber];
        }

        $variants = array_filter([$phoneNumber, PhoneNumber::normalize($phoneNumber)]);

        foreach ($variants as $variant) {
            if (str_starts_with($variant, '233') && strlen($variant) >= 12) {
                $variants[] = '0'.substr($variant, 3);
            }
        }

        return array_values(array_unique($variants));
    }
}
