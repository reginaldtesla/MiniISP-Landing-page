<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\User;
use App\Support\PackageUsage;
use App\Support\PackageValidity;

class PackageQuotaService
{
    public function __construct(
        protected RadiusSyncService $radius,
        protected SessionDisconnectService $disconnect,
    ) {}

    /**
     * Align FreeRADIUS / MikroTik data cap with remaining bytes on the active purchase.
     */
    public function syncForUser(User $user): ?PackagePurchase
    {
        $purchase = PackageUsage::activePurchaseFor($user);

        if (! $purchase) {
            $this->blockDataAccess($user);

            return null;
        }

        if (PackageValidity::isUnlimited($purchase)) {
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

        $this->radius->applyDataLimitBytes($user, $purchase->speed_mbps, $remaining);

        return $purchase;
    }

    protected function blockDataAccess(User $user): void
    {
        // Removing Total-Limit on MikroTik often means unlimited; use a 1-byte cap instead.
        $this->radius->applyDataLimitBytes($user, null, 1);
    }

    protected function disconnectActiveSessions(User $user): void
    {
        $phone = $user->phone_number;

        if (! $phone) {
            return;
        }

        RadAcct::query()
            ->active()
            ->where('username', $phone)
            ->orderByDesc('acctstarttime')
            ->get()
            ->each(fn (RadAcct $session) => $this->disconnect->forceDisconnect($session));
    }
}
