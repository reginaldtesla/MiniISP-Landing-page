<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\Transaction;
use App\Services\PaymentFulfillmentService;
use App\Services\PaystackService;
use App\Support\CustomDataCalculator;
use App\Support\PaymentCompletionResult;
use App\Support\PortalStatus;
use App\Exceptions\PaymentFulfillmentException;
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
            'purchasesBlocked' => PortalStatus::shouldBlockPurchases(),
        ]);
    }

    public function initializeCustomData(Request $request): RedirectResponse
    {
        if (PortalStatus::shouldBlockPurchases()) {
            return redirect()->route('portal.manual-payments.create')
                ->withErrors(['payment' => 'Purchases are temporarily disabled. Submit a manual payment request or contact support.']);
        }

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
        if (PortalStatus::shouldBlockPurchases()) {
            return redirect()->route('portal.manual-payments.create')
                ->withErrors(['payment' => 'Purchases are temporarily disabled. Submit a manual payment request or contact support.']);
        }

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

        $result = $this->processPayment($reference, $request->user()?->id);

        if ($result->success) {
            return redirect()->route('portal.dashboard')
                ->with('status', $result->message);
        }

        return redirect()->route('portal.dashboard')
            ->withErrors(['payment' => $result->message]);
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
                $result = $this->processPayment($reference);

                if (! $result->success && ! $result->alreadyFulfilled) {
                    Log::error('Paystack webhook fulfillment failed', [
                        'reference' => $reference,
                        'message' => $result->message,
                    ]);

                    return response($result->message, 500);
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

    protected function processPayment(string $reference, ?int $expectedUserId = null): PaymentCompletionResult
    {
        $transaction = Transaction::query()
            ->where('paystack_reference', $reference)
            ->first();

        if (! $transaction) {
            return new PaymentCompletionResult(false, 'Payment record not found.');
        }

        if ($expectedUserId !== null && $transaction->user_id !== $expectedUserId) {
            return new PaymentCompletionResult(false, 'Payment does not belong to your account.');
        }

        if ($transaction->status === 'success') {
            return new PaymentCompletionResult(
                true,
                'Payment already confirmed. Your data plan is active.',
                $transaction,
                alreadyFulfilled: true,
            );
        }

        try {
            $verify = $this->paystack->verify($reference);
        } catch (\Throwable $exception) {
            Log::error('Paystack verify failed', ['reference' => $reference, 'error' => $exception->getMessage()]);

            return new PaymentCompletionResult(false, 'Could not verify payment. Contact support if you were charged.');
        }

        if (! ($verify['status'] ?? false)) {
            return new PaymentCompletionResult(false, $verify['message'] ?? 'Payment verification failed.');
        }

        $data = $verify['data'] ?? [];

        if (($data['status'] ?? '') !== 'success') {
            $transaction->update(['status' => 'failed', 'paystack_response' => $verify]);

            return new PaymentCompletionResult(false, 'Payment was not successful.');
        }

        if ((int) ($data['amount'] ?? 0) !== $transaction->amount_pesewas) {
            Log::warning('Paystack amount mismatch', [
                'reference' => $reference,
                'expected' => $transaction->amount_pesewas,
                'received' => $data['amount'] ?? null,
            ]);

            return new PaymentCompletionResult(false, 'Payment amount mismatch. Contact support.');
        }

        if (! in_array($transaction->type, ['package', 'custom_data'], true)) {
            return new PaymentCompletionResult(false, 'Only data package payments are accepted. Contact support if you were charged.');
        }

        try {
            $this->fulfillment->fulfill($transaction, $data);
        } catch (PaymentFulfillmentException $exception) {
            Log::error('Payment fulfillment failed', [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return new PaymentCompletionResult(false, $exception->getMessage().' Contact support if you were charged.');
        } catch (\Throwable $exception) {
            Log::error('Payment fulfillment error', [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return new PaymentCompletionResult(false, 'Could not activate your plan. Contact support if you were charged.');
        }

        return new PaymentCompletionResult(
            true,
            'Package purchased successfully. Connect to Wi‑Fi from your dashboard.',
            $transaction->fresh(),
        );
    }
}
