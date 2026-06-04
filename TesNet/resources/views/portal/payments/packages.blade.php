@extends('portal.layouts.dashboard')

@section('title', 'Buy Data — TesNet')

@php $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]'; @endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface mb-2">Buy Data</h1>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-4 sm:mb-6">Pay for a package with Paystack (Mobile Money & card). Your internet starts as soon as payment succeeds.</p>

    <div class="mb-6 rounded-lg border border-outline-variant/30 dark:border-outline/30 bg-surface-container dark:bg-inverse-surface px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-label-sm text-label-sm font-semibold text-on-surface dark:text-inverse-on-surface">Paid with MoMo outside Paystack?</p>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant text-sm mt-0.5">Submit your payment proof here. An admin will activate your plan after review.</p>
        </div>
        <a href="{{ route('portal.manual-payments.create') }}" class="shrink-0 min-h-[44px] px-4 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">payments</span>
            Manual payment
        </a>
    </div>

    @if (\App\Support\PortalStatus::shouldBlockPurchases() || ! config('paystack.secret_key'))
        <div class="mb-6 rounded-lg border border-tertiary-fixed dark:border-tertiary-fixed-dim/40 bg-tertiary-fixed/50 dark:bg-tertiary-fixed-dim/10 px-4 py-3 font-body-md text-tertiary dark:text-tertiary-fixed-dim text-sm flex flex-wrap items-center justify-between gap-2">
            <span>
                @if (\App\Support\PortalStatus::shouldBlockPurchases())
                    Purchases are temporarily disabled. You can submit a manual payment request.
                @else
                    Paystack is not configured. You can submit a manual payment request.
                @endif
            </span>
            <a href="{{ route('portal.manual-payments.create') }}" class="min-h-[40px] px-4 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                Manual payment
            </a>
        </div>
    @endif

    @if (! config('paystack.secret_key'))
        <div class="mb-6 rounded-lg border border-tertiary-fixed dark:border-tertiary-fixed-dim/40 bg-tertiary-fixed/50 dark:bg-tertiary-fixed-dim/10 px-4 py-3 font-body-md text-tertiary dark:text-tertiary-fixed-dim text-sm">
            Paystack is not configured. Set PAYSTACK keys in your .env file.
        </div>
    @endif

    @if ($specialPackages->isNotEmpty())
        <section class="special-offers-section mb-8 sm:mb-10 sm:p-6">
            <div class="relative z-[1] flex items-center gap-2.5 mb-5">
                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-tertiary/15 dark:bg-tertiary-fixed-dim/20 text-tertiary dark:text-tertiary-fixed-dim">
                    <span class="material-symbols-outlined text-[24px]">celebration</span>
                </span>
                <div>
                    <h2 class="font-title-md text-title-md special-offers-heading">Limited-time offers</h2>
                    <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mt-0.5">Exclusive deals — grab them before they’re gone</p>
                </div>
            </div>
            <div class="relative z-[1] grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($specialPackages as $package)
                    @include('portal.partials.package-card', ['package' => $package, 'card' => $card, 'highlighted' => true, 'purchasesBlocked' => $purchasesBlocked ?? false])
                @endforeach
            </div>
        </section>
    @endif

    @if ($specialPackages->isNotEmpty())
        <div class="border-t border-outline-variant/40 dark:border-outline/30 my-8 sm:my-10" aria-hidden="true"></div>
    @endif

    @include('portal.partials.custom-data-calculator', [
        'card' => $card,
        'hasSpecialOffers' => $specialPackages->isNotEmpty(),
        'purchasesBlocked' => $purchasesBlocked ?? false,
    ])

    @if ($packages->isEmpty() && $specialPackages->isEmpty())
        <div class="{{ $card }} p-6 text-center">
            <p class="font-body-md text-on-surface-variant dark:text-outline-variant">No data packages are available right now. Please check back later.</p>
        </div>
    @elseif ($packages->isNotEmpty())
        <section class="mt-8 sm:mt-10">
            <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-4">Standard packages</h2>
            <div class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($packages as $package)
                    @include('portal.partials.package-card', ['package' => $package, 'card' => $card, 'highlighted' => false, 'purchasesBlocked' => $purchasesBlocked ?? false])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
    @include('portal.partials.portal-script', ['file' => 'portal-custom-calculator.js'])
@endpush
