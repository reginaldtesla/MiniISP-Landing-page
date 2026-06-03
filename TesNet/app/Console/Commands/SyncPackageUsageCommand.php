<?php

namespace App\Console\Commands;

use App\Models\PackagePurchase;
use App\Models\User;
use App\Services\PackageQuotaService;
use App\Support\PackageUsage;
use Illuminate\Console\Command;

class SyncPackageUsageCommand extends Command
{
    protected $signature = 'tesnet:sync-package-usage';

    protected $description = 'Refresh package bytes_consumed from radacct/MikroTik and sync RADIUS caps';

    public function handle(PackageQuotaService $quota): int
    {
        $userIds = PackagePurchase::query()
            ->where('status', 'active')
            ->distinct()
            ->pluck('user_id');

        $count = 0;

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);

            if (! $user) {
                continue;
            }

            PackageUsage::syncConsumptionForUser($user);
            $quota->syncForUser($user);
            $count++;
        }

        $this->info("Synced package usage for {$count} user(s).");

        return self::SUCCESS;
    }
}
