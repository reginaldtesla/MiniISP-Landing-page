<?php

namespace App\Services;

use App\Models\DataPackage;
use App\Models\PackagePurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PackageValidity;
use Illuminate\Support\Facades\DB;

class PaymentFulfillmentService
{
    public function __construct(
        protected RadiusSyncService $radius,
    ) {}

    public function fulfill(Transaction $transaction, array $paystackData): Transaction
    {
        if ($transaction->status === 'success') {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction, $paystackData) {
            $locked = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'success') {
                return $locked;
            }

            $locked->update([
                'status' => 'success',
                'channel' => $paystackData['channel'] ?? null,
                'paid_at' => now(),
                'paystack_response' => ['verify' => $paystackData],
            ]);

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

            if ($locked->type === 'wallet_topup') {
                return $locked->fresh();
            }

            if ($locked->type === 'package' && $locked->package_slug) {
                $package = DataPackage::query()
                    ->where('slug', $locked->package_slug)
                    ->first();

                if ($package) {
                    PackagePurchase::query()
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->update(['status' => 'superseded']);

                    PackagePurchase::query()->create([
                        'user_id' => $user->id,
                        'transaction_id' => $locked->id,
                        'package_slug' => $package->slug,
                        'package_name' => $package->name,
                        'data_limit_mb' => $package->data_limit_mb,
                        'data_limit_bytes' => null,
                        'speed_mbps' => $package->speed_mbps,
                        'validity_type' => PackageValidity::typeForPackage($package),
                        'activated_at' => now(),
                        'expires_at' => PackageValidity::purchaseExpiresAt($package, now()),
                        'status' => 'active',
                    ]);

                    if (PackageValidity::isUnlimited($package)) {
                        $this->radius->applyDataLimitBytes($user, $package->speed_mbps, 0);
                    } else {
                        $this->radius->applyPackageLimits(
                            $user,
                            $package->speed_mbps,
                            $package->data_limit_mb
                        );
                    }
                }
            }

            if ($locked->type === 'custom_data') {
                $meta = $locked->metadata ?? [];
                $limitBytes = (int) ($meta['data_limit_bytes'] ?? 0);
                $speedMbps = (int) ($meta['speed_mbps'] ?? config('custom_data.speed_mbps', 60));
                $label = (string) ($meta['data_label'] ?? 'Custom data');

                if ($limitBytes > 0) {
                    PackagePurchase::query()
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->update(['status' => 'superseded']);

                    PackagePurchase::query()->create([
                        'user_id' => $user->id,
                        'transaction_id' => $locked->id,
                        'package_slug' => 'custom',
                        'package_name' => 'Custom · '.$label,
                        'data_limit_mb' => (int) ceil($limitBytes / 1048576),
                        'data_limit_bytes' => $limitBytes,
                        'speed_mbps' => $speedMbps,
                        'validity_type' => PackageValidity::TYPE_UNTIL_FINISHED,
                        'activated_at' => now(),
                        'expires_at' => null,
                        'status' => 'active',
                    ]);

                    $this->radius->applyDataLimitBytes($user, $speedMbps, $limitBytes);
                }
            }

            return $locked->fresh();
        });
    }
}
