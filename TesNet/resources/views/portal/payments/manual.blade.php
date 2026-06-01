@extends('portal.layouts.dashboard')

@section('title', 'Manual Payment — TesNet')

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface mb-2">Manual payment</h1>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-6">
        If Paystack is not working, submit proof of payment (MoMo / Airtime). An admin will approve and activate your plan.
    </p>

    <div class="bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 p-5 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]">
        <form method="POST" action="{{ route('portal.manual-payments.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">What did you buy?</label>
                    <select name="type" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface">
                        <option value="package" {{ old('type', 'package') === 'package' ? 'selected' : '' }}>A package</option>
                        <option value="custom_data" {{ old('type') === 'custom_data' ? 'selected' : '' }}>Custom amount (pay what you have)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Payment method</label>
                    <select name="payment_method" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface">
                        <option value="momo" {{ old('payment_method', 'momo') === 'momo' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="airtime" {{ old('payment_method') === 'airtime' ? 'selected' : '' }}>Airtime</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Package (if applicable)</label>
                    <select name="package" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface">
                        <option value="">Select package</option>
                        @foreach ($packages as $p)
                            <option value="{{ $p->slug }}" {{ old('package') === $p->slug ? 'selected' : '' }}>
                                {{ $p->name }} (GH¢{{ number_format($p->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-1">If you select a package, the amount will be taken from the package price.</p>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Amount paid (GH¢)</label>
                    <input name="amount" value="{{ old('amount') }}" inputmode="decimal" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface" placeholder="e.g. 5">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Provider (optional)</label>
                    <input name="provider" value="{{ old('provider') }}" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface" placeholder="MTN / Vodafone / AirtelTigo">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Payer phone (optional)</label>
                    <input name="payer_phone" value="{{ old('payer_phone') }}" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface" placeholder="0551234567">
                </div>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Payment screenshot (optional)</label>
                <input type="file" name="proof" accept="image/*,application/pdf" class="w-full text-sm text-on-surface-variant dark:text-outline-variant file:mr-3 file:min-h-[44px] file:px-4 file:rounded-lg file:border-0 file:bg-primary-container file:text-primary dark:file:text-primary-fixed-dim"/>
                <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-1">JPG, PNG, WebP, or PDF — max 5 MB.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Reference / MoMo ID (optional)</label>
                    <input name="reference" value="{{ old('reference') }}" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface" placeholder="Transaction ID">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1.5">Note (optional)</label>
                    <input name="note" value="{{ old('note') }}" class="w-full min-h-[44px] rounded-lg bg-surface dark:bg-on-background border border-outline-variant/40 dark:border-outline/30 px-3 text-on-surface dark:text-inverse-on-surface" placeholder="Any extra details">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="min-h-[44px] px-5 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm">
                    Submit request
                </button>
                <a href="{{ route('portal.packages') }}" class="min-h-[44px] px-5 py-2 rounded-lg border border-outline-variant/40 text-on-surface-variant dark:text-outline-variant font-label-sm text-label-sm flex items-center">
                    Back to packages
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

