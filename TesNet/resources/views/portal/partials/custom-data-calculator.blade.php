@php
    $calcConfig = $customDataConfig ?? [];
    $minAmount = $calcConfig['minAmount'] ?? 1;
    $maxAmount = $calcConfig['maxAmount'] ?? 100;
    $speedMbps = $calcConfig['speedMbps'] ?? 60;
    $purchasesBlocked = $purchasesBlocked ?? \App\Support\PortalStatus::shouldBlockPurchases();
    $paystackReady = config('paystack.secret_key') && ! $purchasesBlocked;
@endphp

<div id="custom-data-calculator"
    class="{{ $card }} mb-8 p-4 sm:p-6 md:p-8"
    data-config="{{ json_encode($calcConfig) }}">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4 sm:mb-6">
        <div class="flex items-center gap-3 min-w-0">
            <div class="bg-secondary-container/30 dark:bg-secondary-container/10 text-secondary dark:text-secondary-fixed-dim p-2.5 rounded-lg shrink-0">
                <span class="material-symbols-outlined">tune</span>
            </div>
            <div>
                <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface">Custom data calculator</h2>
                <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-0.5">
                    @if ($hasSpecialOffers ?? false)
                        Based on standard packages only — limited-time offers above are separate.
                    @else
                        Based on standard packages only (special day offers never affect this).
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div>
                <label for="custom-amount" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Amount to pay (GH¢)</label>
                <input type="number" name="amount" id="custom-amount" data-custom-amount
                    min="{{ $minAmount }}" max="{{ $maxAmount }}" step="0.50" value="{{ old('amount', $minAmount) }}"
                    class="portal-input min-h-[48px] w-full"/>
            </div>
            <div>
                <input type="range" data-custom-range min="{{ $minAmount }}" max="{{ $maxAmount }}" step="0.50"
                    value="{{ old('amount', $minAmount) }}"
                    class="w-full h-2 rounded-full appearance-none bg-surface-variant/60 dark:bg-outline/30 accent-primary dark:accent-primary-fixed-dim cursor-pointer"/>
                <div class="flex justify-between font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-1">
                    <span>GH¢{{ number_format($minAmount, 2) }}</span>
                    <span>GH¢{{ number_format($maxAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-high dark:bg-on-background/50 rounded-xl p-5 sm:p-6 border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant uppercase tracking-wide mb-2">You get</p>
            <p class="font-headline-lg-mobile sm:font-headline-lg text-headline-lg-mobile sm:text-headline-lg text-primary dark:text-primary-fixed-dim tabular-nums">
                <span data-custom-gb>0</span>
                <span class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface">GB</span>
            </p>
            <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-2 tabular-nums">
                <span data-custom-bytes>0</span> bytes · {{ $speedMbps }} Mbps
            </p>
            <form method="POST" action="{{ route('portal.payments.custom') }}" class="mt-5">
                @csrf
                <input type="hidden" name="amount" data-custom-form-amount value="{{ old('amount', $minAmount) }}"/>
                <button type="submit" data-custom-pay @disabled(! $paystackReady)
                    class="w-full min-h-[48px] bg-secondary text-on-secondary dark:bg-secondary-fixed-dim dark:text-inverse-surface rounded-lg font-label-sm text-label-sm flex items-center justify-center gap-2 hover:opacity-90 disabled:opacity-50 transition-colors active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                    @if ($purchasesBlocked)
                        Purchases disabled
                    @else
                        Pay custom amount
                    @endif
                </button>
            </form>
        </div>
    </div>
</div>
