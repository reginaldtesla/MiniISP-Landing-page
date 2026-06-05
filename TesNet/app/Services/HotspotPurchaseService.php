<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\User;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use App\Support\PackageValidity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotspotPurchaseService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
        protected RadiusSyncService $radius,
    ) {}

    public function assignIdentity(PackagePurchase $purchase, User $user): PackagePurchase
    {
        $password = Str::password(32, letters: true, numbers: true, symbols: false);

        $purchase->update([
            'mikrotik_username' => HotspotIdentity::usernameForPurchase($purchase),
            'mikrotik_password' => $password,
            'mikrotik_profile' => $this->profileFor($purchase),
            'mikrotik_synced_at' => null,
        ]);

        $purchase->refresh();

        return $purchase;
    }

    public function profileFor(PackagePurchase $purchase): string
    {
        if ($purchase->package_slug === 'custom') {
            return (string) config('tesnet.hotspot_profiles.custom', 'tesnet-custom');
        }

        return (string) config('tesnet.hotspot_profiles.package', 'tesnet-pkg');
    }

    public function retire(PackagePurchase $purchase, bool $removeFromRouter = false): void
    {
        $username = $purchase->mikrotik_username;

        if (! $username) {
            return;
        }

        if ($this->mikrotik->isEnabled()) {
            $this->mikrotik->disconnectHotspotUser($username);

            if ($removeFromRouter) {
                $this->mikrotik->removeHotspotUser($username);
            } else {
                $this->mikrotik->setHotspotUserDisabled($username, true);
            }
        }

        $this->radius->removePurchaseUser($purchase);
    }

    public function retireActivePurchasesFor(User $user, bool $removeFromRouter = false): void
    {
        PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('mikrotik_username')
            ->get()
            ->each(fn (PackagePurchase $purchase) => $this->retire($purchase, $removeFromRouter));
    }

    /**
     * Stop legacy phone-number hotspot logins (Model A uses tn-{purchase_id} only).
     */
    public function purgeLegacyPhoneHotspot(User $user): void
    {
        $phone = $user->phone_number;

        if (! $phone || ! HotspotIdentity::perPurchaseEnabled()) {
            return;
        }

        if ($this->mikrotik->isEnabled()) {
            foreach (PackageUsage::usernameVariantsFor($phone) as $username) {
                $this->mikrotik->disconnectHotspotUser($username);
                $this->mikrotik->resetHotspotUsageForUser($username);
            }
        }
    }

    public function provision(PackagePurchase $purchase, User $user): bool
    {
        if (! HotspotIdentity::perPurchaseEnabled()) {
            return false;
        }

        $password = $purchase->hotspotLoginPassword();

        if (! $purchase->mikrotik_username || ! $password) {
            $purchase = $this->assignIdentity($purchase, $user);
            $password = $purchase->hotspotLoginPassword();
        }

        if (! $password) {
            return false;
        }

        $limitBytes = PackageUsage::dataLimitBytesFor($purchase);
        $comment = trim(($user->phone_number ?? '').' · '.$purchase->package_name);
        $uptimeSeconds = $this->limitUptimeSeconds($purchase);

        $routerOk = true;

        if ($this->mikrotik->isEnabled()) {
            $routerOk = $this->mikrotik->upsertHotspotUser(
                $purchase->mikrotik_username,
                $password,
                $purchase->mikrotik_profile ?? $this->profileFor($purchase),
                $limitBytes,
                $comment,
                $uptimeSeconds,
            );

            if (! $routerOk) {
                Log::error('MikroTik hotspot user provision failed', [
                    'purchase_id' => $purchase->id,
                    'username' => $purchase->mikrotik_username,
                ]);
            }
        }

        $this->radius->syncPurchaseUser($purchase, $password);
        $this->radius->clearHotspotDataLimits($user);
        $this->radius->setPhoneHotspotLoginAllowed($user, false);
        $this->purgeLegacyPhoneHotspot($user);

        if ($routerOk || ! $this->mikrotik->isEnabled()) {
            $purchase->update(['mikrotik_synced_at' => now()]);

            return true;
        }

        return false;
    }

    public function ensureProvisioned(PackagePurchase $purchase, User $user, bool $force = false): bool
    {
        if (! $force && $purchase->mikrotik_synced_at !== null && $purchase->status === 'active') {
            return true;
        }

        return $this->provision($purchase, $user);
    }

    protected function limitUptimeSeconds(PackagePurchase $purchase): ?int
    {
        if ($purchase->expires_at === null) {
            return null;
        }

        $seconds = now()->diffInSeconds($purchase->expires_at, false);

        return $seconds > 0 ? $seconds : null;
    }
}
