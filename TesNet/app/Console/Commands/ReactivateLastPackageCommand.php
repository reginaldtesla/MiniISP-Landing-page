<?php

namespace App\Console\Commands;

use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\HotspotPurchaseService;
use App\Services\PackageQuotaService;
use App\Support\HotspotIdentity;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class ReactivateLastPackageCommand extends Command
{
    protected $signature = 'tesnet:reactivate-last-package {phone : User phone number}';

    protected $description = 'Reactivate the latest package purchase and reprovision per-purchase hotspot (tn-*)';

    public function handle(HotspotPurchaseService $hotspotPurchase, PackageQuotaService $quota): int
    {
        $phone = PhoneNumber::normalize((string) $this->argument('phone'));

        $user = User::query()
            ->where('phone_number', $phone)
            ->orWhere('phone_number', $this->argument('phone'))
            ->first();

        if (! $user) {
            $this->error("No user found for phone: {$phone}");

            return self::FAILURE;
        }

        $purchase = PackagePurchase::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $purchase) {
            $this->error('No package purchase found for this user.');

            return self::FAILURE;
        }

        $purchase->update([
            'status' => 'active',
            'bytes_consumed' => 0,
            'last_radius_limit_bytes' => 0,
            'mikrotik_synced_at' => null,
        ]);

        if (HotspotIdentity::perPurchaseEnabled()) {
            if (! $purchase->mikrotik_username) {
                $purchase = $hotspotPurchase->assignIdentity($purchase, $user);
            }

            if (! $hotspotPurchase->provision($purchase, $user)) {
                $this->warn('Purchase reactivated in DB but MikroTik provision failed — check API and profiles.');
            }
        }

        $active = $quota->syncForUser($user, force: true);

        if ($active === null) {
            $this->warn('Purchase reactivated in DB but quota sync returned no active plan — check logs.');

            return self::FAILURE;
        }

        $identity = $active->mikrotik_username ?? $user->phone_number;
        $this->info("Reactivated: {$active->package_name} (purchase #{$active->id}, hotspot: {$identity})");

        return self::SUCCESS;
    }
}
