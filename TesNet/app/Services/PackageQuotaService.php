<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
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
        PackageUsage::syncConsumptionForUser($user, includeMikrotik: true);

        $purchase = PackageUsage::activePurchaseFor($user);

        if (! $purchase) {
            $this->blockDataAccess($user);

            return null;
        }

        if (PackageValidity::isUnlimited($purchase)) {
            $this->radius->setHotspotDataAllowed($user, true);
            $this->radius->applyDataLimitBytes($user, $purchase->speed_mbps, 0);

            return $purchase;
        }

        $remaining = PackageUsage::bytesRemaining($purchase, $user->phone_number) ?? 0;

        if ($remaining < 1) {
            if ($purchase->status === 'active') {
                $purchase->update(['status' => 'depleted']);
            }

            $this->blockDataAccess($user);
            $this->disconnectActiveSessions($user);

            return null;
        }

        $this->radius->setHotspotDataAllowed($user, true);
        $this->radius->applyDataLimitBytes($user, $purchase->speed_mbps, $remaining);

        return $purchase;
    }

    protected function blockDataAccess(User $user): void
    {
        try {
            $this->radius->setHotspotDataAllowed($user, false);
            $this->radius->applyDataLimitBytes($user, null, 1);
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

            RadAcct::query()
                ->active()
                ->whereIn('username', $usernames)
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
}
