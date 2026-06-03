@extends('portal.layouts.dashboard')

@section('title', 'Dashboard — TesNet')

@php
    $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]';
@endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-8 max-w-container-max w-full mr-auto space-y-6">
    <div>
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 sm:gap-3 mb-2">
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface">
                Hello, {{ $displayName }}!
            </h1>
            @if ($isConnected)
                <div class="flex items-center gap-1 w-fit bg-secondary-container/30 dark:bg-secondary-container/10 text-secondary dark:text-secondary-fixed-dim px-3 py-1.5 rounded-full">
                    <span class="material-symbols-outlined fill text-[18px] sm:text-[20px]">check_circle</span>
                    <span class="font-label-sm text-label-sm">You're connected</span>
                </div>
            @elseif ($hasActivePlan && ! ($blockConnect ?? false))
                <form method="POST" action="{{ route('portal.connect-wifi') }}" class="w-full sm:w-auto shrink-0">@csrf
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed shadow-md ring-2 ring-white/30 dark:ring-white/25 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">wifi</span>
                        Connect to Internet
                    </button>
                </form>
            @elseif ($hasActivePlan && ($blockConnect ?? false))
                <button type="button" disabled aria-disabled="true"
                    class="w-full sm:w-auto shrink-0 flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-primary/40 text-on-primary/70 dark:bg-primary-fixed-dim/40 dark:text-on-primary-fixed/70 cursor-not-allowed opacity-60">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">wifi</span>
                    Connect unavailable
                </button>
            @else
                <button type="button" disabled aria-disabled="true"
                    class="w-full sm:w-auto shrink-0 flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-primary/40 text-on-primary/70 dark:bg-primary-fixed-dim/40 dark:text-on-primary-fixed/70 cursor-not-allowed opacity-60">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">wifi</span>
                    Connect to Internet
                </button>
                <a href="{{ route('portal.packages') }}"
                   class="w-full sm:w-auto shrink-0 flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-tertiary text-on-tertiary dark:bg-tertiary-fixed-dim dark:text-on-tertiary-container shadow-md ring-2 ring-white/20 hover:opacity-90 transition-opacity active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">bolt</span>
                    Buy data to connect
                </a>
            @endif
        </div>
        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">Welcome back to your Wi-Fi dashboard.</p>
    </div>

@include('portal.partials.announcement-modal', ['announcement' => $announcement ?? null])

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6">
    {{-- Your Data --}}
    <div class="{{ $card }} md:col-span-12 p-4 sm:p-6 md:p-8 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-48 sm:w-64 h-48 sm:h-64 bg-primary-fixed dark:opacity-20 opacity-50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-start gap-6 lg:gap-10">
            <div class="flex-1 min-w-0">
                <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-1">Your Data</h2>
                <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-4 line-clamp-2">
                    {{ $hasActivePlan ? ($activePackage->package_name ?? 'Current active plan') : 'No active plan' }}
                </p>

                @if ($hasActivePlan)
                    @if ($isUnlimitedData ?? false)
                        <p class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary dark:text-primary-fixed-dim mb-2">Unlimited data</p>
                        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">No data cap on this plan.</p>
                    @else
                        <p class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary dark:text-primary-fixed-dim tabular-nums mb-1">
                            {{ $dataRemainingLabel }} <span class="font-title-md text-title-md text-on-surface-variant dark:text-outline-variant">left</span>
                        </p>
                        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-4 tabular-nums">
                            {{ $dataUsedLabel }} used of {{ $totalPlanLabel }}
                        </p>
                        <div class="mb-2 flex justify-between gap-2 font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant tabular-nums">
                            <span>{{ $percentRemaining }}% remaining</span>
                            <span>{{ $percentUsed }}% used</span>
                        </div>
                        <div class="h-3 sm:h-4 rounded-full bg-surface-variant/50 dark:bg-outline/25 overflow-hidden" role="progressbar"
                             aria-valuenow="{{ $percentRemaining }}" aria-valuemin="0" aria-valuemax="100"
                             aria-label="Share of plan data still available">
                            <div class="h-full rounded-full bg-primary dark:bg-primary-fixed-dim transition-[width] duration-500"
                                 style="width: {{ $percentRemaining }}%"></div>
                        </div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant/80 dark:text-outline-variant/80 mt-3">
                            The blue bar is how much of this plan is still left. It updates when you open or refresh this page.
                        </p>
                    @endif
                @else
                    <p class="font-title-md text-title-md text-on-surface-variant dark:text-outline-variant mb-4">No active data plan</p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Buy a package to get online.</p>
                @endif
            </div>

            <div class="lg:w-72 shrink-0 flex flex-col gap-4">
                <div class="bg-surface-container-high dark:bg-on-background/50 p-3 sm:p-4 rounded-xl w-full">
                    <div class="flex justify-between items-center mb-2 gap-2">
                        <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Total Plan</span>
                        <span class="font-label-sm text-label-sm text-on-background dark:text-inverse-on-surface shrink-0 tabular-nums">{{ $hasActivePlan ? (($isUnlimitedData ?? false) ? 'Unlimited' : $totalPlanLabel) : '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Validity</span>
                        <span class="font-label-sm text-label-sm text-on-background dark:text-inverse-on-surface shrink-0 text-right">{{ $hasActivePlan ? $validityLabel : '—' }}</span>
                    </div>
                    @if ($hasActivePlan && $planExpiresAt)
                        <div class="flex justify-between items-center gap-2 mt-2 pt-2 border-t border-outline-variant/20 dark:border-outline/20">
                            <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Time left</span>
                            <span id="plan-countdown"
                                  class="font-label-sm text-label-sm font-semibold text-primary dark:text-primary-fixed-dim shrink-0 text-right tabular-nums"
                                  data-expires-at="{{ $planExpiresAt->toIso8601String() }}">
                                <span id="plan-countdown-value">Calculating…</span>
                            </span>
                        </div>
                    @endif
                </div>
                <a href="{{ route('portal.packages') }}"
                   class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm flex items-center justify-center gap-2 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98] shadow-sm">
                    <span class="material-symbols-outlined">add_circle</span>
                    Quick Recharge
                </a>
            </div>
        </div>
    </div>

    {{-- About hotspot --}}
    <div class="md:col-span-12 bg-surface-container-low dark:bg-inverse-surface rounded-xl p-4 sm:p-6 md:p-8 border border-surface-variant/30 dark:border-outline/30 grid grid-cols-1 md:grid-cols-[1fr_auto] items-start md:items-center gap-4 md:gap-6 shadow-sm">
        <div class="min-w-0 w-full max-w-none">
            <h3 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-2">About the hotspot</h3>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
                Learn how to connect, how your data & speed work, and quick troubleshooting steps.
            </p>
        </div>
        <a href="{{ route('portal.about') }}"
           class="w-full md:w-auto min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg px-6 py-3 font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98] inline-flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">info</span>
            Open
        </a>
    </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('portal.partials.portal-script', ['file' => 'portal-announcements.js'])
    @if ($hasActivePlan && $planExpiresAt)
        @include('portal.partials.portal-script', ['file' => 'portal-plan-countdown.js'])
    @endif
@endpush
