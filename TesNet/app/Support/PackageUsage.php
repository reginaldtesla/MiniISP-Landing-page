<?php

namespace App\Support;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use App\Services\MikrotikApiService;
use Illuminate\Support\Facades\DB;

class PackageUsage
{
    public static function bytesUsed(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        self::refreshConsumption($purchase, $phoneNumber);

        return (int) $purchase->bytes_consumed;
    }

    public static function refreshConsumption(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        $measured = self::measureBytesUsed($purchase, $phoneNumber);

        if ($measured > (int) $purchase->bytes_consumed) {
            $purchase->update(['bytes_consumed' => $measured]);
            $purchase->bytes_consumed = $measured;
        }

        return (int) $purchase->bytes_consumed;
    }

    public static function measureBytesUsed(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        if (! $phoneNumber) {
            return (int) $purchase->bytes_consumed;
        }

        return max(
            (int) $purchase->bytes_consumed,
            self::bytesFromRadAcct($purchase, $phoneNumber),
            self::bytesFromMikrotik($phoneNumber),
        );
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

        $routerRemaining = self::bytesRemainingFromRouter($phoneNumber, $limitBytes);

        if ($routerRemaining !== null) {
            $remaining = min($remaining, $routerRemaining);
        }

        return $remaining;
    }

    public static function hasDataRemaining(PackagePurchase $purchase, ?string $phoneNumber): bool
    {
        if (PackageValidity::isUnlimited($purchase)) {
            return true;
        }

        if ($phoneNumber && app(MikrotikApiService::class)->hotspotQuotaExhausted($phoneNumber)) {
            return false;
        }

        return (self::bytesRemaining($purchase, $phoneNumber) ?? 0) > 0;
    }

    public static function activePurchaseFor(User $user): ?PackagePurchase
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

            if (self::hasDataRemaining($purchase, $phone)) {
                return $purchase;
            }

            $purchase->update(['status' => 'depleted']);
        }

        return null;
    }

    protected static function bytesFromRadAcct(PackagePurchase $purchase, string $phoneNumber): int
    {
        $activatedAt = $purchase->activated_at?->copy()->subSecond() ?? now()->subYear();
        $total = 0;

        foreach (self::usernameLookupVariants($phoneNumber) as $username) {
            $total = max($total, (int) RadAcct::query()
                ->where('username', $username)
                ->where('acctstarttime', '>=', $activatedAt)
                ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)')));
        }

        return $total;
    }

    protected static function bytesFromMikrotik(string $phoneNumber): int
    {
        $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);

        return $usage['used'] ?? 0;
    }

    protected static function bytesRemainingFromRouter(?string $phoneNumber, int $purchaseLimitBytes): ?int
    {
        if (! $phoneNumber) {
            return null;
        }

        $usage = app(MikrotikApiService::class)->hotspotDataUsageForUser($phoneNumber);

        if ($usage === null) {
            return null;
        }

        $routerLimit = $usage['limit'] > 0 ? $usage['limit'] : $purchaseLimitBytes;

        if ($routerLimit < 1) {
            return null;
        }

        return max(0, $routerLimit - $usage['used']);
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
