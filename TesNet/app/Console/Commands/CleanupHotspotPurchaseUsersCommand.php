<?php

namespace App\Console\Commands;

use App\Models\PackagePurchase;
use App\Services\HotspotPurchaseService;
use Illuminate\Console\Command;

class CleanupHotspotPurchaseUsersCommand extends Command
{
    protected $signature = 'tesnet:cleanup-hotspot-users';

    protected $description = 'Remove or disable retired per-purchase MikroTik hotspot users (tn-*)';

    public function handle(HotspotPurchaseService $hotspotPurchase): int
    {
        $days = max(1, (int) config('tesnet.hotspot_cleanup_days', 30));
        $cutoff = now()->subDays($days);

        $purchases = PackagePurchase::query()
            ->whereNotNull('mikrotik_username')
            ->whereIn('status', ['depleted', 'superseded', 'expired'])
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->limit(500)
            ->get();

        $count = 0;

        foreach ($purchases as $purchase) {
            $hotspotPurchase->retire($purchase, removeFromRouter: true);
            $count++;
        }

        $this->info("Cleaned up {$count} per-purchase hotspot user(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
