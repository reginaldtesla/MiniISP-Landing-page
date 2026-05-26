<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\Transaction;
use App\Services\PaymentFulfillmentService;
use App\Services\PaystackService;
use App\Support\CustomDataCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaystackService $paystack,
        protected PaymentFulfillmentService $fulfillment,
    ) {}

    public function packages(): View
    {
        $specialPackages = DataPackage::query()
            ->active()
            ->visibleSpecialOffer()
            ->ordered()
            ->get();

        $packages = DataPackage::query()
            ->active()
            ->regular()
            ->ordered()
            ->get();

        return view('portal.payments.packages', [
            'packages' => $packages,
            'specialPackages' => $specialPackages,
            'paystackPublicKey' => config('paystack.public_key'),
            'customDataConfig' => CustomDataCalculator::frontendConfig(),
        ]);
    }

    public function initializeCustomData(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:5000'],
        ]);

        $user = $request->user();

        try {
            $quote = CustomDataCalculator::quote((float) $validated['amount']);
            $transaction = $this->paystack->initializeCustomDataPayment($user, $quote);
            $url = $transaction->authorizationUrl();

            if (! $url) {
                return back()->withErrors(['payment' => 'Could not start Paystack checkout. Please try again.']);
            }

            return redirect()->away($url);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            Log::error('Paystack custom data init failed', [
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function initializePackage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'string'],
        ]);

        $package = DataPackage::query()
            ->active()
            ->where('slug', $validated['package'])
            ->first();

        if (! $package || ! $package->isPurchasable()) {
            return back()->withErrors(['package' => 'Invalid or unavailable package.']);
        }

        $user = $request->user();

        try {
            $transaction = $this->paystack->initializePackagePayment($user, $package);
            $url = $transaction->authorizationUrl();

            if (! $url) {
                return back()->withErrors(['payment' => 'Could not start Paystack checkout. Please try again.']);
            }

            return redirect()->away($url);
        } catch (\Throwable $exception) {
            Log::error('Paystack package init failed', [
                'user_id' => $user->id,
                'package' => $package->slug,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference') ?? $request->input('reference');

        if (! $reference) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Missing payment reference.']);
        }

        return $this->completePayment($reference, $request->user()?->id);
    }

    public function webhook(Request $request): Response
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (! $this->paystack->verifyWebhookSignature($payload, $signature)) {
            return response('Invalid signature', 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success' && is_array($data)) {
            $reference = $data['reference'] ?? null;

            if ($reference) {
                try {
                    $this->completePayment($reference);
                } catch (\Throwable $exception) {
                    Log::error('Paystack webhook fulfillment failed', [
                        'reference' => $reference,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    public function history(Request $request): View
    {
        $transactions = $request->user()
            ->transactions()
            ->whereIn('type', ['package', 'custom_data'])
            ->latest()
            ->paginate(15);

        return view('portal.payments.history', compact('transactions'));
    }

    protected function completePayment(string $reference, ?int $expectedUserId = null): RedirectResponse
    {
        $transaction = Transaction::query()
            ->where('paystack_reference', $reference)
            ->first();

        if (! $transaction) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Payment record not found.']);
        }

        if ($expectedUserId !== null && $transaction->user_id !== $expectedUserId) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Payment does not belong to your account.']);
        }

        if ($transaction->status === 'success') {
            return redirect()->route('portal.dashboard')
                ->with('status', 'Payment already confirmed. Your data plan is active.');
        }

        try {
            $verify = $this->paystack->verify($reference);
        } catch (\Throwable $exception) {
            Log::error('Paystack verify failed', ['reference' => $reference, 'error' => $exception->getMessage()]);

            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Could not verify payment. Contact support if you were charged.']);
        }

        if (! ($verify['status'] ?? false)) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => $verify['message'] ?? 'Payment verification failed.']);
        }

        $data = $verify['data'] ?? [];

        if (($data['status'] ?? '') !== 'success') {
            $transaction->update(['status' => 'failed', 'paystack_response' => $verify]);

            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Payment was not successful.']);
        }

        if ((int) ($data['amount'] ?? 0) !== $transaction->amount_pesewas) {
            Log::warning('Paystack amount mismatch', [
                'reference' => $reference,
                'expected' => $transaction->amount_pesewas,
                'received' => $data['amount'] ?? null,
            ]);

            return redirect()->route('portal.dashboard')
                ->withErrors(['payment' => 'Payment amount mismatch. Contact support.']);
        }

        if (! in_array($transaction->type, ['package', 'custom_data'], true)) {
            return redirect()->route('portal.packages')
                ->withErrors(['payment' => 'Only data package payments are accepted. Contact support if you were charged.']);
        }

        $this->fulfillment->fulfill($transaction, $data);

        return redirect()->route('portal.dashboard')
            ->with('status', 'Package purchased successfully. Connect to Wi‑Fi from your dashboard.');
    }
}
