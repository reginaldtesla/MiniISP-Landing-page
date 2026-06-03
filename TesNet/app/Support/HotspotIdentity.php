<?php

namespace App\Support;

use App\Models\PackagePurchase;
use App\Models\User;

class HotspotIdentity
{
    public static function perPurchaseEnabled(): bool
    {
        return (bool) config('tesnet.per_purchase_hotspot', true);
    }

    public static function usesPerPurchase(PackagePurchase $purchase): bool
    {
        return self::perPurchaseEnabled()
            && filled($purchase->mikrotik_username);
    }

    public static function usernameForPurchase(PackagePurchase $purchase): string
    {
        return 'tn-'.$purchase->id;
    }

    public static function usageUsername(PackagePurchase $purchase, ?string $phoneFallback = null): ?string
    {
        if (self::usesPerPurchase($purchase)) {
            return $purchase->mikrotik_username;
        }

        return $phoneFallback;
    }

    public static function usageUsernameFor(User $user, PackagePurchase $purchase): ?string
    {
        return self::usageUsername($purchase, $user->phone_number);
    }
}
