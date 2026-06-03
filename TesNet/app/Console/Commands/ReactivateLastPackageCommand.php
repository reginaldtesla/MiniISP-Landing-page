<?php

namespace App\Console\Commands;

use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\MikrotikApiService;
use App\Services\PackageQuotaService;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class ReactivateLastPackageCommand extends Command
{
    protected $signature = 'tesnet:reactivate-last-package {phone : User phone number}';

    protected $description = 'One-time fix: reactivate the latest package purchase after a false depletion';

    public function handle(MikrotikApiService $mikrotik, PackageQuotaService $quota): int
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
        ]);

        if ($user->phone_number) {
            $mikrotik->resetHotspotUsageForUser($user->phone_number);
        }

        $active = $quota->syncForUser($user, force: true);

        if ($active === null) {
            $this->warn('Purchase reactivated in DB but quota sync returned no active plan — check MikroTik API and logs.');

            return self::FAILURE;
        }

        $this->info("Reactivated: {$active->package_name} (purchase #{$active->id}) for {$user->phone_number}");

        return self::SUCCESS;
    }
}
