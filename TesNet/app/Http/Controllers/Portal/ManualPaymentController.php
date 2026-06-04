<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\ManualPaymentRequest;
use App\Support\CustomDataCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ManualPaymentController extends Controller
{
    public function create(): View
    {
        $packages = DataPackage::query()->active()->ordered()->get();

        return view('portal.payments.manual', compact('packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:package,custom_data'],
            'package' => ['required_if:type,package', 'nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:1', 'max:5000'],
            'payment_method' => ['required', 'string', 'in:momo,airtime,cash,other'],
            'provider' => ['nullable', 'string', 'max:64'],
            'payer_phone' => ['nullable', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:128'],
            'note' => ['nullable', 'string', 'max:2000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $type = $validated['type'];
        $amount = (float) $validated['amount'];
        $amountPesewas = (int) round($amount * 100);

        $packageSlug = null;
        $meta = [];

        if ($type === 'package') {
            $package = DataPackage::query()
                ->active()
                ->where('slug', (string) ($validated['package'] ?? ''))
                ->first();

            if (! $package || ! $package->isPurchasable()) {
                return back()->withInput()->withErrors(['package' => 'Select a valid package.']);
            }

            $packageSlug = $package->slug;
            $amount = (float) $package->price;
            $amountPesewas = $package->amountPesewas();
        }

        if ($type === 'custom_data') {
            try {
                $quote = CustomDataCalculator::quote($amount);
            } catch (\InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            $meta = $quote->toMetadata();
        }

        $requestRow = ManualPaymentRequest::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'status' => 'pending',
            'package_slug' => $packageSlug,
            'amount' => $amount,
            'amount_pesewas' => $amountPesewas,
            'payment_method' => $validated['payment_method'],
            'provider' => $validated['provider'] ?? null,
            'payer_phone' => $validated['payer_phone'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'note' => $validated['note'] ?? null,
            'metadata' => $meta ?: null,
        ]);

        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store(
                'manual-payments/'.$requestRow->id,
                'local'
            );
            $requestRow->update(['proof_path' => $path]);
        }

        return redirect()->route('portal.packages')
            ->with('status', 'Your payment request has been submitted. We will activate your plan after confirmation.');
    }
}

