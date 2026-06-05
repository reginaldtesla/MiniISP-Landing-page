<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\PaymentFulfillmentException;
use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\PackageVoucher;
use App\Models\Transaction;
use App\Services\PaymentFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherController extends Controller
{
    public function redeem(Request $request, PaymentFulfillmentService $fulfillment): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $code = PackageVoucher::normalizeCode($validated['code']);
        $user = $request->user();
        $transaction = null;

        try {
            $transaction = DB::transaction(function () use ($code, $user) {
                $voucher = PackageVoucher::query()
                    ->where('code', $code)
                    ->lockForUpdate()
                    ->first();

                if (! $voucher || $voucher->status !== PackageVoucher::STATUS_AVAILABLE) {
                    throw new PaymentFulfillmentException('Invalid, expired, or already used code.');
                }

                $package = DataPackage::query()
                    ->active()
                    ->where('slug', $voucher->package_slug)
                    ->first();

                if (! $package) {
                    throw new PaymentFulfillmentException('This code\'s package is no longer available. Contact admin.');
                }

                $reference = 'voucher_'.$voucher->id.'_'.now()->format('YmdHis');

                return Transaction::query()->create([
                    'user_id' => $user->id,
                    'type' => 'package',
                    'package_slug' => $voucher->package_slug,
                    'amount' => $voucher->amount,
                    'currency' => 'GHS',
                    'amount_pesewas' => $voucher->amount_pesewas,
                    'paystack_reference' => $reference,
                    'status' => 'pending',
                    'channel' => 'voucher',
                    'metadata' => [
                        'voucher_code' => $voucher->code,
                        'package_name' => $package->name,
                    ],
                    'paystack_response' => null,
                    'paid_at' => null,
                ]);
            });

            $fulfillment->fulfill($transaction, [
                'channel' => 'voucher',
                'code' => $code,
            ]);

            DB::transaction(function () use ($code, $user, $transaction) {
                $voucher = PackageVoucher::query()
                    ->where('code', $code)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($voucher->status !== PackageVoucher::STATUS_AVAILABLE) {
                    return;
                }

                $voucher->update([
                    'status' => PackageVoucher::STATUS_REDEEMED,
                    'redeemed_by' => $user->id,
                    'redeemed_at' => now(),
                    'transaction_id' => $transaction->id,
                ]);
            });
        } catch (PaymentFulfillmentException $exception) {
            $this->discardPendingTransaction($transaction);

            return back()->withErrors(['code' => $exception->getMessage()]);
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->discardPendingTransaction($transaction);

            Log::error('Voucher redemption failed', [
                'user_id' => $user->id,
                'code' => $code,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return back()->withErrors(['code' => 'Database error while activating this code. Ask admin to run php artisan migrate --force, then try again.']);
        } catch (Throwable $exception) {
            $this->discardPendingTransaction($transaction);

            Log::error('Voucher redemption failed', [
                'user_id' => $user->id,
                'code' => $code,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return back()->withErrors(['code' => 'Could not activate this code. Try again or contact support.']);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('status', 'Code accepted! Your plan is active — tap Connect to Internet.');
    }

    protected function discardPendingTransaction(?Transaction $transaction): void
    {
        if ($transaction === null) {
            return;
        }

        $fresh = Transaction::query()->find($transaction->id);

        if ($fresh !== null && $fresh->status === 'pending') {
            $fresh->delete();
        }
    }
}
