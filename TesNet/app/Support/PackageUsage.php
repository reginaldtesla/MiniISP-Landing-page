<?php

namespace App\Support;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\RadReply;
use App\Models\User;
use App\Services\MikrotikApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PackageUsage
{
    protected static ?bool $hasBytesConsumedColumn = null;

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

        if ($includeMikrotik) {
            self::ingestPeakActiveSessionBytes($user, $phone);
        }

        $purchases = PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($purchases as $purchase) {
            self::refreshConsumption($purchase, $phone);

            if (self::isQuotaExhausted($purchase, $phone)) {
                $purchase->update(['status' => 'depleted']);
            }
        }
    }

    public static function ingestPeakActiveSessionBytes(User $user, string $phoneNumber): void
    {
        if (! self::$queryMikrotik) {
            return;
        }

        try {
            $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);
            $peak = $usage['used'] ?? 0;

            foreach (RadAcct::query()->active()->whereIn('username', self::usernameLookupVariants($phoneNumber))->get() as $session) {
                $peak = max($peak, $session->totalBytes());
            }

            if ($peak < 1) {
                return;
            }

            $purchase = PackagePurchase::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('activated_at')
                ->first();

            if (! $purchase) {
                return;
            }

            $measured = max((int) $purchase->bytes_consumed, $peak, self::bytesFromRadAcct($purchase, $phoneNumber));

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

        $sources = [
            $stored,
            self::bytesFromRadAcct($purchase, $phoneNumber),
            self::bytesFromRadReply($phoneNumber, $purchase),
        ];

        if (self::$queryMikrotik) {
            $sources[] = self::bytesFromMikrotik($phoneNumber);
        }

        return max(...$sources);
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
            $routerRemaining = self::bytesRemainingFromRouter($phoneNumber, $limitBytes);

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

            self::refreshConsumption($purchase, $phone);

            if (self::isQuotaExhausted($purchase, $phone) || ! self::hasDataRemaining($purchase, $phone)) {
                $purchase->update(['status' => 'depleted']);

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

    protected static function bytesFromRadReply(string $phoneNumber, PackagePurchase $purchase): int
    {
        $limitBytes = self::dataLimitBytesFor($purchase);

        if ($limitBytes < 1) {
            return 0;
        }

        foreach (self::usernameLookupVariants($phoneNumber) as $username) {
            $replyLimit = RadReply::query()
                ->where('username', $username)
                ->where('attribute', 'Mikrotik-Total-Limit')
                ->value('value');

            if ($replyLimit === null) {
                continue;
            }

            $remaining = (int) $replyLimit;

            if ($remaining > 0 && $remaining < $limitBytes) {
                return $limitBytes - $remaining;
            }
        }

        return 0;
    }

    protected static function bytesFromRadAcct(PackagePurchase $purchase, string $phoneNumber): int
    {
        try {
            $activatedAt = $purchase->activated_at?->copy()->subSecond() ?? now()->subYear();
            $total = 0;

            foreach (self::usernameLookupVariants($phoneNumber) as $username) {
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

    protected static function bytesFromMikrotik(string $phoneNumber): int
    {
        if (! self::$queryMikrotik) {
            return 0;
        }

        try {
            $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);

            return $usage['used'] ?? 0;
        } catch (Throwable $exception) {
            Log::warning('MikroTik usage lookup failed', ['error' => $exception->getMessage()]);

            return 0;
        }
    }

    protected static function bytesRemainingFromRouter(?string $phoneNumber, int $purchaseLimitBytes): ?int
    {
        if (! $phoneNumber || ! self::$queryMikrotik) {
            return null;
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

    /**
     * @return array<int, string>
     */
    protected static function usernameLookupVariants(string $phoneNumber): array
    {
        $variants = array_filter([$phoneNumber, PhoneNumber::normalize($phoneNumber)]);

        foreach ($variants as $variant) {
            if (str_starts_with($variant, '233') && strlen($variant) >= 12) {
                $variants[] = '0'.substr($variant, 3);
            }
        }

        return array_values(array_unique($variants));
    }
}
