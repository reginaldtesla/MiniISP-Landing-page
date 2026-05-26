<?php

namespace App\Support;

use App\Models\DataPackage;
use App\Models\PackagePurchase;
use Illuminate\Support\Carbon;

class PackageValidity
{
    public const TYPE_DAYS = 'days';

    public const TYPE_UNTIL_FINISHED = 'until_finished';

    public const TYPE_UNLIMITED = 'unlimited';

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_DAYS => 'Fixed days',
            self::TYPE_UNTIL_FINISHED => 'Until finished',
            self::TYPE_UNLIMITED => 'Unlimited',
        ];
    }

    public static function typeForPackage(DataPackage $package): string
    {
        if ($package->is_special_offer) {
            return self::TYPE_DAYS;
        }

        return $package->validity_type ?: self::TYPE_DAYS;
    }

    public static function typeForPurchase(PackagePurchase $purchase): string
    {
        return $purchase->validity_type ?: self::TYPE_DAYS;
    }

    public static function isUnlimited(DataPackage|PackagePurchase $record): bool
    {
        $type = $record instanceof DataPackage
            ? self::typeForPackage($record)
            : self::typeForPurchase($record);

        return $type === self::TYPE_UNLIMITED;
    }

    public static function isUntilFinished(DataPackage|PackagePurchase $record): bool
    {
        $type = $record instanceof DataPackage
            ? self::typeForPackage($record)
            : self::typeForPurchase($record);

        return $type === self::TYPE_UNTIL_FINISHED;
    }

    public static function purchaseExpiresAt(DataPackage $package, ?Carbon $activatedAt = null): ?Carbon
    {
        $activatedAt = ($activatedAt ?? now())->copy();

        if ($package->is_special_offer && $package->special_ends_at) {
            return $package->special_ends_at->copy();
        }

        return match (self::typeForPackage($package)) {
            self::TYPE_DAYS => ($package->validity_days && $package->validity_days > 0)
                ? $activatedAt->addDays($package->validity_days)
                : null,
            self::TYPE_UNTIL_FINISHED, self::TYPE_UNLIMITED => null,
            default => null,
        };
    }

    public static function labelForPackage(DataPackage $package): string
    {
        if ($package->is_special_offer && $package->special_ends_at) {
            return 'Data expires '.$package->specialOfferEndsLabel();
        }

        return match (self::typeForPackage($package)) {
            self::TYPE_DAYS => ($package->validity_days && $package->validity_days > 0)
                ? 'Valid for '.self::daysLabel($package->validity_days)
                : 'Valid until data is used up',
            self::TYPE_UNTIL_FINISHED => 'Valid until data is used up',
            self::TYPE_UNLIMITED => 'Unlimited data',
            default => 'Valid until data is used up',
        };
    }

    public static function labelForPurchase(PackagePurchase $purchase): string
    {
        if ($purchase->expires_at !== null) {
            return 'Expires '.$purchase->expires_at->timezone(config('app.timezone'))->format('M j, Y g:i A');
        }

        return match (self::typeForPurchase($purchase)) {
            self::TYPE_UNLIMITED => 'Unlimited data',
            self::TYPE_UNTIL_FINISHED => 'Until data is used up',
            default => 'Until data is used up',
        };
    }

    public static function adminDurationLabel(DataPackage $package): string
    {
        if ($package->is_special_offer && $package->special_ends_at) {
            return 'Until '.$package->specialOfferEndsLabel();
        }

        return match (self::typeForPackage($package)) {
            self::TYPE_DAYS => self::daysLabel($package->validity_days),
            self::TYPE_UNTIL_FINISHED => 'Until finished',
            self::TYPE_UNLIMITED => 'Unlimited',
            default => '—',
        };
    }

    public static function daysLabel(?int $days): string
    {
        if (! $days || $days < 1) {
            return '—';
        }

        return $days === 1 ? '1 day' : $days.' days';
    }
}
