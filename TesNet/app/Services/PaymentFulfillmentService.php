<?php

namespace App\Services;

use App\Exceptions\PaymentFulfillmentException;
use App\Models\DataPackage;
use App\Models\PackagePurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Support\HotspotIdentity;
use App\Support\PackageValidity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFulfillmentService
{
    public function __construct(
        protected PackageQuotaService $quota,
        protected HotspotPurchaseService $hotspotPurchase,
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

            $package = $this->validateBeforeFulfillment($locked);

            $locked->update([
                'status' => 'success',
                'channel' => $paystackData['channel'] ?? null,
                'paid_at' => now(),
                'paystack_response' => ['verify' => $paystackData],
            ]);

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

            if ($locked->type === 'package' && $package) {
                $this->activatePackagePurchase($user, $locked, [
                    'package_slug' => $package->slug,
                    'package_name' => $package->name,
                    'data_limit_mb' => $package->data_limit_mb,
                    'data_limit_bytes' => null,
                    'speed_mbps' => $package->speed_mbps,
                    'validity_type' => PackageValidity::typeForPackage($package),
                    'expires_at' => PackageValidity::purchaseExpiresAt($package, now()),
                ]);
            }

            if ($locked->type === 'custom_data') {
                $meta = $locked->metadata ?? [];
                $limitBytes = (int) ($meta['data_limit_bytes'] ?? 0);
                $speedMbps = (int) ($meta['speed_mbps'] ?? config('custom_data.speed_mbps', 60));
                $label = (string) ($meta['data_label'] ?? 'Custom data');

                $this->activatePackagePurchase($user, $locked, [
                    'package_slug' => 'custom',
                    'package_name' => 'Custom · '.$label,
                    'data_limit_mb' => (int) ceil($limitBytes / 1048576),
                    'data_limit_bytes' => $limitBytes,
                    'speed_mbps' => $speedMbps,
                    'validity_type' => PackageValidity::TYPE_UNTIL_FINISHED,
                    'expires_at' => null,
                ]);
            }

            return $locked->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function activatePackagePurchase(User $user, Transaction $transaction, array $attributes): void
    {
        if (HotspotIdentity::perPurchaseEnabled()) {
            $this->hotspotPurchase->retireActivePurchasesFor($user, removeFromRouter: true);
            $this->hotspotPurchase->purgeLegacyPhoneHotspot($user);
        }

        PackagePurchase::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'superseded']);

        $purchase = PackagePurchase::query()->create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'activated_at' => now(),
            'status' => 'active',
            'bytes_consumed' => 0,
            'last_radius_limit_bytes' => 0,
            ...$attributes,
        ]);

        if (HotspotIdentity::perPurchaseEnabled()) {
            $purchase = $this->hotspotPurchase->assignIdentity($purchase, $user);

            if (! $this->hotspotPurchase->provision($purchase, $user)) {
                Log::error('Per-purchase MikroTik provision failed after payment', [
                    'user_id' => $user->id,
                    'purchase_id' => $purchase->id,
                ]);
            }
        }

        $active = $this->quota->syncForUser($user, force: true);

        if ($active === null) {
            Log::error('New package not active after payment', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * @throws PaymentFulfillmentException
     */
    protected function validateBeforeFulfillment(Transaction $transaction): ?DataPackage
    {
        if ($transaction->type === 'wallet_topup') {
            throw new PaymentFulfillmentException('Wallet top-up is not enabled.');
        }

        if (! in_array($transaction->type, ['package', 'custom_data'], true)) {
            throw new PaymentFulfillmentException('Unsupported payment type.');
        }

        if ($transaction->type === 'package') {
            if (! $transaction->package_slug) {
                throw new PaymentFulfillmentException('Package payment is missing package information.');
            }

            $package = DataPackage::query()
                ->active()
                ->where('slug', $transaction->package_slug)
                ->first();

            if (! $package) {
                throw new PaymentFulfillmentException('Package no longer exists or is unavailable.');
            }

            return $package;
        }

        $limitBytes = (int) (($transaction->metadata ?? [])['data_limit_bytes'] ?? 0);

        if ($limitBytes <= 0) {
            throw new PaymentFulfillmentException('Custom data payment is missing a valid data allocation.');
        }

        return null;
    }
}
