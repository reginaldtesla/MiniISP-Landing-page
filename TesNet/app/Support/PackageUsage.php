<?php

namespace App\Support;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PackageUsage
{
    public static function bytesUsed(PackagePurchase $purchase, ?string $phoneNumber): int
    {
        if (! $phoneNumber) {
            return 0;
        }

        return (int) RadAcct::query()
            ->where('username', $phoneNumber)
            ->where('acctstarttime', '>=', $purchase->activated_at)
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
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

        return max(0, $limitBytes - self::bytesUsed($purchase, $phoneNumber));
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

            if (self::hasDataRemaining($purchase, $user->phone_number)) {
                return $purchase;
            }

            $purchase->update(['status' => 'depleted']);
        }

        return null;
    }
}
