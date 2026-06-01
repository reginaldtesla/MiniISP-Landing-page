@php
    $highlighted = $highlighted ?? false;
    $purchasesBlocked = $purchasesBlocked ?? \App\Support\PortalStatus::shouldBlockPurchases();
    $paystackReady = config('paystack.secret_key') && ! $purchasesBlocked;
@endphp

@if ($highlighted)
    <div class="special-offer-card">
        <div class="special-offer-card-glow" aria-hidden="true"></div>
        @if ($package->promo_label || $package->is_special_offer)
            <div class="special-offer-badge">
                {{ $package->promo_label ?: 'Special offer' }}
            </div>
        @endif
        <p class="relative z-[1] font-label-sm text-label-sm text-tertiary dark:text-tertiary-fixed-dim uppercase tracking-wide pr-28 font-semibold">{{ $package->name }}</p>
        <p class="relative z-[1] font-headline-lg-mobile sm:font-headline-lg text-headline-lg-mobile sm:text-headline-lg special-offer-data mt-1">{{ $package->data_label }}</p>
        <p class="relative z-[1] font-title-md text-title-md text-on-background dark:text-inverse-on-surface mt-1">GH¢{{ number_format($package->price, 2) }}</p>
        <p class="relative z-[1] font-body-md text-body-md text-on-surface-variant dark:text-outline-variant flex-1">
            {{ $package->speedLabel() }} · {{ $package->validityLabel() }}
        </p>
        @if ($package->description)
            <p class="relative z-[1] font-body-md text-sm text-on-surface-variant dark:text-outline-variant mt-2">{{ $package->description }}</p>
        @endif
        <form method="POST" action="{{ route('portal.payments.package') }}" class="relative z-[1] mt-4">
            @csrf
            <input type="hidden" name="package" value="{{ $package->slug }}">
            <button type="submit" @disabled(! $paystackReady) class="special-offer-btn">
                <span class="material-symbols-outlined text-[20px]">payments</span>
                @if ($purchasesBlocked)
                    Purchases disabled
                @else
                    Grab this offer
                @endif
            </button>
        </form>
    </div>
@else
    <div class="{{ $card }} p-4 sm:p-6 flex flex-col">
        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant uppercase tracking-wide">{{ $package->name }}</p>
        <p class="font-headline-lg-mobile sm:font-headline-lg text-headline-lg-mobile sm:text-headline-lg text-primary dark:text-primary-fixed-dim mt-1">{{ $package->data_label }}</p>
        <p class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mt-1">GH¢{{ number_format($package->price, 2) }}</p>
        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant flex-1">
            {{ $package->speedLabel() }} · {{ $package->validityLabel() }}
        </p>
        @if ($package->description)
            <p class="font-body-md text-sm text-on-surface-variant dark:text-outline-variant mt-2">{{ $package->description }}</p>
        @endif
        <form method="POST" action="{{ route('portal.payments.package') }}" class="mt-4">
            @csrf
            <input type="hidden" name="package" value="{{ $package->slug }}">
            <button type="submit" @disabled(! $paystackReady)
                class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm flex items-center justify-center gap-2 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 disabled:opacity-50 transition-colors active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">payments</span>
                @if ($purchasesBlocked)
                    Purchases disabled
                @else
                    Pay with Paystack
                @endif
            </button>
        </form>
    </div>
@endif
