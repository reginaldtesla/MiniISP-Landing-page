<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use App\Support\PackageValidity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PackageQuotaService
{
    public function __construct(
        protected RadiusSyncService $radius,
        protected SessionDisconnectService $disconnect,
        protected MikrotikApiService $mikrotik,
        protected HotspotPurchaseService $hotspotPurchase,
    ) {}

    /**
     * Align FreeRADIUS / MikroTik data cap with remaining bytes on the active purchase.
     */
    public function syncForUser(User $user, bool $force = false): ?PackagePurchase
    {
        $cacheKey = 'package_quota_sync:'.$user->id;

        if (! $force && Cache::has($cacheKey)) {
            return PackageUsage::activePurchaseForDisplay($user);
        }

        try {
            $purchase = $this->performSync($user);
            Cache::put($cacheKey, true, now()->addSeconds((int) config('tesnet.quota_sync_cooldown_seconds', 45)));

            return $purchase;
        } catch (Throwable $exception) {
            Log::error('Package quota sync failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return PackageUsage::activePurchaseFor($user);
        }
    }

    protected function performSync(User $user): ?PackagePurchase
    {
        PackageUsage::consolidateActivePurchases($user);
        PackageUsage::syncConsumptionForUser($user, includeMikrotik: true);

        $purchase = PackageUsage::activePurchaseFor($user);

        if (! $purchase) {
            $this->blockDataAccess($user);

            return null;
        }

        if (HotspotIdentity::usesPerPurchase($purchase)) {
            return $this->performSyncPerPurchase($user, $purchase);
        }

        if (PackageValidity::isUnlimited($purchase)) {
            $this->radius->setHotspotDataAllowed($user, true);
            $this->radius->applyDataLimitBytes($user, $purchase->speed_mbps, 0);

            return $purchase;
        }

        $remaining = PackageUsage::bytesRemaining($purchase, $user->phone_number) ?? 0;

        if ($remaining < 1) {
            if ($purchase->status === 'active') {
                $purchase->update([
                    'status' => 'depleted',
                    'last_radius_limit_bytes' => 0,
                ]);
            }

            $this->blockDataAccess($user);
            $this->disconnectActiveSessions($user);

            return null;
        }

        $this->radius->setHotspotDataAllowed($user, true);
        $this->radius->applyDataLimitBytes($user, $purchase->speed_mbps, $remaining);
        PackageUsage::recordLastRadiusLimitBytes($purchase, $remaining);

        return $purchase;
    }

    protected function performSyncPerPurchase(User $user, PackagePurchase $purchase): ?PackagePurchase
    {
        $usageUser = HotspotIdentity::usageUsernameFor($user, $purchase);

        $this->radius->clearHotspotDataLimits($user);
        $this->radius->setPhoneHotspotLoginAllowed($user, false);
        $this->hotspotPurchase->purgeLegacyPhoneHotspot($user);

        if (PackageValidity::isUnlimited($purchase)) {
            $this->hotspotPurchase->ensureProvisioned($purchase, $user);

            return $purchase;
        }

        $remaining = PackageUsage::bytesRemainingWithRouter($purchase, $usageUser) ?? 0;

        if ($remaining < 1) {
            PackageUsage::markDepleted($purchase);
            $this->disconnectPurchaseSessions($purchase, $usageUser);
            Cache::forget('portal_connected:'.$user->id);

            return null;
        }

        $this->hotspotPurchase->ensureProvisioned($purchase, $user, force: true);

        return $purchase->fresh();
    }

    protected function blockDataAccess(User $user): void
    {
        try {
            $this->radius->clearHotspotDataLimits($user);
            $this->radius->setHotspotDataAllowed($user, true);
            $this->radius->applyDataLimitBytes($user, null, 1);
            $this->disconnectActiveSessions($user);
            $this->kickAllHotspotSessions($user);
            Cache::forget('portal_connected:'.$user->id);
        } catch (Throwable $exception) {
            Log::warning('Could not block RADIUS data access', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function disconnectActiveSessions(User $user): void
    {
        $phone = $user->phone_number;

        if (! $phone) {
            return;
        }

        try {
            $usernames = PackageUsage::usernameVariantsFor($phone);

            $activePurchase = PackageUsage::activePurchaseForDisplay($user);

            if ($activePurchase?->mikrotik_username) {
                $usernames[] = $activePurchase->mikrotik_username;
            }

            RadAcct::query()
                ->active()
                ->whereIn('username', array_unique($usernames))
                ->orderByDesc('acctstarttime')
                ->get()
                ->each(fn (RadAcct $session) => $this->disconnect->forceDisconnect($session));
        } catch (Throwable $exception) {
            Log::warning('Could not disconnect active sessions', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function disconnectPurchaseSessions(PackagePurchase $purchase, ?string $usageUser): void
    {
        if (! $usageUser) {
            return;
        }

        try {
            RadAcct::query()
                ->active()
                ->whereIn('username', PackageUsage::usernameVariantsFor($usageUser))
                ->orderByDesc('acctstarttime')
                ->get()
                ->each(fn (RadAcct $session) => $this->disconnect->forceDisconnect($session));

            $this->mikrotik->disconnectHotspotUser($usageUser);
        } catch (Throwable $exception) {
            Log::warning('Could not disconnect purchase hotspot sessions', [
                'purchase_id' => $purchase->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function kickAllHotspotSessions(User $user): void
    {
        if (! $this->mikrotik->isEnabled() || ! $user->phone_number) {
            return;
        }

        try {
            foreach (PackageUsage::usernameVariantsFor($user->phone_number) as $username) {
                $this->mikrotik->disconnectHotspotUser($username);
            }

            $purchase = PackageUsage::activePurchaseForDisplay($user);

            if ($purchase?->mikrotik_username) {
                $this->mikrotik->disconnectHotspotUser($purchase->mikrotik_username);
            }
        } catch (Throwable $exception) {
            Log::warning('Could not kick MikroTik hotspot sessions', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
