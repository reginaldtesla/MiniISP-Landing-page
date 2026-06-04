<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentFulfillmentException;
use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\ManualPaymentRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentFulfillmentService;
use App\Support\CustomDataCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ManualPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';

        $requests = ManualPaymentRequest::query()
            ->with(['user', 'reviewer'])
            ->where('status', $status)
            ->latest()
            ->paginate(25);

        return view('admin.manual-payments.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function approve(
        ManualPaymentRequest $manualPaymentRequest,
        Request $request,
        PaymentFulfillmentService $fulfillment
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($manualPaymentRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This request has already been reviewed.']);
        }

        $approved = false;

        try {
            $fulfillmentMeta = $this->resolveFulfillmentMetadata($manualPaymentRequest);

            DB::transaction(function () use ($manualPaymentRequest, $request, $fulfillment, $validated, $fulfillmentMeta, &$approved) {
                $locked = ManualPaymentRequest::query()
                    ->whereKey($manualPaymentRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== 'pending') {
                    return;
                }

                $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

                $txType = $locked->type;
                $packageSlug = null;
                $metadata = [];

                if ($txType === 'package') {
                    $packageSlug = $locked->package_slug;
                }

                if ($txType === 'custom_data') {
                    $metadata = $fulfillmentMeta;
                }

                $reference = 'manual_'.$locked->id.'_'.now()->format('YmdHis');

                $transaction = Transaction::query()->create([
                    'user_id' => $user->id,
                    'type' => $txType,
                    'package_slug' => $packageSlug,
                    'amount' => $locked->amount,
                    'currency' => 'GHS',
                    'amount_pesewas' => $locked->amount_pesewas,
                    'paystack_reference' => $reference,
                    'status' => 'pending',
                    'channel' => 'manual',
                    'metadata' => $metadata ?: null,
                    'paystack_response' => null,
                    'paid_at' => null,
                ]);

                $fulfillment->fulfill($transaction, [
                    'channel' => 'manual',
                    'reference' => $locked->reference,
                    'provider' => $locked->provider,
                    'method' => $locked->payment_method,
                ]);

                $locked->update([
                    'status' => 'approved',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'transaction_id' => $transaction->id,
                    'admin_note' => $validated['admin_note'] ?? null,
                ]);

                $approved = true;
            });
        } catch (PaymentFulfillmentException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::error('Manual payment approval failed', [
                'manual_payment_request_id' => $manualPaymentRequest->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'request' => 'Could not approve this request. Run php artisan migrate --force on the server, then try again. Details were logged.',
            ]);
        }

        if (! $approved) {
            return back()->withErrors(['request' => 'This request has already been reviewed.']);
        }

        return back()->with('status', 'Manual payment approved and plan activated.');
    }

    public function reject(ManualPaymentRequest $manualPaymentRequest, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if ($manualPaymentRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This request has already been reviewed.']);
        }

        ManualPaymentRequest::query()
            ->whereKey($manualPaymentRequest->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'],
            ]);

        return back()->with('status', 'Request rejected.');
    }

    public function proof(ManualPaymentRequest $manualPaymentRequest): StreamedResponse
    {
        if (! $manualPaymentRequest->proof_path || ! Storage::disk('local')->exists($manualPaymentRequest->proof_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $manualPaymentRequest->proof_path,
            'manual-payment-'.$manualPaymentRequest->id.'-proof'
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PaymentFulfillmentException
     */
    protected function resolveFulfillmentMetadata(ManualPaymentRequest $manualPaymentRequest): array
    {
        if ($manualPaymentRequest->type === 'package') {
            if (! $manualPaymentRequest->package_slug) {
                throw new PaymentFulfillmentException('This request has no package selected. Reject it and ask the student to submit again.');
            }

            $package = DataPackage::query()
                ->active()
                ->where('slug', $manualPaymentRequest->package_slug)
                ->first();

            if (! $package) {
                throw new PaymentFulfillmentException('Package "'.$manualPaymentRequest->package_slug.'" is missing or inactive. Reactivate the package or reject this request.');
            }

            return [];
        }

        if ($manualPaymentRequest->type !== 'custom_data') {
            throw new PaymentFulfillmentException('Unsupported manual payment type.');
        }

        $metadata = $manualPaymentRequest->metadata ?? [];
        $limitBytes = (int) ($metadata['data_limit_bytes'] ?? 0);

        if ($limitBytes > 0) {
            return $metadata;
        }

        try {
            return CustomDataCalculator::quote((float) $manualPaymentRequest->amount)->toMetadata();
        } catch (\InvalidArgumentException $exception) {
            throw new PaymentFulfillmentException($exception->getMessage());
        }
    }
}
